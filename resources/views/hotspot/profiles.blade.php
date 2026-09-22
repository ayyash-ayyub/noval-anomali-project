<x-app-layout>
    <x-slot name="header">HotSpot Profiles</x-slot>

    @if ($mikrotiks->isEmpty())
        @include('hotspot.partials.no-mikrotiks')
    @else
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-matrix-green-dim">Profile HotSpot dibaca langsung dari router (tidak disimpan lokal).</p>
            @include('hotspot.partials.mikrotik-selector')
        </div>

        @if ($error)
            <div class="mb-4 px-4 py-2.5 rounded border border-red-500/40 bg-red-500/10 text-red-400 text-sm">
                &gt; Gagal membaca profile dari '{{ $selected->name }}': {{ $error }}
            </div>
        @endif

        <div class="bg-matrix-panel border border-matrix-border rounded-md overflow-hidden">
            @if (! $error && empty($profiles))
                <div class="text-center py-16">
                    <p class="text-matrix-green-dim text-sm">Tidak ada HotSpot profile pada router ini.</p>
                </div>
            @elseif (! empty($profiles))
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-matrix-border text-left text-[11px] uppercase tracking-widest text-matrix-green-deep">
                                <th class="px-4 py-3">Nama</th>
                                <th class="px-4 py-3">Session Timeout</th>
                                <th class="px-4 py-3">Rate Limit</th>
                                <th class="px-4 py-3">Idle Timeout</th>
                                <th class="px-4 py-3">Shared Users</th>
                                <th class="px-4 py-3">Address Pool</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-matrix-border/60">
                            @foreach ($profiles as $profile)
                                <tr class="hover:bg-matrix-green/5">
                                    <td class="px-4 py-3 text-matrix-green font-medium">{{ $profile['name'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-matrix-green-dim">{{ $profile['session-timeout'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-matrix-green-dim">{{ $profile['rate-limit'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-matrix-green-dim">{{ $profile['idle-timeout'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-matrix-green-dim">{{ $profile['shared-users'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-matrix-green-dim">{{ $profile['address-pool'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif
</x-app-layout>
