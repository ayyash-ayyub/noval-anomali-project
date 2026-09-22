<header class="h-16 shrink-0 border-b border-matrix-border bg-matrix-surface flex items-center justify-between px-4 sm:px-6">
    <div class="flex items-center gap-3">
        <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-matrix-green-dim hover:text-matrix-green">
            <span class="text-xl leading-none">&#9776;</span>
        </button>

        <img
            src="{{ asset('images/logo.jpeg') }}"
            alt="Noval Anomali"
            class="w-8 h-8 rounded-md object-cover shadow-matrix-glow lg:hidden"
        >

        <div>
            <p class="text-[10px] uppercase tracking-widest text-matrix-green-deep">
                {{ now()->format('Y-m-d H:i') }}
            </p>
            <h2 class="text-sm sm:text-base font-semibold text-matrix-green">
                {{ $header ?? 'Dashboard' }}
            </h2>
        </div>
    </div>

    <x-dropdown align="right" width="56" contentClasses="py-1 bg-matrix-panel border border-matrix-border">
        <x-slot name="trigger">
            <button class="flex items-center gap-2 px-3 py-1.5 border border-matrix-border rounded text-matrix-green-dim hover:text-matrix-green hover:border-matrix-green/50 transition-colors text-sm">
                <span>{{ auth()->user()->name }}</span>
                <span class="text-[10px] px-1.5 py-0.5 rounded border border-matrix-green-dark text-matrix-green-dark uppercase">
                    {{ auth()->user()->role }}
                </span>
                <span>&#9662;</span>
            </button>
        </x-slot>

        <x-slot name="content">
            <x-dropdown-link :href="route('profile.edit')">
                {{ __('Profile') }}
            </x-dropdown-link>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-dropdown-link :href="route('logout')"
                        onclick="event.preventDefault(); this.closest('form').submit();">
                    {{ __('Log Out') }}
                </x-dropdown-link>
            </form>
        </x-slot>
    </x-dropdown>
</header>
