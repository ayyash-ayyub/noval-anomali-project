<x-app-layout>
    <x-slot name="header">Voucher Batches</x-slot>

    <div class="flex items-center justify-end mb-4">
        <a href="{{ route('vouchers.generate') }}"
            class="inline-flex items-center gap-1.5 px-4 py-2 border border-matrix-green text-matrix-green text-xs uppercase tracking-widest rounded hover:bg-matrix-green hover:text-black transition-colors shadow-matrix-glow">
            <span>&#43;</span> Generate Voucher
        </a>
    </div>

    <div class="bg-matrix-panel border border-matrix-border rounded-md overflow-hidden">
        @if ($batches->isEmpty())
            <div class="text-center py-16">
                <p class="text-matrix-green-dim text-sm">Belum ada batch voucher.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-matrix-border text-left text-[11px] uppercase tracking-widest text-matrix-green-deep">
                            <th class="px-4 py-3">Batch Code</th>
                            <th class="px-4 py-3">Router</th>
                            <th class="px-4 py-3">Profile</th>
                            <th class="px-4 py-3">Quantity</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Dibuat Oleh</th>
                            <th class="px-4 py-3">Waktu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-matrix-border/60">
                        @foreach ($batches as $batch)
                            <tr class="hover:bg-matrix-green/5">
                                <td class="px-4 py-3">
                                    <a href="{{ route('vouchers.batches.show', $batch) }}" class="text-matrix-green hover:underline font-medium font-mono">
                                        {{ $batch->batch_code }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-matrix-green-dim">{{ $batch->mikrotik->name }}</td>
                                <td class="px-4 py-3 text-matrix-green-dim">{{ $batch->profile }}</td>
                                <td class="px-4 py-3 text-matrix-green-dim">{{ $batch->quantity }}</td>
                                <td class="px-4 py-3"><x-status-badge :status="$batch->status" /></td>
                                <td class="px-4 py-3 text-matrix-green-dim">{{ $batch->creator?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-matrix-green-deep">{{ $batch->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3 border-t border-matrix-border">
                {{ $batches->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
