<div class="bg-matrix-panel border border-matrix-border rounded-md text-center py-16">
    <p class="text-matrix-green-dim text-sm mb-1">Belum ada MikroTik terdaftar.</p>
    <p class="text-matrix-green-deep text-xs mb-4">Tambahkan router terlebih dahulu untuk membaca data HotSpot.</p>
    @can('create', \App\Models\Mikrotik::class)
        <a href="{{ route('mikrotiks.create') }}"
            class="inline-flex items-center gap-1.5 px-4 py-2 border border-matrix-green text-matrix-green text-xs uppercase tracking-widest rounded hover:bg-matrix-green hover:text-black transition-colors">
            Tambah MikroTik
        </a>
    @endcan
</div>
