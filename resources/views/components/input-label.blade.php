@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-xs uppercase tracking-widest text-matrix-green-dim']) }}>
    {{ $value ?? $slot }}
</label>
