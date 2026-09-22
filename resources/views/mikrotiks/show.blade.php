<x-app-layout>
    <x-slot name="header">{{ $mikrotik->name }}</x-slot>

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="flex items-center gap-3">
            <x-status-badge :status="$mikrotik->status" />
            <span class="text-sm text-matrix-green-dim">{{ $mikrotik->host }}:{{ $mikrotik->port }}</span>
            <span class="text-[11px] text-matrix-green-deep uppercase tracking-widest">
                {{ $mikrotik->api_type->label() }}{{ $mikrotik->ssl_enabled ? ' · SSL' : '' }}
            </span>
        </div>

        <div class="flex items-center gap-2">
            <form method="POST" action="{{ route('mikrotiks.test', $mikrotik) }}">
                @csrf
                <x-secondary-button type="submit">Test Connection</x-secondary-button>
            </form>

            <form method="POST" action="{{ route('mikrotiks.router-info', $mikrotik) }}">
                @csrf
                <x-secondary-button type="submit">Router Info</x-secondary-button>
            </form>

            <a href="{{ route('mikrotiks.sync', $mikrotik) }}">
                <x-secondary-button type="button">Sync</x-secondary-button>
            </a>

            @can('update', $mikrotik)
                <a href="{{ route('mikrotiks.edit', $mikrotik) }}">
                    <x-secondary-button type="button">Edit</x-secondary-button>
                </a>
            @endcan
        </div>
    </div>

    @if (session('router_info'))
        @php $info = session('router_info'); @endphp
        <div class="mb-6 bg-matrix-panel border border-matrix-border rounded-md p-4">
            <h3 class="text-xs uppercase tracking-widest text-matrix-green-deep mb-3">Router Information</h3>
            <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                <div>
                    <dt class="text-[10px] uppercase text-matrix-green-deep">Identity</dt>
                    <dd class="text-matrix-green">{{ $info['identity'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] uppercase text-matrix-green-deep">Board</dt>
                    <dd class="text-matrix-green">{{ $info['board_name'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] uppercase text-matrix-green-deep">RouterOS Version</dt>
                    <dd class="text-matrix-green">{{ $info['version'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] uppercase text-matrix-green-deep">Uptime</dt>
                    <dd class="text-matrix-green">{{ $info['uptime'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] uppercase text-matrix-green-deep">CPU Load</dt>
                    <dd class="text-matrix-green">{{ $info['cpu_load'] ?? '—' }}%</dd>
                </div>
                <div>
                    <dt class="text-[10px] uppercase text-matrix-green-deep">Free Memory</dt>
                    <dd class="text-matrix-green">{{ $info['free_memory'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] uppercase text-matrix-green-deep">Free HDD Space</dt>
                    <dd class="text-matrix-green">{{ $info['free_hdd_space'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] uppercase text-matrix-green-deep">Architecture</dt>
                    <dd class="text-matrix-green">{{ $info['architecture'] ?? '—' }}</dd>
                </div>
            </dl>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 bg-matrix-panel border border-matrix-border rounded-md p-4">
            <h3 class="text-xs uppercase tracking-widest text-matrix-green-deep mb-3">Detail</h3>
            <dl class="grid grid-cols-2 gap-y-3 text-sm">
                <dt class="text-matrix-green-deep">Username</dt>
                <dd class="text-matrix-green">{{ $mikrotik->username }}</dd>

                <dt class="text-matrix-green-deep">Password</dt>
                <dd class="text-matrix-green">••••••••</dd>

                <dt class="text-matrix-green-deep">Deskripsi</dt>
                <dd class="text-matrix-green">{{ $mikrotik->description ?: '—' }}</dd>

                <dt class="text-matrix-green-deep">Terdaftar</dt>
                <dd class="text-matrix-green">{{ $mikrotik->created_at->format('Y-m-d H:i') }}</dd>
            </dl>
        </div>

        <div class="bg-matrix-panel border border-matrix-border rounded-md p-4">
            <h3 class="text-xs uppercase tracking-widest text-matrix-green-deep mb-3">Health Check</h3>
            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-[10px] uppercase text-matrix-green-deep">Last Check</dt>
                    <dd class="text-matrix-green">{{ $mikrotik->last_check_at?->format('Y-m-d H:i:s') ?? 'Belum pernah' }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] uppercase text-matrix-green-deep">Last Online</dt>
                    <dd class="text-matrix-green">{{ $mikrotik->last_online_at?->format('Y-m-d H:i:s') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] uppercase text-matrix-green-deep">Last Offline</dt>
                    <dd class="text-matrix-green">{{ $mikrotik->last_offline_at?->format('Y-m-d H:i:s') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] uppercase text-matrix-green-deep">Response Time</dt>
                    <dd class="text-matrix-green">{{ $mikrotik->last_response_time !== null ? $mikrotik->last_response_time . ' ms' : '—' }}</dd>
                </div>
                @if ($mikrotik->last_error)
                    <div>
                        <dt class="text-[10px] uppercase text-red-400">Last Error</dt>
                        <dd class="text-red-400 break-words">{{ $mikrotik->last_error }}</dd>
                    </div>
                @endif
            </dl>
        </div>
    </div>

    @can('delete', $mikrotik)
        <div class="mt-6 bg-matrix-panel border border-red-500/30 rounded-md p-4 flex items-center justify-between">
            <p class="text-sm text-matrix-green-dim">Menghapus router ini juga akan memutus keterkaitan data yang mereferensikannya.</p>
            <form method="POST" action="{{ route('mikrotiks.destroy', $mikrotik) }}"
                onsubmit="return confirm('Hapus MikroTik {{ $mikrotik->name }}? Tindakan ini tidak dapat dibatalkan.');">
                @csrf
                @method('DELETE')
                <x-danger-button type="submit">Hapus MikroTik</x-danger-button>
            </form>
        </div>
    @endcan
</x-app-layout>
