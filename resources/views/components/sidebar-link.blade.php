@props(['href', 'active' => false])

@php
$classes = $active
    ? 'flex items-center gap-3 px-3 py-2 rounded border border-matrix-green/60 bg-matrix-green/10 text-matrix-green shadow-matrix-glow'
    : 'flex items-center gap-3 px-3 py-2 rounded border border-transparent text-matrix-green-dim hover:text-matrix-green hover:border-matrix-border hover:bg-matrix-green/5 transition-colors';
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
