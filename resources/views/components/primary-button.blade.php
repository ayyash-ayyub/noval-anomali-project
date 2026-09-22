<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-transparent border border-matrix-green rounded-md font-semibold text-xs text-matrix-green uppercase tracking-widest hover:bg-matrix-green hover:text-black focus:outline-none focus:ring-1 focus:ring-matrix-green focus:ring-offset-2 focus:ring-offset-black transition ease-in-out duration-150 shadow-matrix-glow']) }}>
    {{ $slot }}
</button>
