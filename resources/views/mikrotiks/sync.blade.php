<x-app-layout>
    <x-slot name="header">Sync — {{ $mikrotik->name }}</x-slot>

    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-matrix-green-dim">
            Membandingkan voucher di Laravel dengan HotSpot user yang benar-benar ada di router.
            Tidak ada data yang dihapus otomatis — tinjau dan tindak lanjuti secara manual.
        </p>
        <a href="{{ route('mikrotiks.sync', $mikrotik) }}">
            <x-secondary-button type="button">Refresh</x-secondary-button>
        </a>
    </div>

    @if ($error)
        <div class="mb-6 px-4 py-2.5 rounded border border-red-500/40 bg-red-500/10 text-red-400 text-sm">
            &gt; Gagal membaca data dari '{{ $mikrotik->name }}': {{ $error }}
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
            <x-stat-card label="Cocok" :value="$matchedCount" accent="green" />
            <x-stat-card label="Hanya di Laravel" :value="$laravelOnly->count()" accent="yellow" />
            <x-stat-card label="Hanya di MikroTik" :value="$mikrotikOnly->count()" accent="blue" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="bg-matrix-panel border border-matrix-border rounded-md overflow-hidden">
                <div class="px-4 py-3 border-b border-matrix-border">
                    <h3 class="text-xs uppercase tracking-widest text-yellow-300">Hanya di Laravel</h3>
                    <p class="text-[11px] text-matrix-green-deep mt-1">
                        Voucher berstatus tersinkronisasi di database, tapi username-nya tidak ditemukan di router.
                        Mungkin terhapus manual dari MikroTik.
                    </p>
                </div>
                @if ($laravelOnly->isEmpty())
                    <p class="text-sm text-matrix-green-deep italic text-center py-10">Tidak ada discrepancy.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-matrix-border text-left text-[11px] uppercase tracking-widest text-matrix-green-deep">
                                    <th class="px-4 py-2">Username</th>
                                    <th class="px-4 py-2">Status</th>
                                    <th class="px-4 py-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-matrix-border/60">
                                @foreach ($laravelOnly as $voucher)
                                    <tr class="hover:bg-matrix-green/5">
                                        <td class="px-4 py-2 text-matrix-green font-mono">{{ $voucher->username }}</td>
                                        <td class="px-4 py-2"><x-status-badge :status="$voucher->status" /></td>
                                        <td class="px-4 py-2 text-right">
                                            <a href="{{ route('vouchers.show', $voucher) }}" class="text-matrix-green-dim hover:text-matrix-green text-xs">Detail &rarr;</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="bg-matrix-panel border border-matrix-border rounded-md overflow-hidden">
                <div class="px-4 py-3 border-b border-matrix-border">
                    <h3 class="text-xs uppercase tracking-widest text-sky-300">Hanya di MikroTik</h3>
                    <p class="text-[11px] text-matrix-green-deep mt-1">
                        User HotSpot ada di router tapi tidak punya voucher di Laravel — kemungkinan dibuat manual
                        langsung di router.
                    </p>
                </div>
                @if ($mikrotikOnly->isEmpty())
                    <p class="text-sm text-matrix-green-deep italic text-center py-10">Tidak ada discrepancy.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-matrix-border text-left text-[11px] uppercase tracking-widest text-matrix-green-deep">
                                    <th class="px-4 py-2">Username</th>
                                    <th class="px-4 py-2">Profile</th>
                                    <th class="px-4 py-2">Komentar</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-matrix-border/60">
                                @foreach ($mikrotikOnly as $user)
                                    <tr class="hover:bg-matrix-green/5">
                                        <td class="px-4 py-2 text-matrix-green font-mono">{{ $user['name'] ?? '—' }}</td>
                                        <td class="px-4 py-2 text-matrix-green-dim">{{ $user['profile'] ?? '—' }}</td>
                                        <td class="px-4 py-2 text-matrix-green-deep">{{ $user['comment'] ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @endif
</x-app-layout>
