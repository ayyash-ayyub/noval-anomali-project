@props(['label', 'value', 'accent' => 'green'])

@php
$accents = [
    'green' => 'border-matrix-green/40 text-matrix-green shadow-matrix-glow',
    'red' => 'border-red-500/50 text-red-400',
    'yellow' => 'border-yellow-400/50 text-yellow-300',
    'blue' => 'border-sky-400/50 text-sky-300',
    'gray' => 'border-gray-500/40 text-gray-400',
];
$accentClasses = $accents[$accent] ?? $accents['green'];
@endphp

<div {{ $attributes->merge(['class' => "bg-matrix-panel border rounded-md p-4 flex flex-col gap-1 $accentClasses"]) }}>
    <span class="text-[10px] uppercase tracking-widest text-matrix-green-deep">{{ $label }}</span>
    <span class="text-2xl font-bold">{{ $value }}</span>
</div>
