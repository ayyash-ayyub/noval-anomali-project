<x-app-layout>
    <x-slot name="header">Dashboard</x-slot>

    <div class="-m-4 sm:-m-6 min-h-screen bg-white p-4 sm:p-6">
        <div class="mb-6">
            <p class="text-sm text-gray-500">
                Selamat datang kembali, <span class="font-medium text-gray-900">{{ auth()->user()->name }}</span>.
            </p>
        </div>

        <div class="mb-8">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-xs uppercase tracking-widest text-gray-400">MikroTik</h3>
                <a href="{{ route('mikrotiks.index') }}" class="text-[11px] text-gray-500 hover:text-gray-900">Kelola &rarr;</a>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                <x-stat-card theme="light" label="Total MikroTik" :value="$stats['mikrotik_total']" accent="gray" />
                <x-stat-card theme="light" label="Online" :value="$stats['mikrotik_online']" accent="green" />
                <x-stat-card theme="light" label="Offline" :value="$stats['mikrotik_offline']" accent="red" />
            </div>
        </div>

        <div class="mb-8">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-xs uppercase tracking-widest text-gray-400">Voucher</h3>
                <a href="{{ route('vouchers.index') }}" class="text-[11px] text-gray-500 hover:text-gray-900">Kelola &rarr;</a>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                <x-stat-card theme="light" label="Total Voucher" :value="$stats['voucher_total']" accent="gray" />
                <x-stat-card theme="light" label="Available" :value="$stats['voucher_available']" accent="blue" />
                <x-stat-card theme="light" label="Active" :value="$stats['voucher_active']" accent="green" />
                <x-stat-card theme="light" label="Used" :value="$stats['voucher_used']" accent="gray" />
                <x-stat-card theme="light" label="Expired" :value="$stats['voucher_expired']" accent="yellow" />
                <x-stat-card theme="light" label="Failed" :value="$stats['voucher_failed']" accent="red" />
                <x-stat-card theme="light" label="Generated Today" :value="$stats['voucher_generated_today']" accent="blue" />
                <x-stat-card theme="light" label="Used Today" :value="$stats['voucher_used_today']" accent="blue" />
            </div>
        </div>

        <div class="mb-8">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-xs uppercase tracking-widest text-gray-400">HotSpot</h3>
                <a href="{{ route('hotspot.active') }}" class="text-[11px] text-gray-500 hover:text-gray-900">Detail &rarr;</a>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                <x-stat-card theme="light" label="Active Users" :value="$stats['active_users']" accent="green" />
            </div>
            <p class="mt-2 text-[11px] text-gray-400">
                @if ($activeUsersSyncedAt)
                    Disinkronkan {{ $activeUsersSyncedAt->diffForHumans() }}
                @else
                    Belum pernah disinkronkan &mdash; menunggu scheduler berjalan.
                @endif
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <h3 class="text-xs uppercase tracking-widest text-gray-400 mb-3">Recent Batch</h3>
                @if ($recentBatches->isEmpty())
                    <p class="text-sm text-gray-400 italic">Belum ada batch voucher.</p>
                @else
                    <ul class="space-y-2 text-sm">
                        @foreach ($recentBatches as $batch)
                            <li class="flex items-center justify-between gap-3">
                                <div>
                                    <a href="{{ route('vouchers.batches.show', $batch) }}" class="text-gray-900 hover:text-blue-600 hover:underline font-mono">{{ $batch->batch_code }}</a>
                                    <p class="text-gray-400 text-xs">{{ $batch->mikrotik->name }} &middot; {{ $batch->quantity }} voucher</p>
                                </div>
                                <x-status-badge theme="light" :status="$batch->status" />
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <h3 class="text-xs uppercase tracking-widest text-gray-400 mb-3">Recent Errors</h3>
                @if ($recentErrors->isEmpty())
                    <p class="text-sm text-gray-400 italic">Tidak ada error MikroTik saat ini.</p>
                @else
                    <ul class="space-y-2 text-sm">
                        @foreach ($recentErrors as $router)
                            <li class="flex items-start justify-between gap-3">
                                <div>
                                    <a href="{{ route('mikrotiks.show', $router) }}" class="text-gray-900 hover:text-blue-600 hover:underline">{{ $router->name }}</a>
                                    <p class="text-red-600 text-xs">{{ $router->last_error }}</p>
                                </div>
                                <span class="text-[10px] text-gray-400 whitespace-nowrap">{{ $router->last_check_at?->diffForHumans() }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
