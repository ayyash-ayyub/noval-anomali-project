<x-app-layout>
    <x-slot name="header">Batch {{ $batch->batch_code }}</x-slot>

    <div
        x-data="{
            status: '{{ $batch->status->value }}',
            total: {{ $progress['total'] }},
            success: {{ $progress['success'] }},
            failed: {{ $progress['failed'] }},
            pending: {{ $progress['pending'] }},
            percent: {{ $progress['percent'] }},
            poll() {
                if (this.status !== 'PROCESSING' && this.status !== 'PENDING') return;
                fetch('{{ route('vouchers.batches.progress', $batch) }}')
                    .then(r => r.json())
                    .then(data => {
                        this.status = data.status;
                        this.total = data.total;
                        this.success = data.success;
                        this.failed = data.failed;
                        this.pending = data.pending;
                        this.percent = data.percent;
                        if (this.status === 'PROCESSING' || this.status === 'PENDING') {
                            setTimeout(() => this.poll(), 3000);
                        } else {
                            location.reload();
                        }
                    });
            }
        }"
        x-init="poll()"
    >
        <div class="mb-6 bg-matrix-panel border border-matrix-border rounded-md p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-[11px] uppercase tracking-widest text-matrix-green-deep">
                        {{ $batch->mikrotik->name }} &middot; {{ $batch->profile }}
                    </p>
                    <p class="font-mono text-matrix-green text-lg">{{ $batch->batch_code }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <span x-show="status === 'PROCESSING' || status === 'PENDING'" class="flex items-center gap-2 text-xs text-matrix-green-dim">
                        <span class="w-2 h-2 rounded-full bg-matrix-green animate-pulse"></span> live
                    </span>
                    <template x-if="status !== 'PROCESSING' && status !== 'PENDING'">
                        <x-status-badge :status="$batch->status" />
                    </template>

                    <form method="POST" action="{{ route('vouchers.print.store') }}" class="flex items-center gap-2">
                        @csrf
                        <input type="hidden" name="batch_id" value="{{ $batch->id }}">
                        <select name="paper_size" class="bg-black border-matrix-border text-matrix-green text-xs rounded-md py-1.5 focus:border-matrix-green focus:ring-matrix-green">
                            @foreach (\App\Enums\PdfPaperSize::cases() as $size)
                                <option value="{{ $size->value }}">{{ $size->label() }}</option>
                            @endforeach
                        </select>
                        <x-secondary-button type="submit">Print All</x-secondary-button>
                    </form>
                </div>
            </div>

            <div class="w-full bg-black border border-matrix-border rounded-full h-3 overflow-hidden mb-3">
                <div class="h-full bg-matrix-green transition-all duration-500" :style="`width: ${percent}%`"></div>
            </div>
            <p class="text-right text-xs text-matrix-green-dim" x-text="percent + '%'"></p>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
            <div class="bg-matrix-panel border border-matrix-green/40 rounded-md p-4">
                <span class="text-[10px] uppercase tracking-widest text-matrix-green-deep">Total</span>
                <p class="text-2xl font-bold text-matrix-green" x-text="total"></p>
            </div>
            <div class="bg-matrix-panel border border-matrix-green/40 rounded-md p-4">
                <span class="text-[10px] uppercase tracking-widest text-matrix-green-deep">Success</span>
                <p class="text-2xl font-bold text-matrix-green" x-text="success"></p>
            </div>
            <div class="bg-matrix-panel border border-red-500/40 rounded-md p-4">
                <span class="text-[10px] uppercase tracking-widest text-matrix-green-deep">Failed</span>
                <p class="text-2xl font-bold text-red-400" x-text="failed"></p>
            </div>
            <div class="bg-matrix-panel border border-gray-500/40 rounded-md p-4">
                <span class="text-[10px] uppercase tracking-widest text-matrix-green-deep">Pending</span>
                <p class="text-2xl font-bold text-gray-400" x-text="pending"></p>
            </div>
        </div>
    </div>

    <div class="bg-matrix-panel border border-matrix-border rounded-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-matrix-border text-left text-[11px] uppercase tracking-widest text-matrix-green-deep">
                        <th class="px-4 py-3">Username</th>
                        <th class="px-4 py-3">Password</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Sync Error</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-matrix-border/60">
                    @foreach ($vouchers as $voucher)
                        <tr class="hover:bg-matrix-green/5">
                            <td class="px-4 py-3">
                                <a href="{{ route('vouchers.show', $voucher) }}" class="text-matrix-green hover:underline font-mono">{{ $voucher->username }}</a>
                            </td>
                            <td class="px-4 py-3 text-matrix-green-dim font-mono">{{ $voucher->password }}</td>
                            <td class="px-4 py-3"><x-status-badge :status="$voucher->status" /></td>
                            <td class="px-4 py-3 text-red-400 text-xs">{{ $voucher->sync_error ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                @if ($voucher->status->value === 'FAILED')
                                    <form method="POST" action="{{ route('vouchers.retry', $voucher) }}">
                                        @csrf
                                        <button type="submit" class="text-sky-300 hover:underline text-xs">Retry</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-t border-matrix-border">
            {{ $vouchers->links() }}
        </div>
    </div>
</x-app-layout>
