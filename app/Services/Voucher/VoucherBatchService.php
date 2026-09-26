<?php

namespace App\Services\Voucher;

use App\Enums\PasswordGenerationMethod;
use App\Enums\UsernameGenerationMethod;
use App\Enums\VoucherBatchStatus;
use App\Jobs\CreateHotspotVoucherChunkJob;
use App\Models\Mikrotik;
use App\Models\Voucher;
use App\Models\VoucherBatch;
use App\Services\Mikrotik\Exceptions\MikrotikConnectionException;
use App\Services\Mikrotik\MikrotikServiceFactory;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Orchestrates batch voucher generation: creates the VoucherBatch and its
 * Voucher rows inside a single transaction, then dispatches chunked queue
 * jobs (never more than CHUNK_SIZE per job) to actually create each
 * hotspot user on the router — per spec section 6, a batch of 1000 is
 * never processed inside the triggering HTTP request.
 */
class VoucherBatchService
{
    private const CHUNK_SIZE = 100;

    public function __construct(
        private readonly UsernameGenerator $usernameGenerator,
        private readonly PasswordGenerator $passwordGenerator,
        private readonly MikrotikServiceFactory $serviceFactory,
    ) {}

    /**
     * @param  array{
     *     mikrotik_id: int, profile: string, quantity: int, username_prefix: string,
     *     username_method: UsernameGenerationMethod, username_length: int,
     *     password_method: PasswordGenerationMethod, password_length: int,
     * }  $data
     */
    public function generate(array $data, ?int $createdBy): VoucherBatch
    {
        $mikrotik = Mikrotik::findOrFail($data['mikrotik_id']);

        $usernames = $this->usernameGenerator->generate(
            $data['username_method'],
            $data['username_prefix'],
            $data['username_length'],
            $data['quantity'],
            $mikrotik->id,
        );

        $passwords = $this->passwordGenerator->generateMany(
            $data['password_method'],
            $data['password_length'],
            $data['quantity'],
        );

        // Read the profile's own session-timeout live from the router
        // rather than trusting a value submitted from the browser — the
        // generate form only posts the profile's name, never its
        // parameters (spec section 15: don't trust client input for
        // security/business-relevant values). Falls back to null (today's
        // behavior) if the router can't be reached right now; the batch
        // still proceeds.
        $limitUptime = $this->resolveLimitUptime($mikrotik, $data['profile']);

        $batch = DB::transaction(function () use ($data, $mikrotik, $usernames, $passwords, $createdBy, $limitUptime) {
            $batch = VoucherBatch::create([
                'mikrotik_id' => $mikrotik->id,
                'batch_code' => $this->generateBatchCode(),
                'profile' => $data['profile'],
                'quantity' => $data['quantity'],
                'username_prefix' => $data['username_prefix'],
                'username_method' => $data['username_method'],
                'username_length' => $data['username_length'],
                'password_method' => $data['password_method'],
                'password_length' => $data['password_length'],
                'created_by' => $createdBy,
            ]);

            $now = now();

            $rows = array_map(
                fn (string $username, string $password) => [
                    'batch_id' => $batch->id,
                    'mikrotik_id' => $mikrotik->id,
                    'username' => $username,
                    'password' => $password,
                    'profile' => $data['profile'],
                    'status' => 'PENDING',
                    'limit_uptime' => $limitUptime,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                $usernames,
                $passwords,
            );

            foreach (array_chunk($rows, 500) as $insertChunk) {
                Voucher::insert($insertChunk);
            }

            return $batch;
        });

        $this->dispatchCreationJobs($batch);

        return $batch;
    }

    private function dispatchCreationJobs(VoucherBatch $batch): void
    {
        $jobs = $batch->vouchers()
            ->pluck('id')
            ->chunk(self::CHUNK_SIZE)
            ->map(fn ($chunk) => new CreateHotspotVoucherChunkJob($chunk->values()->all()))
            ->all();

        $batchId = $batch->id;

        // Must be saved BEFORE dispatch(): on a synchronous queue
        // connection the jobs — and the ->finally() finalizer below —
        // run inline inside dispatch() itself, so they may already have
        // resolved the batch to its final status before this method
        // returns. Setting PROCESSING afterwards would clobber that.
        $batch->update(['status' => VoucherBatchStatus::Processing]);

        try {
            $laravelBatch = Bus::batch($jobs)
                ->name("voucher-batch-{$batchId}")
                ->onQueue('voucher-generation')
                // Without this, Laravel cancels the ENTIRE batch the
                // moment any single chunk job fails — every other chunk
                // still queued would then skip itself (each job checks
                // $this->batch()->cancelled()) and never even attempt
                // its vouchers. A batch of 1000 must not go PARTIAL just
                // because one chunk out of ten had a bad router.
                ->allowFailures()
                ->finally(function () use ($batchId) {
                    app(VoucherBatchFinalizer::class)->finalize($batchId);
                })
                ->dispatch();

            $batch->update(['job_batch_id' => $laravelBatch->id]);
        } catch (Throwable $e) {
            // On the 'sync' queue connection (local dev convenience, or
            // tests) a job failure propagates straight back here instead
            // of being handled by a separate worker process — and the
            // ->finally() callback above may not have run yet in that
            // case. Resolve the batch from whatever state the jobs left
            // behind rather than leaving it stuck in PROCESSING or
            // letting this bubble into an unhandled 500.
            app(VoucherBatchFinalizer::class)->finalize($batchId);
        }
    }

    private function generateBatchCode(): string
    {
        return 'WIFI-'.now()->format('Y-m-d').'-'.strtoupper(Str::random(4));
    }

    private function resolveLimitUptime(Mikrotik $mikrotik, string $profileName): ?string
    {
        try {
            $profiles = $this->serviceFactory->make($mikrotik)->getHotspotProfiles();
        } catch (MikrotikConnectionException) {
            return null;
        }

        $profile = collect($profiles)->firstWhere('name', $profileName);
        $sessionTimeout = $profile['session-timeout'] ?? null;

        return in_array($sessionTimeout, [null, '', '0s', 'none'], true) ? null : $sessionTimeout;
    }
}
