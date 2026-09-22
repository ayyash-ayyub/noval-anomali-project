<x-app-layout>
    <x-slot name="header">Active HotSpot Users</x-slot>

    @if ($mikrotiks->isEmpty())
        @include('hotspot.partials.no-mikrotiks')
    @else
        <div class="mb-6">
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-4">
                <x-stat-card label="Total Active Users" :value="count($sessions)" accent="green" />
                <x-stat-card label="Router Online" :value="$mikrotiks->count() - count($routerErrors)" accent="green" />
                <x-stat-card label="Router Bermasalah" :value="count($routerErrors)" accent="red" />
            </div>

            @if ($perMikrotik->isNotEmpty())
                <div class="flex flex-wrap gap-2 text-xs">
                    @foreach ($mikrotiks as $mikrotik)
                        @if ($perMikrotik->has($mikrotik->id))
                            <span class="px-2.5 py-1 rounded border border-matrix-border text-matrix-green-dim">
                                {{ $mikrotik->name }}: <span class="text-matrix-green font-semibold">{{ $perMikrotik->get($mikrotik->id) }}</span>
                            </span>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>

        @foreach ($routerErrors as $mikrotikId => $message)
            <div class="mb-3 px-4 py-2.5 rounded border border-red-500/40 bg-red-500/10 text-red-400 text-sm">
                &gt; {{ $mikrotiks->firstWhere('id', $mikrotikId)?->name }}: {{ $message }}
            </div>
        @endforeach

        <div class="bg-matrix-panel border border-matrix-border rounded-md overflow-hidden">
            @if (empty($sessions))
                <div class="text-center py-16">
                    <p class="text-matrix-green-dim text-sm">Tidak ada sesi HotSpot yang sedang aktif.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-matrix-border text-left text-[11px] uppercase tracking-widest text-matrix-green-deep">
                                <th class="px-4 py-3">Username</th>
                                <th class="px-4 py-3">Router</th>
                                <th class="px-4 py-3">IP Address</th>
                                <th class="px-4 py-3">MAC Address</th>
                                <th class="px-4 py-3">Uptime</th>
                                <th class="px-4 py-3">Idle Time</th>
                                <th class="px-4 py-3">Login Method</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-matrix-border/60">
                            @foreach ($sessions as $session)
                                @php $row = $session['row']; @endphp
                                <tr class="hover:bg-matrix-green/5">
                                    <td class="px-4 py-3 text-matrix-green font-medium">{{ $row['user'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-matrix-green-dim">{{ $session['mikrotik']->name }}</td>
                                    <td class="px-4 py-3 text-matrix-green-dim">{{ $row['address'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-matrix-green-dim">{{ $row['mac-address'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-matrix-green-dim">{{ $row['uptime'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-matrix-green-dim">{{ $row['idle-time'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-matrix-green-dim">{{ $row['login-by'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif
</x-app-layout>
