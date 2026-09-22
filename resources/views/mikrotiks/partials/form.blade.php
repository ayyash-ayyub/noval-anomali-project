@php
$isEdit = isset($mikrotik);
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-6"
    x-data="{
        updatePort() {
            const ports = { api: this.$refs.ssl.checked ? 8729 : 8728, rest: this.$refs.ssl.checked ? 8443 : 8080 };
            this.$refs.port.value = ports[this.$refs.apiType.value];
        }
    }"
>
    <div>
        <x-input-label for="name" value="Nama Router" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
            :value="old('name', $isEdit ? $mikrotik->name : '')" required autofocus placeholder="Jakarta - Kantor Pusat" />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="host" value="Host / IP Address" />
        <x-text-input id="host" name="host" type="text" class="mt-1 block w-full"
            :value="old('host', $isEdit ? $mikrotik->host : '')" required placeholder="192.168.88.1" />
        <x-input-error :messages="$errors->get('host')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="api_type" value="Tipe Koneksi" />
        <select x-ref="apiType" id="api_type" name="api_type" required @change="updatePort()"
            class="mt-1 block w-full bg-black border-matrix-border text-matrix-green focus:border-matrix-green focus:ring-matrix-green rounded-md shadow-sm font-mono">
            <option value="api" @selected(old('api_type', $isEdit ? $mikrotik->api_type->value : 'api') === 'api')>RouterOS API (binary)</option>
            <option value="rest" @selected(old('api_type', $isEdit ? $mikrotik->api_type->value : 'api') === 'rest')>RouterOS REST API</option>
        </select>
        <x-input-error :messages="$errors->get('api_type')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="port" value="Port" />
        <x-text-input x-ref="port" id="port" name="port" type="number" min="1" max="65535" class="mt-1 block w-full"
            :value="old('port', $isEdit ? $mikrotik->port : 8728)" required />
        <p class="mt-1 text-[11px] text-matrix-green-deep">Default: API 8728 (SSL 8729) &middot; REST 80/8080 (SSL 443/8443). Bisa disesuaikan.</p>
        <x-input-error :messages="$errors->get('port')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="username" value="Username" />
        <x-text-input id="username" name="username" type="text" class="mt-1 block w-full"
            :value="old('username', $isEdit ? $mikrotik->username : '')" required autocomplete="off" />
        <x-input-error :messages="$errors->get('username')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="password_encrypted" value="Password" />
        <x-text-input id="password_encrypted" name="password_encrypted" type="password" class="mt-1 block w-full"
            autocomplete="new-password" placeholder="{{ $isEdit ? 'Kosongkan jika tidak diubah' : '' }}"
            :required="! $isEdit" />
        <p class="mt-1 text-[11px] text-matrix-green-deep">Password dienkripsi otomatis sebelum disimpan.</p>
        <x-input-error :messages="$errors->get('password_encrypted')" class="mt-2" />
    </div>

    <div class="flex items-center gap-2 sm:col-span-2">
        <input x-ref="ssl" id="ssl_enabled" name="ssl_enabled" type="checkbox" value="1" @change="updatePort()"
            {{ old('ssl_enabled', $isEdit ? $mikrotik->ssl_enabled : false) ? 'checked' : '' }}
            class="rounded border-matrix-border bg-black text-matrix-green focus:ring-matrix-green focus:ring-offset-black">
        <x-input-label for="ssl_enabled" value="Gunakan SSL/TLS" class="!normal-case !tracking-normal" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="description" value="Deskripsi (opsional)" />
        <textarea id="description" name="description" rows="3"
            class="mt-1 block w-full bg-black border-matrix-border text-matrix-green placeholder-matrix-green-deep focus:border-matrix-green focus:ring-matrix-green rounded-md shadow-sm font-mono"
            placeholder="Catatan lokasi, kontak teknisi, dsb.">{{ old('description', $isEdit ? $mikrotik->description : '') }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>
</div>
