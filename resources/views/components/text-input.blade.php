@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'bg-black border-matrix-border text-matrix-green placeholder-matrix-green-deep focus:border-matrix-green focus:ring-matrix-green rounded-md shadow-sm font-mono']) }}>
