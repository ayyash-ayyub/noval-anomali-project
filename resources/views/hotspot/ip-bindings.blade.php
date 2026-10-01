<x-app-layout>
    <x-slot name="header">IP Bindings</x-slot>

    @if ($mikrotiks->isEmpty())
        @include('hotspot.partials.no-mikrotiks')
    @else
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <p class="text-sm text-matrix-green-dim">IP Binding dibaca langsung dari router (tidak disimpan lokal).</p>
            <div class="flex items-center gap-3">
                @include('hotspot.partials.mikrotik-selector')
                @can('createIpBinding', \App\Models\Mikrotik::class)
                    <a href="{{ route('hotspot.ip-bindings.create', $selected ? ['mikrotik' => $selected->id] : []) }}"
                        class="inline-flex items-center gap-1.5 px-4 py-2 border border-matrix-green text-matrix-green text-xs uppercase tracking-widest rounded hover:bg-matrix-green hover:text-black transition-colors shadow-matrix-glow">
                        <span>&#43;</span> Add IP Binding
                    </a>
                @endcan
            </div>
        </div>

        @if ($error)
            <div class="mb-4 px-4 py-2.5 rounded border border-red-500/40 bg-red-500/10 text-red-400 text-sm">
                &gt; Gagal membaca IP binding dari '{{ $selected->name }}': {{ $error }}
            </div>
        @endif

        <div class="bg-matrix-panel border border-matrix-border rounded-md overflow-hidden">
            @if (! $error && $bindings->isEmpty())
                <div class="text-center py-16">
                    <p class="text-matrix-green-dim text-sm">Belum ada IP binding pada router ini.</p>
                </div>
            @elseif ($bindings->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-matrix-border text-left text-[11px] uppercase tracking-widest text-matrix-green-deep">
                                <th class="px-4 py-3">Nama</th>
                                <th class="px-4 py-3">MAC Address</th>
                                <th class="px-4 py-3">Address</th>
                                <th class="px-4 py-3">To Address</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-matrix-border/60">
                            @foreach ($bindings as $binding)
                                <tr class="hover:bg-matrix-green/5">
                                    <td class="px-4 py-3 text-matrix-green font-medium">{{ $binding['comment'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-matrix-green-dim font-mono">{{ $binding['mac-address'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-matrix-green-dim">{{ $binding['address'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-matrix-green-dim">{{ $binding['to-address'] ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        @php
                                            $type = $binding['type'] ?? 'regular';
                                            $typeColors = [
                                                'bypassed' => 'border-matrix-green/50 text-matrix-green bg-matrix-green/10',
                                                'blocked' => 'border-red-500/50 text-red-400 bg-red-500/10',
                                                'regular' => 'border-gray-500/40 text-gray-400 bg-gray-500/10',
                                            ];
                                        @endphp
                                        <span class="text-[11px] uppercase tracking-wider px-2 py-0.5 rounded border {{ $typeColors[$type] ?? $typeColors['regular'] }}">
                                            {{ $type }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if (($binding['disabled'] ?? 'false') === 'true')
                                            <span class="text-[11px] uppercase tracking-wider px-2 py-0.5 rounded border border-gray-500/40 text-gray-400 bg-gray-500/10">Disabled</span>
                                        @else
                                            <span class="text-[11px] uppercase tracking-wider px-2 py-0.5 rounded border border-matrix-green/50 text-matrix-green bg-matrix-green/10">Active</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-4 py-3 border-t border-matrix-border">
                    {{ $bindings->links() }}
                </div>
            @endif
        </div>
    @endif
</x-app-layout>
