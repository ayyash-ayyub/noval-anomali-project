@php
$currentRoute = request()->route()->getName();
@endphp

<form method="GET" action="{{ route($currentRoute) }}" class="flex items-center gap-2">
    <label for="mikrotik" class="text-[11px] uppercase tracking-widest text-matrix-green-deep">Router</label>
    <select id="mikrotik" name="mikrotik" onchange="this.form.submit()"
        class="bg-black border-matrix-border text-matrix-green focus:border-matrix-green focus:ring-matrix-green rounded-md shadow-sm font-mono text-sm py-1.5">
        @foreach ($mikrotiks as $option)
            <option value="{{ $option->id }}" @selected($selected?->id === $option->id)>
                {{ $option->name }} ({{ $option->host }})
            </option>
        @endforeach
    </select>
</form>
