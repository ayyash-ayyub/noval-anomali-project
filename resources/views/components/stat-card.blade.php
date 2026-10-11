@props(['label', 'value', 'accent' => 'green', 'theme' => 'matrix'])

@php
$matrixAccents = [
    'green' => 'border-matrix-green/40 text-matrix-green shadow-matrix-glow',
    'red' => 'border-red-500/50 text-red-400',
    'yellow' => 'border-yellow-400/50 text-yellow-300',
    'blue' => 'border-sky-400/50 text-sky-300',
    'gray' => 'border-gray-500/40 text-gray-400',
];

$lightAccents = [
    'green' => 'border-gray-200 text-green-600',
    'red' => 'border-gray-200 text-red-600',
    'yellow' => 'border-gray-200 text-amber-600',
    'blue' => 'border-gray-200 text-blue-600',
    'gray' => 'border-gray-200 text-gray-500',
];

if ($theme === 'light') {
    $accentClasses = $lightAccents[$accent] ?? $lightAccents['green'];
    $baseClasses = "bg-white border rounded-lg p-4 flex flex-col gap-1 $accentClasses";
    $labelClasses = 'text-[10px] uppercase tracking-widest text-gray-400';
} else {
    $accentClasses = $matrixAccents[$accent] ?? $matrixAccents['green'];
    $baseClasses = "bg-matrix-panel border rounded-md p-4 flex flex-col gap-1 $accentClasses";
    $labelClasses = 'text-[10px] uppercase tracking-widest text-matrix-green-deep';
}
@endphp

<div {{ $attributes->merge(['class' => $baseClasses]) }}>
    <span class="{{ $labelClasses }}">{{ $label }}</span>
    <span class="text-2xl font-bold">{{ $value }}</span>
</div>
