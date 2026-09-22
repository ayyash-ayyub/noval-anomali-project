<x-app-layout>
    <x-slot name="header">PDF Export #{{ $export->id }}</x-slot>

    <div
        x-data="{
            status: '{{ $export->status->value }}',
            error: {{ Js::from($export->error) }},
            poll() {
                if (this.status !== 'PENDING' && this.status !== 'PROCESSING') return;
                fetch('{{ route('vouchers.print.progress', $export) }}')
                    .then(r => r.json())
                    .then(data => {
                        this.status = data.status;
                        this.error = data.error;
                        if (this.status === 'PENDING' || this.status === 'PROCESSING') {
                            setTimeout(() => this.poll(), 2000);
                        }
                    });
            }
        }"
        x-init="poll()"
        class="max-w-xl"
    >
        <div class="bg-matrix-panel border border-matrix-border rounded-md p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-[11px] uppercase tracking-widest text-matrix-green-deep">
                        {{ $export->voucher_count }} voucher &middot; {{ $export->paper_size->label() }}
                    </p>
                    @if ($export->batch)
                        <a href="{{ route('vouchers.batches.show', $export->batch) }}" class="text-matrix-green hover:underline font-mono text-sm">
                            {{ $export->batch->batch_code }}
                        </a>
                    @endif
                </div>
                <template x-if="status === 'PENDING' || status === 'PROCESSING'">
                    <span class="flex items-center gap-2 text-xs text-matrix-green-dim">
                        <span class="w-2 h-2 rounded-full bg-matrix-green animate-pulse"></span>
                        <span x-text="status === 'PENDING' ? 'Menunggu worker...' : 'Sedang dibuat...'"></span>
                    </span>
                </template>
                <template x-if="status === 'READY'">
                    <x-status-badge :status="\App\Enums\PdfExportStatus::Ready" />
                </template>
                <template x-if="status === 'FAILED'">
                    <x-status-badge :status="\App\Enums\PdfExportStatus::Failed" />
                </template>
            </div>

            <div x-show="status === 'READY'" x-cloak class="mt-4">
                <a href="{{ route('vouchers.print.download', $export) }}">
                    <x-primary-button type="button">Download PDF</x-primary-button>
                </a>
            </div>

            <div x-show="status === 'FAILED'" x-cloak class="mt-4 px-4 py-2.5 rounded border border-red-500/40 bg-red-500/10 text-red-400 text-sm">
                &gt; Gagal membuat PDF: <span x-text="error"></span>
            </div>
        </div>

        <a href="{{ route('vouchers.print') }}" class="inline-block mt-4 text-xs text-matrix-green-dim hover:text-matrix-green">
            &larr; Kembali ke riwayat
        </a>
    </div>
</x-app-layout>
