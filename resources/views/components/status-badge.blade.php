@props(['status'])

@php
$isLabeledEnum = is_object($status) && method_exists($status, 'label') && method_exists($status, 'color');
$label = $isLabeledEnum ? $status->label() : (string) $status;
$color = $isLabeledEnum ? $status->color() : 'gray';

$colors = [
    'green' => 'border-matrix-green/50 text-matrix-green bg-matrix-green/10',
    'red' => 'border-red-500/50 text-red-400 bg-red-500/10',
    'yellow' => 'border-yellow-400/50 text-yellow-300 bg-yellow-400/10',
    'blue' => 'border-sky-400/50 text-sky-300 bg-sky-400/10',
    'gray' => 'border-gray-500/40 text-gray-400 bg-gray-500/10',
];
$classes = $colors[$color] ?? $colors['gray'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 px-2 py-0.5 rounded border text-[11px] uppercase tracking-wider font-semibold $classes"]) }}>
    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
    {{ $label }}
</span>
