<x-guest-layout>
    <div class="mb-6 flex items-center gap-2 text-matrix-green-dim text-sm">
        <span class="text-matrix-green">&gt;</span>
        <span>login --secure</span>
        <span class="inline-block w-2 h-4 bg-matrix-green animate-blink-cursor"></span>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4 text-matrix-green" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Username -->
        <div>
            <label for="username" class="block text-xs uppercase tracking-widest text-matrix-green-dim mb-1">
                {{ __('Username') }}
            </label>
            <input
                id="username"
                name="username"
                type="text"
                value="{{ old('username') }}"
                required
                autofocus
                autocomplete="username"
                class="block w-full bg-black border border-matrix-border text-matrix-green placeholder-matrix-green-deep font-mono px-3 py-2 rounded focus:outline-none focus:ring-1 focus:ring-matrix-green focus:border-matrix-green"
                placeholder="adminanomali"
            />
            <x-input-error :messages="$errors->get('username')" class="mt-2 text-red-400" />
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-xs uppercase tracking-widest text-matrix-green-dim mb-1">
                {{ __('Password') }}
            </label>
            <input
                id="password"
                name="password"
                type="password"
                required
                autocomplete="current-password"
                class="block w-full bg-black border border-matrix-border text-matrix-green placeholder-matrix-green-deep font-mono px-3 py-2 rounded focus:outline-none focus:ring-1 focus:ring-matrix-green focus:border-matrix-green"
                placeholder="••••••••"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-red-400" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between text-sm">
            <label for="remember_me" class="inline-flex items-center text-matrix-green-dim">
                <input id="remember_me" type="checkbox" name="remember"
                    class="rounded border-matrix-border bg-black text-matrix-green focus:ring-matrix-green focus:ring-offset-black">
                <span class="ms-2">{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-matrix-green-dim hover:text-matrix-green underline" href="{{ route('password.request') }}">
                    {{ __('Forgot password?') }}
                </a>
            @endif
        </div>

        <button type="submit"
            class="w-full py-2.5 border border-matrix-green text-matrix-green uppercase tracking-widest text-sm font-bold rounded hover:bg-matrix-green hover:text-black transition-colors shadow-matrix-glow">
            {{ __('Authenticate') }}
        </button>
    </form>
</x-guest-layout>
