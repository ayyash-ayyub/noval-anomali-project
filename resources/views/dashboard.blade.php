<x-app-layout>
    <x-slot name="header">Dashboard</x-slot>

    <div class="mb-6">
        <p class="text-sm text-matrix-green-dim">
            &gt; welcome back, <span class="text-matrix-green">{{ auth()->user()->name }}</span>.
            <span class="inline-block w-2 h-4 align-middle bg-matrix-green animate-blink-cursor"></span>
        </p>
    </div>

    <div class="mb-8">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-xs uppercase tracking-widest text-matrix-green-deep">MikroTik</h3>
            <a href="{{ route('mikrotiks.index') }}" class="text-[11px] text-matrix-green-dim hover:text-matrix-green">Kelola &rarr;</a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
            <x-stat-card label="Total MikroTik" :value="$stats['mikrotik_total']" accent="green" />
            <x-stat-card label="Online" :value="$stats['mikrotik_online']" accent="green" />
            <x-stat-card label="Offline" :value="$stats['mikrotik_offline']" accent="red" />
        </div>
    </div>

    <div class="mb-8">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-xs uppercase tracking-widest text-matrix-green-deep">Voucher</h3>
            <a href="{{ route('vouchers.index') }}" class="text-[11px] text-matrix-green-dim hover:text-matrix-green">Kelola &rarr;</a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
            <x-stat-card label="Total Voucher" :value="$stats['voucher_total']" accent="green" />
            <x-stat-card label="Available" :value="$stats['voucher_available']" accent="blue" />
            <x-stat-card label="Active" :value="$stats['voucher_active']" accent="green" />
            <x-stat-card label="Used" :value="$stats['voucher_used']" accent="gray" />
            <x-stat-card label="Expired" :value="$stats['voucher_expired']" accent="yellow" />
            <x-stat-card label="Failed" :value="$stats['voucher_failed']" accent="red" />
            <x-stat-card label="Generated Today" :value="$stats['voucher_generated_today']" accent="green" />
            <x-stat-card label="Used Today" :value="$stats['voucher_used_today']" accent="blue" />
        </div>
    </div>

    <div class="mb-8">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-xs uppercase tracking-widest text-matrix-green-deep">HotSpot</h3>
            <a href="{{ route('hotspot.active') }}" class="text-[11px] text-matrix-green-dim hover:text-matrix-green">Detail &rarr;</a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
            <x-stat-card label="Active Users" :value="$stats['active_users']" accent="green" />
        </div>
        <p class="mt-2 text-[11px] text-matrix-green-deep">
            @if ($activeUsersSyncedAt)
                Disinkronkan {{ $activeUsersSyncedAt->diffForHumans() }}
            @else
                Belum pernah disinkronkan &mdash; menunggu scheduler berjalan.
            @endif
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-matrix-panel border border-matrix-border rounded-md p-4">
            <h3 class="text-xs uppercase tracking-widest text-matrix-green-deep mb-3">Recent Batch</h3>
            @if ($recentBatches->isEmpty())
                <p class="text-sm text-matrix-green-deep italic">Belum ada batch voucher.</p>
            @else
                <ul class="space-y-2 text-sm">
                    @foreach ($recentBatches as $batch)
                        <li class="flex items-center justify-between gap-3">
                            <div>
                                <a href="{{ route('vouchers.batches.show', $batch) }}" class="text-matrix-green hover:underline font-mono">{{ $batch->batch_code }}</a>
                                <p class="text-matrix-green-deep text-xs">{{ $batch->mikrotik->name }} &middot; {{ $batch->quantity }} voucher</p>
                            </div>
                            <x-status-badge :status="$batch->status" />
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
        <div class="bg-matrix-panel border border-matrix-border rounded-md p-4">
            <h3 class="text-xs uppercase tracking-widest text-matrix-green-deep mb-3">Recent Errors</h3>
            @if ($recentErrors->isEmpty())
                <p class="text-sm text-matrix-green-deep italic">Tidak ada error MikroTik saat ini.</p>
            @else
                <ul class="space-y-2 text-sm">
                    @foreach ($recentErrors as $router)
                        <li class="flex items-start justify-between gap-3">
                            <div>
                                <a href="{{ route('mikrotiks.show', $router) }}" class="text-matrix-green hover:underline">{{ $router->name }}</a>
                                <p class="text-red-400 text-xs">{{ $router->last_error }}</p>
                            </div>
                            <span class="text-[10px] text-matrix-green-deep whitespace-nowrap">{{ $router->last_check_at?->diffForHumans() }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-app-layout>
