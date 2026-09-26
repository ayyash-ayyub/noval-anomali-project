<x-app-layout>
    <x-slot name="header">Voucher {{ $voucher->username }}</x-slot>

    <div class="flex items-center justify-between mb-6">
        <x-status-badge :status="$voucher->status" />

        <div class="flex items-center gap-2">
            <form method="POST" action="{{ route('vouchers.print.store') }}" class="flex items-center gap-2">
                @csrf
                <input type="hidden" name="voucher_ids[]" value="{{ $voucher->id }}">
                <select name="paper_size" class="bg-black border-matrix-border text-matrix-green text-xs rounded-md py-1.5 focus:border-matrix-green focus:ring-matrix-green">
                    @foreach (\App\Enums\PdfPaperSize::cases() as $size)
                        <option value="{{ $size->value }}">{{ $size->label() }}</option>
                    @endforeach
                </select>
                <x-secondary-button type="submit">Print PDF</x-secondary-button>
            </form>

            @if (in_array($voucher->status->value, ['SYNCED', 'ACTIVE']))
                <form method="POST" action="{{ route('vouchers.disable', $voucher) }}"
                    onsubmit="return confirm('Nonaktifkan voucher {{ $voucher->username }} di router?');">
                    @csrf
                    <x-danger-button type="submit">Disable Voucher</x-danger-button>
                </form>
            @elseif ($voucher->status->value === 'FAILED')
                <form method="POST" action="{{ route('vouchers.retry', $voucher) }}">
                    @csrf
                    <x-primary-button type="submit">Retry Voucher</x-primary-button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-matrix-panel border border-matrix-border rounded-md p-4">
            <h3 class="text-xs uppercase tracking-widest text-matrix-green-deep mb-3">WiFi Access</h3>
            <dl class="grid grid-cols-2 gap-y-3 text-sm">
                <dt class="text-matrix-green-deep">Username</dt>
                <dd class="text-matrix-green font-mono">{{ $voucher->username }}</dd>

                <dt class="text-matrix-green-deep">Password</dt>
                <dd class="text-matrix-green font-mono">{{ $voucher->password }}</dd>

                <dt class="text-matrix-green-deep">Profile</dt>
                <dd class="text-matrix-green">{{ $voucher->profile }}</dd>

                <dt class="text-matrix-green-deep">Limit Uptime</dt>
                <dd class="text-matrix-green">{{ $voucher->limit_uptime ?? '—' }}</dd>

                <dt class="text-matrix-green-deep">Router</dt>
                <dd class="text-matrix-green">
                    <a href="{{ route('mikrotiks.show', $voucher->mikrotik) }}" class="hover:underline">{{ $voucher->mikrotik->name }}</a>
                </dd>

                <dt class="text-matrix-green-deep">Batch</dt>
                <dd class="text-matrix-green">
                    <a href="{{ route('vouchers.batches.show', $voucher->batch) }}" class="hover:underline">{{ $voucher->batch->batch_code }}</a>
                </dd>
            </dl>
        </div>

        <div class="bg-matrix-panel border border-matrix-border rounded-md p-4">
            <h3 class="text-xs uppercase tracking-widest text-matrix-green-deep mb-3">Riwayat</h3>
            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-[10px] uppercase text-matrix-green-deep">Dibuat</dt>
                    <dd class="text-matrix-green">{{ $voucher->created_at->format('Y-m-d H:i:s') }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] uppercase text-matrix-green-deep">Tersinkronisasi ke Router</dt>
                    <dd class="text-matrix-green">{{ $voucher->last_synced_at?->format('Y-m-d H:i:s') ?? 'Belum' }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] uppercase text-matrix-green-deep">Aktif Sejak</dt>
                    <dd class="text-matrix-green">{{ $voucher->activated_at?->format('Y-m-d H:i:s') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] uppercase text-matrix-green-deep">Kedaluwarsa</dt>
                    <dd class="text-matrix-green">{{ $voucher->expired_at?->format('Y-m-d H:i:s') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] uppercase text-matrix-green-deep">Dinonaktifkan</dt>
                    <dd class="text-matrix-green">{{ $voucher->disabled_at?->format('Y-m-d H:i:s') ?? '—' }}</dd>
                </div>
                @if ($voucher->sync_error)
                    <div>
                        <dt class="text-[10px] uppercase text-red-400">Sync Error</dt>
                        <dd class="text-red-400 break-words">{{ $voucher->sync_error }}</dd>
                    </div>
                @endif
            </dl>
        </div>
    </div>
</x-app-layout>
