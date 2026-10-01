<x-app-layout>
    <x-slot name="header">+ Add IP Binding</x-slot>

    @if ($mikrotiks->isEmpty())
        @include('hotspot.partials.no-mikrotiks')
    @else
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <form method="POST" action="{{ route('hotspot.ip-bindings.store') }}"
                class="lg:col-span-2 bg-matrix-panel border border-matrix-border rounded-md p-6 space-y-5">
                @csrf

                <div>
                    <x-input-label for="mikrotik_id" value="Router" />
                    <select id="mikrotik_id" name="mikrotik_id" required
                        onchange="window.location.href = '{{ route('hotspot.ip-bindings.create') }}?mikrotik=' + this.value"
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

                <div>
                    <x-input-label for="name" value="Name" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                        :value="old('name')" autofocus placeholder="contoh: Laptop Kantor / Printer Admin" />
                    <p class="mt-1 text-[11px] text-matrix-green-deep">Disimpan pada field comment binding di router.</p>
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="mac_address" value="MAC Address" />
                    <x-text-input id="mac_address" name="mac_address" type="text" class="mt-1 block w-full uppercase font-mono"
                        :value="old('mac_address')" required placeholder="AA:BB:CC:00:11:22" />
                    <x-input-error :messages="$errors->get('mac_address')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="type" value="Type" />
                    <select id="type" name="type" required
                        class="mt-1 block w-full bg-black border-matrix-border text-matrix-green focus:border-matrix-green focus:ring-matrix-green rounded-md shadow-sm font-mono">
                        <option value="bypassed" @selected(old('type', 'bypassed') === 'bypassed')>bypassed</option>
                        <option value="blocked" @selected(old('type') === 'blocked')>blocked</option>
                        <option value="regular" @selected(old('type') === 'regular')>regular</option>
                    </select>
                    <x-input-error :messages="$errors->get('type')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="address" value="Address (opsional)" />
                    <x-text-input id="address" name="address" type="text" class="mt-1 block w-full"
                        :value="old('address')" placeholder="192.168.88.50" />
                    <x-input-error :messages="$errors->get('address')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="to_address" value="To Address (opsional)" />
                    <x-text-input id="to_address" name="to_address" type="text" class="mt-1 block w-full"
                        :value="old('to_address')" placeholder="192.168.88.50" />
                    <x-input-error :messages="$errors->get('to_address')" class="mt-2" />
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <x-primary-button type="submit">Save</x-primary-button>
                    <a href="{{ route('hotspot.ip-bindings', $selected ? ['mikrotik' => $selected->id] : []) }}"
                        class="text-sm text-matrix-green-dim hover:text-matrix-green">
                        Close
                    </a>
                </div>
            </form>

            <div class="bg-matrix-panel border border-matrix-border rounded-md p-6 h-fit">
                <h3 class="text-xs uppercase tracking-widest text-matrix-green-deep mb-3">Read Me</h3>
                <div class="space-y-3 text-xs text-matrix-green-dim leading-relaxed">
                    <p>
                        IP binding dibuat langsung di router yang dipilih (<code class="text-matrix-green">/ip hotspot ip-binding</code>)
                        &mdash; tidak disimpan di database aplikasi.
                    </p>
                    <p>
                        <span class="text-matrix-green font-semibold">bypassed</span>: perangkat ini langsung bisa akses internet
                        tanpa perlu login voucher di halaman HotSpot.
                    </p>
                    <p>
                        <span class="text-matrix-green font-semibold">blocked</span>: perangkat ini diblokir total, tidak bisa
                        mengakses jaringan sama sekali.
                    </p>
                    <p>
                        <span class="text-matrix-green font-semibold">regular</span>: binding normal, tetap melalui proses
                        autentikasi HotSpot seperti biasa.
                    </p>
                    <p>
                        <span class="text-matrix-green font-semibold">Address / To Address</span> opsional &mdash; hanya diisi
                        jika perangkat perlu IP statis atau NAT ke tujuan tertentu.
                    </p>
                </div>
            </div>
        </div>
    @endif
</x-app-layout>
