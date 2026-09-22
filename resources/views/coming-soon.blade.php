<x-app-layout>
    <x-slot name="header">{{ $module }}</x-slot>

    <div class="max-w-2xl mx-auto text-center py-20">
        <p class="text-matrix-green-deep text-xs uppercase tracking-widest mb-2">Module Not Yet Deployed</p>
        <h2 class="text-xl sm:text-2xl font-bold text-matrix-green mb-3">{{ $module }}</h2>
        <p class="text-sm text-matrix-green-dim mb-6">{{ $description }}</p>
        <div class="inline-flex items-center gap-2 text-xs text-matrix-green-deep border border-matrix-border rounded px-3 py-2">
            <span class="w-2 h-2 rounded-full bg-yellow-400 animate-pulse"></span>
            Akan dibangun pada tahap pengembangan berikutnya.
        </div>
    </div>
</x-app-layout>
