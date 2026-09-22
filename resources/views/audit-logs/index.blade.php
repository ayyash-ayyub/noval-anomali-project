<x-app-layout>
    <x-slot name="header">Audit Log</x-slot>

    <div class="bg-matrix-panel border border-matrix-border rounded-md overflow-hidden">
        @if ($logs->isEmpty())
            <div class="text-center py-16">
                <p class="text-matrix-green-dim text-sm">Belum ada aktivitas tercatat.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-matrix-border text-left text-[11px] uppercase tracking-widest text-matrix-green-deep">
                            <th class="px-4 py-3">Waktu</th>
                            <th class="px-4 py-3">User</th>
                            <th class="px-4 py-3">Aksi</th>
                            <th class="px-4 py-3">MikroTik</th>
                            <th class="px-4 py-3">Hasil</th>
                            <th class="px-4 py-3">Deskripsi</th>
                            <th class="px-4 py-3">IP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-matrix-border/60">
                        @foreach ($logs as $log)
                            <tr class="hover:bg-matrix-green/5 align-top">
                                <td class="px-4 py-3 text-matrix-green-dim whitespace-nowrap">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                <td class="px-4 py-3 text-matrix-green">{{ $log->user?->name ?? 'System' }}</td>
                                <td class="px-4 py-3 text-matrix-green-dim font-mono text-xs">{{ $log->action }}</td>
                                <td class="px-4 py-3 text-matrix-green-dim">{{ $log->mikrotik?->name ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="text-[11px] uppercase tracking-wider px-2 py-0.5 rounded border
                                        {{ $log->result === 'success' ? 'border-matrix-green/50 text-matrix-green bg-matrix-green/10' : 'border-red-500/50 text-red-400 bg-red-500/10' }}">
                                        {{ $log->result }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-matrix-green-dim max-w-md">{{ $log->description ?? '—' }}</td>
                                <td class="px-4 py-3 text-matrix-green-deep whitespace-nowrap">{{ $log->ip_address ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3 border-t border-matrix-border">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
