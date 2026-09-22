<?php

namespace App\Http\Controllers;

use App\Enums\PasswordGenerationMethod;
use App\Enums\UsernameGenerationMethod;
use App\Enums\VoucherStatus;
use App\Http\Requests\GenerateVoucherRequest;
use App\Models\Mikrotik;
use App\Models\Voucher;
use App\Models\VoucherBatch;
use App\Services\AuditLogService;
use App\Services\Mikrotik\Exceptions\MikrotikConnectionException;
use App\Services\Mikrotik\MikrotikServiceFactory;
use App\Services\Voucher\HotspotUserSyncer;
use App\Services\Voucher\VoucherBatchFinalizer;
use App\Services\Voucher\VoucherBatchService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function __construct(
        private readonly VoucherBatchService $batchService,
        private readonly MikrotikServiceFactory $serviceFactory,
        private readonly AuditLogService $auditLog,
    ) {
    }

    public function index(Request $request): View
    {
        $vouchers = Voucher::query()
            ->with(['mikrotik', 'batch'])
            ->when($request->filled('mikrotik_id'), fn ($q) => $q->where('mikrotik_id', $request->integer('mikrotik_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest('created_at')
            ->paginate(50)
            ->withQueryString();

        $mikrotiks = Mikrotik::orderBy('name')->get();

        return view('vouchers.index', compact('vouchers', 'mikrotiks'));
    }

    public function create(Request $request): View
    {
        $mikrotiks = Mikrotik::orderBy('name')->get();

        $selected = $mikrotiks->firstWhere('id', (int) $request->query('mikrotik'))
            ?? $mikrotiks->first();

        $profiles = [];
        $error = null;

        if ($selected) {
            try {
                $profiles = $this->serviceFactory->make($selected)->getHotspotProfiles();
            } catch (MikrotikConnectionException $e) {
                $error = $e->getMessage();
            }
        }

        return view('vouchers.generate', [
            'mikrotiks' => $mikrotiks,
            'selected' => $selected,
            'profiles' => $profiles,
            'error' => $error,
        ]);
    }

    public function store(GenerateVoucherRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['username_method'] = UsernameGenerationMethod::from($data['username_method']);
        $data['password_method'] = PasswordGenerationMethod::from($data['password_method']);

        $batch = $this->batchService->generate($data, $request->user()->id);

        $this->auditLog->log(
            action: 'voucher.generate_batch',
            description: "Batch '{$batch->batch_code}' ({$batch->quantity} voucher, profile '{$batch->profile}') dibuat.",
            mikrotik: $batch->mikrotik,
            targetType: VoucherBatch::class,
            targetId: $batch->id,
        );

        return redirect()->route('vouchers.batches.show', $batch)
            ->with('status', "Batch '{$batch->batch_code}' sedang diproses di background.");
    }

    public function show(Voucher $voucher): View
    {
        $voucher->load(['mikrotik', 'batch']);

        return view('vouchers.show', compact('voucher'));
    }

    public function disable(Voucher $voucher): RedirectResponse
    {
        try {
            $this->serviceFactory->make($voucher->mikrotik)->disableHotspotUser($voucher->username);
        } catch (MikrotikConnectionException $e) {
            $this->auditLog->log(
                action: 'voucher.disable',
                result: 'failed',
                description: "Gagal menonaktifkan voucher '{$voucher->username}': {$e->getMessage()}",
                mikrotik: $voucher->mikrotik,
                targetType: Voucher::class,
                targetId: $voucher->id,
            );

            return back()->with('error', "Gagal menonaktifkan voucher di router: {$e->getMessage()}");
        }

        $voucher->update([
            'status' => VoucherStatus::Disabled,
            'disabled_at' => now(),
        ]);

        $this->auditLog->log(
            action: 'voucher.disable',
            description: "Voucher '{$voucher->username}' dinonaktifkan.",
            mikrotik: $voucher->mikrotik,
            targetType: Voucher::class,
            targetId: $voucher->id,
        );

        return back()->with('status', "Voucher '{$voucher->username}' berhasil dinonaktifkan.");
    }

    /**
     * Manually retries a single FAILED voucher. Unlike batch generation,
     * this is a single, bounded, user-initiated action — it runs inline
     * (not queued) so the operator gets an immediate result, and goes
     * through the same idempotency-aware HotspotUserSyncer the queue
     * job uses.
     */
    public function retry(Voucher $voucher, HotspotUserSyncer $syncer, VoucherBatchFinalizer $finalizer): RedirectResponse
    {
        if ($voucher->status !== VoucherStatus::Failed) {
            return back()->with('error', "Voucher '{$voucher->username}' tidak dalam status Failed.");
        }

        $voucher->update(['status' => VoucherStatus::Pending]);

        try {
            $syncer->sync($this->serviceFactory->make($voucher->mikrotik), $voucher);
        } catch (MikrotikConnectionException $e) {
            $voucher->update([
                'status' => VoucherStatus::Failed,
                'last_synced_at' => now(),
                'sync_error' => $e->getMessage(),
            ]);

            $finalizer->finalize($voucher->batch_id);

            $this->auditLog->log(
                action: 'voucher.retry',
                result: 'failed',
                description: "Retry voucher '{$voucher->username}' gagal: {$e->getMessage()}",
                mikrotik: $voucher->mikrotik,
                targetType: Voucher::class,
                targetId: $voucher->id,
            );

            return back()->with('error', "Retry gagal: {$e->getMessage()}");
        }

        $finalizer->finalize($voucher->batch_id);

        $this->auditLog->log(
            action: 'voucher.retry',
            description: "Retry voucher '{$voucher->username}' berhasil.",
            mikrotik: $voucher->mikrotik,
            targetType: Voucher::class,
            targetId: $voucher->id,
        );

        return back()->with('status', "Voucher '{$voucher->username}' berhasil disinkronkan ulang.");
    }
}
