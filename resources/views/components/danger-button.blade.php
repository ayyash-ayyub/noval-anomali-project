<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-transparent border border-red-500 rounded-md font-semibold text-xs text-red-400 uppercase tracking-widest hover:bg-red-500 hover:text-black focus:outline-none focus:ring-1 focus:ring-red-500 focus:ring-offset-2 focus:ring-offset-black transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
