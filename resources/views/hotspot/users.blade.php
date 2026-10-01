<x-app-layout>
    <x-slot name="header">HotSpot Users</x-slot>

    @if ($mikrotiks->isEmpty())
        @include('hotspot.partials.no-mikrotiks')
    @else
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-matrix-green-dim">Daftar HotSpot user yang tersimpan langsung pada router.</p>
            @include('hotspot.partials.mikrotik-selector')
        </div>

        @if ($error)
            <div class="mb-4 px-4 py-2.5 rounded border border-red-500/40 bg-red-500/10 text-red-400 text-sm">
                &gt; Gagal membaca user dari '{{ $selected->name }}': {{ $error }}
            </div>
        @endif

        <div class="bg-matrix-panel border border-matrix-border rounded-md overflow-hidden">
            @if (! $error && $users->isEmpty())
                <div class="text-center py-16">
                    <p class="text-matrix-green-dim text-sm">Tidak ada HotSpot user pada router ini.</p>
                </div>
            @elseif ($users->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-matrix-border text-left text-[11px] uppercase tracking-widest text-matrix-green-deep">
                                <th class="px-4 py-3">Username</th>
                                <th class="px-4 py-3">Password</th>
                                <th class="px-4 py-3">Profile</th>
                                <th class="px-4 py-3">Limit Uptime</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Komentar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-matrix-border/60">
                            @foreach ($users as $user)
                                <tr class="hover:bg-matrix-green/5">
                                    <td class="px-4 py-3 text-matrix-green font-medium">{{ $user['name'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-matrix-green-dim font-mono">{{ $user['password'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-matrix-green-dim">{{ $user['profile'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-matrix-green-dim">{{ $user['limit-uptime'] ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        @if (($user['disabled'] ?? 'false') === 'true')
                                            <span class="text-[11px] uppercase tracking-wider px-2 py-0.5 rounded border border-gray-500/40 text-gray-400 bg-gray-500/10">Disabled</span>
                                        @else
                                            <span class="text-[11px] uppercase tracking-wider px-2 py-0.5 rounded border border-matrix-green/50 text-matrix-green bg-matrix-green/10">Active</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-matrix-green-deep">{{ $user['comment'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-4 py-3 border-t border-matrix-border">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    @endif
</x-app-layout>
