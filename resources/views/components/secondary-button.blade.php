<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-2 bg-black border border-matrix-border rounded-md font-semibold text-xs text-matrix-green-dim uppercase tracking-widest hover:text-matrix-green hover:border-matrix-green/50 focus:outline-none focus:ring-1 focus:ring-matrix-green focus:ring-offset-2 focus:ring-offset-black disabled:opacity-25 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
