@props(['href', 'active' => false])

@php
$classes = $active
    ? 'flex items-center gap-3 px-3 py-2 rounded-md bg-gray-100 text-gray-900 font-medium'
    : 'flex items-center gap-3 px-3 py-2 rounded-md text-gray-500 hover:text-gray-900 hover:bg-gray-50 transition-colors';
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
