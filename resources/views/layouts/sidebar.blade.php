<aside
    x-cloak
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    class="fixed inset-y-0 left-0 z-40 w-64 bg-matrix-surface border-r border-matrix-border transform transition-transform duration-200 ease-in-out lg:translate-x-0 lg:static lg:inset-auto flex flex-col"
>
    <a href="{{ route('dashboard') }}" class="h-16 flex items-center gap-2.5 px-4 border-b border-matrix-border shrink-0">
        <img
            src="{{ asset('images/logo.jpeg') }}"
            alt="Noval Anomali"
            class="w-9 h-9 rounded-md object-cover shadow-matrix-glow shrink-0"
        >
        <span class="text-xs text-matrix-green-dim leading-tight">
            <span class="block text-matrix-green font-semibold tracking-wide">Noval Anomali</span>
            Voucher System
        </span>
    </a>

    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1 text-sm">
        <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
            <span>&#9635;</span> Dashboard
        </x-sidebar-link>

        <p class="px-3 pt-4 pb-1 text-[10px] uppercase tracking-widest text-matrix-green-deep">MikroTik</p>
        <x-sidebar-link :href="route('mikrotiks.index')" :active="request()->routeIs('mikrotiks.*')">
            <span>&#9711;</span> MikroTik
        </x-sidebar-link>
        <x-sidebar-link :href="route('hotspot.profiles')" :active="request()->routeIs('hotspot.profiles')">
            <span>&#9673;</span> HotSpot Profiles
        </x-sidebar-link>
        <x-sidebar-link :href="route('hotspot.users')" :active="request()->routeIs('hotspot.users')">
            <span>&#9673;</span> HotSpot Users
        </x-sidebar-link>
        <x-sidebar-link :href="route('hotspot.active')" :active="request()->routeIs('hotspot.active')">
            <span>&#9889;</span> Active Users
        </x-sidebar-link>

        <p class="px-3 pt-4 pb-1 text-[10px] uppercase tracking-widest text-matrix-green-deep">Voucher</p>
        <x-sidebar-link :href="route('vouchers.index')" :active="request()->routeIs('vouchers.index')">
            <span>&#9776;</span> Voucher List
        </x-sidebar-link>
        <x-sidebar-link :href="route('vouchers.generate')" :active="request()->routeIs('vouchers.generate')">
            <span>&#43;</span> Generate Voucher
        </x-sidebar-link>
        <x-sidebar-link :href="route('vouchers.batches')" :active="request()->routeIs('vouchers.batches*')">
            <span>&#9635;</span> Batches
        </x-sidebar-link>
        <x-sidebar-link :href="route('vouchers.print')" :active="request()->routeIs('vouchers.print')">
            <span>&#9113;</span> Print Voucher
        </x-sidebar-link>

        <p class="px-3 pt-4 pb-1 text-[10px] uppercase tracking-widest text-matrix-green-deep">Insight</p>
        <x-sidebar-link :href="route('reports.index')" :active="request()->routeIs('reports.index')">
            <span>&#9776;</span> Reports
        </x-sidebar-link>

        @if(auth()->user()->isAdmin())
            <x-sidebar-link :href="route('audit-logs.index')" :active="request()->routeIs('audit-logs.index')">
                <span>&#128274;</span> Audit Log
            </x-sidebar-link>
            <x-sidebar-link :href="route('failed-jobs.index')" :active="request()->routeIs('failed-jobs.*')">
                <span>&#9888;</span> Failed Jobs
            </x-sidebar-link>
            <x-sidebar-link :href="route('settings.index')" :active="request()->routeIs('settings.index')">
                <span>&#9881;</span> Settings
            </x-sidebar-link>
        @endif
    </nav>

    <div class="border-t border-matrix-border p-3 text-[11px] text-matrix-green-deep shrink-0">
        v0.1 &mdash; Foundation Phase
    </div>
</aside>
