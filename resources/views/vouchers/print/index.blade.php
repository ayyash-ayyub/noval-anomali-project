<x-app-layout>
    <x-slot name="header">Print Voucher</x-slot>

    <p class="text-sm text-matrix-green-dim mb-4">
        Riwayat PDF voucher yang pernah dibuat. Mulai proses baru lewat tombol "Print All" pada halaman batch,
        atau "Print Selected" pada daftar voucher.
    </p>

    <div class="bg-matrix-panel border border-matrix-border rounded-md overflow-hidden">
        @if ($exports->isEmpty())
            <div class="text-center py-16">
                <p class="text-matrix-green-dim text-sm mb-1">Belum ada PDF yang dibuat.</p>
                <a href="{{ route('vouchers.batches') }}" class="text-matrix-green text-xs hover:underline">Lihat Voucher Batches &rarr;</a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-matrix-border text-left text-[11px] uppercase tracking-widest text-matrix-green-deep">
                            <th class="px-4 py-3">ID</th>
                            <th class="px-4 py-3">Batch</th>
                            <th class="px-4 py-3">Jumlah</th>
                            <th class="px-4 py-3">Paper Size</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Diminta Oleh</th>
                            <th class="px-4 py-3">Waktu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-matrix-border/60">
                        @foreach ($exports as $export)
                            <tr class="hover:bg-matrix-green/5">
                                <td class="px-4 py-3">
                                    <a href="{{ route('vouchers.print.show', $export) }}" class="text-matrix-green hover:underline">#{{ $export->id }}</a>
                                </td>
                                <td class="px-4 py-3 text-matrix-green-dim">
                                    @if ($export->batch)
                                        <a href="{{ route('vouchers.batches.show', $export->batch) }}" class="hover:underline font-mono">{{ $export->batch->batch_code }}</a>
                                    @else
                                        <span class="text-matrix-green-deep">Voucher terpilih</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-matrix-green-dim">{{ $export->voucher_count }}</td>
                                <td class="px-4 py-3 text-matrix-green-dim">{{ $export->paper_size->label() }}</td>
                                <td class="px-4 py-3"><x-status-badge :status="$export->status" /></td>
                                <td class="px-4 py-3 text-matrix-green-dim">{{ $export->requester?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-matrix-green-deep">{{ $export->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3 border-t border-matrix-border">
                {{ $exports->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
