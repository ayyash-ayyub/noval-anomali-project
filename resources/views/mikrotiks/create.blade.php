<x-app-layout>
    <x-slot name="header">Tambah MikroTik</x-slot>

    <div class="max-w-3xl">
        <form method="POST" action="{{ route('mikrotiks.store') }}" class="bg-matrix-panel border border-matrix-border rounded-md p-6">
            @csrf

            @include('mikrotiks.partials.form')

            <div class="flex items-center gap-3 mt-8">
                <x-primary-button type="submit">Simpan</x-primary-button>
                <a href="{{ route('mikrotiks.index') }}" class="text-sm text-matrix-green-dim hover:text-matrix-green">
                    Batal
                </a>
            </div>
        </form>
    </div>
</x-app-layout>
