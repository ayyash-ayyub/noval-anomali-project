<x-app-layout>
    <x-slot name="header">Edit MikroTik</x-slot>

    <div class="max-w-3xl">
        <form method="POST" action="{{ route('mikrotiks.update', $mikrotik) }}" class="bg-matrix-panel border border-matrix-border rounded-md p-6">
            @csrf
            @method('PUT')

            @include('mikrotiks.partials.form')

            <div class="flex items-center gap-3 mt-8">
                <x-primary-button type="submit">Simpan Perubahan</x-primary-button>
                <a href="{{ route('mikrotiks.show', $mikrotik) }}" class="text-sm text-matrix-green-dim hover:text-matrix-green">
                    Batal
                </a>
            </div>
        </form>
    </div>
</x-app-layout>
