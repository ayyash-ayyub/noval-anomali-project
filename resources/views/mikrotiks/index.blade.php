<x-app-layout>
    <x-slot name="header">MikroTik Management</x-slot>

    <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-matrix-green-dim">
            {{ $mikrotiks->total() }} router terdaftar
        </p>

        @can('create', \App\Models\Mikrotik::class)
            <a href="{{ route('mikrotiks.create') }}"
                class="inline-flex items-center gap-1.5 px-4 py-2 border border-matrix-green text-matrix-green text-xs uppercase tracking-widest rounded hover:bg-matrix-green hover:text-black transition-colors shadow-matrix-glow">
                <span>&#43;</span> Tambah MikroTik
            </a>
        @endcan
    </div>

    <div class="bg-matrix-panel border border-matrix-border rounded-md overflow-hidden">
        @if ($mikrotiks->isEmpty())
            <div class="text-center py-16">
                <p class="text-matrix-green-dim text-sm mb-1">Belum ada MikroTik terdaftar.</p>
                <p class="text-matrix-green-deep text-xs">Tambahkan router pertama untuk mulai memonitor status &amp; mengelola voucher.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-matrix-border text-left text-[11px] uppercase tracking-widest text-matrix-green-deep">
                            <th class="px-4 py-3">Nama</th>
                            <th class="px-4 py-3">Host</th>
                            <th class="px-4 py-3">Tipe</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Response</th>
                            <th class="px-4 py-3">Last Check</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-matrix-border/60">
                        @foreach ($mikrotiks as $mikrotik)
                            <tr class="hover:bg-matrix-green/5">
                                <td class="px-4 py-3">
                                    <a href="{{ route('mikrotiks.show', $mikrotik) }}" class="text-matrix-green hover:underline font-medium">
                                        {{ $mikrotik->name }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-matrix-green-dim">{{ $mikrotik->host }}:{{ $mikrotik->port }}</td>
                                <td class="px-4 py-3 text-matrix-green-dim">
                                    {{ $mikrotik->api_type->label() }}
                                    @if ($mikrotik->ssl_enabled)
                                        <span class="text-[10px] text-matrix-green-deep">(SSL)</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3"><x-status-badge :status="$mikrotik->status" /></td>
                                <td class="px-4 py-3 text-matrix-green-dim">
                                    {{ $mikrotik->last_response_time !== null ? $mikrotik->last_response_time . ' ms' : '—' }}
                                </td>
                                <td class="px-4 py-3 text-matrix-green-dim">
                                    {{ $mikrotik->last_check_at?->diffForHumans() ?? 'Belum pernah' }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-3 text-xs">
                                        <form method="POST" action="{{ route('mikrotiks.test', $mikrotik) }}">
                                            @csrf
                                            <button type="submit" class="text-sky-300 hover:underline">Test</button>
                                        </form>
                                        <a href="{{ route('mikrotiks.show', $mikrotik) }}" class="text-matrix-green-dim hover:text-matrix-green">Detail</a>
                                        @can('update', $mikrotik)
                                            <a href="{{ route('mikrotiks.edit', $mikrotik) }}" class="text-matrix-green-dim hover:text-matrix-green">Edit</a>
                                        @endcan
                                        @can('delete', $mikrotik)
                                            <form method="POST" action="{{ route('mikrotiks.destroy', $mikrotik) }}"
                                                onsubmit="return confirm('Hapus MikroTik {{ $mikrotik->name }}? Tindakan ini tidak dapat dibatalkan.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-400 hover:underline">Hapus</button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3 border-t border-matrix-border">
                {{ $mikrotiks->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
