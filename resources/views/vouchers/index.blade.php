<x-app-layout>
    <x-slot name="header">Voucher List</x-slot>

    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <form method="GET" action="{{ route('vouchers.index') }}" class="flex flex-wrap items-center gap-2 text-sm">
            <select name="mikrotik_id" onchange="this.form.submit()"
                class="bg-black border-matrix-border text-matrix-green focus:border-matrix-green focus:ring-matrix-green rounded-md shadow-sm font-mono text-sm py-1.5">
                <option value="">Semua Router</option>
                @foreach ($mikrotiks as $mikrotik)
                    <option value="{{ $mikrotik->id }}" @selected(request('mikrotik_id') == $mikrotik->id)>{{ $mikrotik->name }}</option>
                @endforeach
            </select>

            <select name="status" onchange="this.form.submit()"
                class="bg-black border-matrix-border text-matrix-green focus:border-matrix-green focus:ring-matrix-green rounded-md shadow-sm font-mono text-sm py-1.5">
                <option value="">Semua Status</option>
                @foreach (\App\Enums\VoucherStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>

            @if (request('mikrotik_id') || request('status'))
                <a href="{{ route('vouchers.index') }}" class="text-matrix-green-dim hover:text-matrix-green text-xs">Reset</a>
            @endif
        </form>

        <a href="{{ route('vouchers.generate') }}"
            class="inline-flex items-center gap-1.5 px-4 py-2 border border-matrix-green text-matrix-green text-xs uppercase tracking-widest rounded hover:bg-matrix-green hover:text-black transition-colors shadow-matrix-glow">
            <span>&#43;</span> Generate Voucher
        </a>
    </div>

    <form method="POST" action="{{ route('vouchers.print.store') }}" x-data="{ checked: [] }">
        @csrf

        <div class="bg-matrix-panel border border-matrix-border rounded-md overflow-hidden">
            @if ($vouchers->isEmpty())
                <div class="text-center py-16">
                    <p class="text-matrix-green-dim text-sm mb-1">Belum ada voucher.</p>
                    <p class="text-matrix-green-deep text-xs">Generate batch voucher pertama untuk mulai.</p>
                </div>
            @else
                <div class="flex items-center justify-between px-4 py-2.5 border-b border-matrix-border text-xs">
                    <span class="text-matrix-green-dim">
                        <span x-text="checked.length"></span> voucher dipilih (halaman ini)
                    </span>
                    <div class="flex items-center gap-2">
                        <select name="paper_size" class="bg-black border-matrix-border text-matrix-green text-xs rounded-md py-1.5 focus:border-matrix-green focus:ring-matrix-green">
                            @foreach (\App\Enums\PdfPaperSize::cases() as $size)
                                <option value="{{ $size->value }}">{{ $size->label() }}</option>
                            @endforeach
                        </select>
                        <button type="submit" :disabled="checked.length === 0"
                            class="px-3 py-1.5 border border-matrix-green text-matrix-green uppercase tracking-widest rounded hover:bg-matrix-green hover:text-black transition-colors disabled:opacity-30 disabled:cursor-not-allowed">
                            Print Selected
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-matrix-border text-left text-[11px] uppercase tracking-widest text-matrix-green-deep">
                                <th class="px-4 py-3 w-8">
                                    <input type="checkbox"
                                        @change="checked = $event.target.checked ? Array.from($el.closest('table').querySelectorAll('.voucher-checkbox')).map(el => el.value) : []; $el.closest('table').querySelectorAll('.voucher-checkbox').forEach(el => el.checked = $event.target.checked)"
                                        class="rounded border-matrix-border bg-black text-matrix-green focus:ring-matrix-green focus:ring-offset-black">
                                </th>
                                <th class="px-4 py-3">Username</th>
                                <th class="px-4 py-3">Password</th>
                                <th class="px-4 py-3">Router</th>
                                <th class="px-4 py-3">Profile</th>
                                <th class="px-4 py-3">Batch</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Dibuat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-matrix-border/60">
                            @foreach ($vouchers as $voucher)
                                <tr class="hover:bg-matrix-green/5">
                                    <td class="px-4 py-3">
                                        <input type="checkbox" name="voucher_ids[]" value="{{ $voucher->id }}"
                                            class="voucher-checkbox rounded border-matrix-border bg-black text-matrix-green focus:ring-matrix-green focus:ring-offset-black"
                                            x-model="checked">
                                    </td>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('vouchers.show', $voucher) }}" class="text-matrix-green hover:underline font-medium">
                                            {{ $voucher->username }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-matrix-green-dim font-mono">{{ $voucher->password }}</td>
                                    <td class="px-4 py-3 text-matrix-green-dim">{{ $voucher->mikrotik->name }}</td>
                                    <td class="px-4 py-3 text-matrix-green-dim">{{ $voucher->profile }}</td>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('vouchers.batches.show', $voucher->batch) }}" class="text-matrix-green-dim hover:text-matrix-green text-xs">
                                            {{ $voucher->batch->batch_code }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3"><x-status-badge :status="$voucher->status" /></td>
                                    <td class="px-4 py-3 text-matrix-green-deep">{{ $voucher->created_at->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-4 py-3 border-t border-matrix-border">
                    {{ $vouchers->links() }}
                </div>
            @endif
        </div>
    </form>
</x-app-layout>
