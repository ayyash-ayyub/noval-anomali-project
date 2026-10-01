<x-app-layout>
    <x-slot name="header">+ Add User Profile</x-slot>

    @if ($mikrotiks->isEmpty())
        @include('hotspot.partials.no-mikrotiks')
    @else
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <form method="POST" action="{{ route('hotspot.profiles.store') }}"
                class="lg:col-span-2 bg-matrix-panel border border-matrix-border rounded-md p-6 space-y-5">
                @csrf

                <div>
                    <x-input-label for="mikrotik_id" value="Router" />
                    <select id="mikrotik_id" name="mikrotik_id" required
                        onchange="window.location.href = '{{ route('hotspot.profiles.create') }}?mikrotik=' + this.value"
                        class="mt-1 block w-full bg-black border-matrix-border text-matrix-green focus:border-matrix-green focus:ring-matrix-green rounded-md shadow-sm font-mono">
                        @foreach ($mikrotiks as $option)
                            <option value="{{ $option->id }}" @selected(old('mikrotik_id', $selected?->id) == $option->id)>
                                {{ $option->name }} ({{ $option->host }})
                            </option>
                        @endforeach
                    </select>
                    @if ($selected)
                        <p class="mt-1.5 flex items-center gap-2 text-[11px] text-matrix-green-deep">
                            Status: <x-status-badge :status="$selected->status" />
                        </p>
                    @endif
                    <x-input-error :messages="$errors->get('mikrotik_id')" class="mt-2" />
                </div>

                @if ($poolError)
                    <div class="px-4 py-2.5 rounded border border-red-500/40 bg-red-500/10 text-red-400 text-sm">
                        &gt; Router ini belum terkoneksi, tidak bisa membaca Address Pool: {{ $poolError }}
                        <br>
                        <span class="text-red-400/70">Pilih router lain yang sedang ONLINE, atau perbaiki koneksinya dulu sebelum membuat profile di sini.</span>
                    </div>
                @endif

                <div>
                    <x-input-label for="name" value="Name" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                        :value="old('name')" required autofocus placeholder="contoh: 1-Hari-24jam" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="address_pool" value="Address Pool" />
                    <select id="address_pool" name="address_pool"
                        class="mt-1 block w-full bg-black border-matrix-border text-matrix-green focus:border-matrix-green focus:ring-matrix-green rounded-md shadow-sm font-mono">
                        <option value="none" @selected(old('address_pool', 'none') === 'none')>none</option>
                        @foreach ($pools as $pool)
                            <option value="{{ $pool['name'] }}" @selected(old('address_pool') === $pool['name'])>
                                {{ $pool['name'] }} ({{ $pool['ranges'] ?? '—' }})
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-[11px] text-matrix-green-deep">Daftar pool dibaca langsung dari router yang dipilih.</p>
                    <x-input-error :messages="$errors->get('address_pool')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="shared_users" value="Shared Users" />
                    <x-text-input id="shared_users" name="shared_users" type="number" min="1" max="9999" class="mt-1 block w-full"
                        :value="old('shared_users', 1)" required />
                    <x-input-error :messages="$errors->get('shared_users')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="rate_limit" value="Rate limit [up/down]" />
                    <x-text-input id="rate_limit" name="rate_limit" type="text" class="mt-1 block w-full"
                        :value="old('rate_limit')" placeholder="Example : 512k/1M" />
                    <x-input-error :messages="$errors->get('rate_limit')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="session_timeout" value="Session Timeout" />
                    <x-text-input id="session_timeout" name="session_timeout" type="text" class="mt-1 block w-full"
                        :value="old('session_timeout')" placeholder="Example : 1d, 6h, 5h30m" />
                    <p class="mt-1 text-[11px] text-matrix-green-deep">
                        Menggantikan "Expired Mode" Mikhmon &mdash; ini field durasi voucher asli RouterOS, dan langsung
                        dipakai oleh fitur lifecycle tracking aplikasi ini (lihat panel kanan).
                    </p>
                    <x-input-error :messages="$errors->get('session_timeout')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="parent_queue" value="Parent Queue" />
                    <x-text-input id="parent_queue" name="parent_queue" type="text" class="mt-1 block w-full"
                        :value="old('parent_queue', 'none')" placeholder="none" />
                    <x-input-error :messages="$errors->get('parent_queue')" class="mt-2" />
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <x-primary-button type="submit">Save</x-primary-button>
                    <a href="{{ route('hotspot.profiles', $selected ? ['mikrotik' => $selected->id] : []) }}"
                        class="text-sm text-matrix-green-dim hover:text-matrix-green">
                        Close
                    </a>
                </div>
            </form>

            <div class="bg-matrix-panel border border-matrix-border rounded-md p-6 h-fit">
                <h3 class="text-xs uppercase tracking-widest text-matrix-green-deep mb-3">Read Me</h3>
                <div class="space-y-3 text-xs text-matrix-green-dim leading-relaxed">
                    <p>
                        Profile ini dibuat langsung di router yang dipilih (<code class="text-matrix-green">/ip hotspot user profile</code>)
                        &mdash; tidak disimpan di database aplikasi.
                    </p>
                    <p>
                        <span class="text-matrix-green font-semibold">Session Timeout</span> adalah batas durasi login voucher.
                        Format <code class="text-matrix-green">[wdhm]</code>, contoh: <code class="text-matrix-green">30d</code> = 30 hari,
                        <code class="text-matrix-green">12h</code> = 12 jam, <code class="text-matrix-green">5h30m</code> = 5 jam 30 menit.
                        Kosongkan untuk tanpa batas waktu.
                    </p>
                    <p>
                        <span class="text-matrix-green font-semibold">Shared Users</span> membatasi jumlah device yang bisa login
                        bersamaan dengan satu voucher.
                    </p>
                    <div class="pt-2 border-t border-matrix-border">
                        <p class="text-matrix-green-deep">
                            Field Mikhmon <span class="text-matrix-green-dim">Expired Mode</span>,
                            <span class="text-matrix-green-dim">Price Rp</span>, <span class="text-matrix-green-dim">Selling Price Rp</span>,
                            dan <span class="text-matrix-green-dim">Lock User</span> sengaja tidak ditampilkan di sini &mdash;
                            keempatnya adalah fitur scripting/bookkeeping milik Mikhmon sendiri, bukan parameter asli RouterOS,
                            dan aplikasi ini memang dirancang tanpa Mikhmon.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-app-layout>
