<x-app-layout>
    <x-slot name="header">Generate Voucher</x-slot>

    @if ($mikrotiks->isEmpty())
        @include('hotspot.partials.no-mikrotiks')
    @else
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-matrix-green-dim">Pilih router untuk memuat daftar HotSpot profile yang tersedia.</p>
            @include('hotspot.partials.mikrotik-selector')
        </div>

        @if ($error)
            <div class="mb-4 px-4 py-2.5 rounded border border-red-500/40 bg-red-500/10 text-red-400 text-sm">
                &gt; Gagal membaca profile dari '{{ $selected->name }}': {{ $error }}
            </div>
        @elseif (empty($profiles))
            <div class="mb-4 px-4 py-2.5 rounded border border-yellow-400/40 bg-yellow-400/10 text-yellow-300 text-sm">
                &gt; Router ini belum memiliki HotSpot profile. Buat profile terlebih dahulu di MikroTik.
            </div>
        @endif

        @if (! empty($profiles))
            <form method="POST" action="{{ route('vouchers.store') }}" class="max-w-3xl bg-matrix-panel border border-matrix-border rounded-md p-6"
                x-data="{
                    prefix: '{{ old('username_prefix', 'JKT') }}',
                    usernameMethod: '{{ old('username_method', 'sequential') }}',
                    usernameLength: {{ old('username_length', 6) }},
                    passwordMethod: '{{ old('password_method', 'numeric') }}',
                    passwordLength: {{ old('password_length', 6) }},
                    get isUserEqualsPassword() {
                        return this.usernameMethod === 'user_equals_password';
                    },
                    randomCode(length) {
                        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
                        let out = '';
                        for (let i = 0; i < length; i++) out += chars[Math.floor(Math.random() * chars.length)];
                        return out;
                    },
                    // Computed together (not as two separate getters) so
                    // 'User = Password' mode always previews the exact
                    // same value for both — two independent getters would
                    // each draw their own random string and desync.
                    get preview() {
                        if (this.isUserEqualsPassword) {
                            const code = this.prefix.toUpperCase() + this.randomCode(this.usernameLength);

                            return { username: code, password: code };
                        }

                        const passwordChars = this.passwordMethod === 'numeric' ? '0123456789' : 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
                        let password = '';
                        for (let i = 0; i < this.passwordLength; i++) password += passwordChars[Math.floor(Math.random() * passwordChars.length)];

                        return {
                            username: this.prefix.toUpperCase() + '1'.padStart(this.usernameLength, '0'),
                            password,
                        };
                    }
                }"
            >
                @csrf
                <input type="hidden" name="mikrotik_id" value="{{ $selected->id }}">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <x-input-label value="MikroTik" />
                        <p class="mt-1 text-sm text-matrix-green">{{ $selected->name }} ({{ $selected->host }})</p>
                    </div>

                    <div>
                        <x-input-label for="profile" value="Profile" />
                        <select id="profile" name="profile" required
                            class="mt-1 block w-full bg-black border-matrix-border text-matrix-green focus:border-matrix-green focus:ring-matrix-green rounded-md shadow-sm font-mono">
                            @foreach ($profiles as $profile)
                                <option value="{{ $profile['name'] }}" @selected(old('profile') === $profile['name'])>
                                    {{ $profile['name'] }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('profile')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="quantity" value="Quantity" />
                        <x-text-input id="quantity" name="quantity" type="number" min="1" max="1000" class="mt-1 block w-full"
                            :value="old('quantity', 100)" required />
                        <p class="mt-1 text-[11px] text-matrix-green-deep">Maksimum 1000 voucher per batch.</p>
                        <x-input-error :messages="$errors->get('quantity')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="username_prefix" value="Username Prefix" />
                        <x-text-input id="username_prefix" name="username_prefix" type="text" maxlength="10" class="mt-1 block w-full uppercase"
                            x-model="prefix" :value="old('username_prefix', 'JKT')" required />
                        <x-input-error :messages="$errors->get('username_prefix')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="username_method" value="Username Generation Method" />
                        <select id="username_method" name="username_method" required x-model="usernameMethod"
                            class="mt-1 block w-full bg-black border-matrix-border text-matrix-green focus:border-matrix-green focus:ring-matrix-green rounded-md shadow-sm font-mono">
                            @foreach (\App\Enums\UsernameGenerationMethod::cases() as $method)
                                <option value="{{ $method->value }}" @selected(old('username_method', 'sequential') === $method->value)>
                                    {{ $method->label() }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-[11px] text-matrix-green-deep" x-show="isUserEqualsPassword" x-cloak>
                            Password akan otomatis sama dengan username &mdash; field Password di bawah diabaikan.
                        </p>
                        <x-input-error :messages="$errors->get('username_method')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="username_length" value="Username Number Length" />
                        <x-text-input id="username_length" name="username_length" type="number" min="3" max="12" class="mt-1 block w-full"
                            x-model.number="usernameLength" :value="old('username_length', 6)" required />
                        <x-input-error :messages="$errors->get('username_length')" class="mt-2" />
                    </div>

                    <div x-show="! isUserEqualsPassword" x-cloak>
                        <x-input-label for="password_method" value="Password Generation Method" />
                        <select id="password_method" name="password_method" required x-model="passwordMethod"
                            class="mt-1 block w-full bg-black border-matrix-border text-matrix-green focus:border-matrix-green focus:ring-matrix-green rounded-md shadow-sm font-mono">
                            @foreach (\App\Enums\PasswordGenerationMethod::cases() as $method)
                                <option value="{{ $method->value }}" @selected(old('password_method', 'numeric') === $method->value)>
                                    {{ $method->label() }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('password_method')" class="mt-2" />
                    </div>

                    <div x-show="! isUserEqualsPassword" x-cloak>
                        <x-input-label for="password_length" value="Password Length" />
                        <x-text-input id="password_length" name="password_length" type="number" min="4" max="20" class="mt-1 block w-full"
                            x-model.number="passwordLength" :value="old('password_length', 6)" required />
                        <x-input-error :messages="$errors->get('password_length')" class="mt-2" />
                    </div>
                </div>

                <div class="mt-6 px-4 py-3 rounded border border-matrix-border bg-black text-sm">
                    <p class="text-[10px] uppercase tracking-widest text-matrix-green-deep mb-1">Preview</p>
                    <p class="font-mono text-matrix-green" x-text="preview.username + ' / ' + preview.password"></p>
                </div>

                <div class="flex items-center gap-3 mt-6">
                    <x-primary-button type="submit">Generate Batch</x-primary-button>
                    <a href="{{ route('vouchers.index') }}" class="text-sm text-matrix-green-dim hover:text-matrix-green">Batal</a>
                </div>
            </form>
        @endif
    @endif
</x-app-layout>
