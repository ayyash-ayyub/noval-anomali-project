<x-app-layout>
    <x-slot name="header">Failed Jobs</x-slot>

    <p class="text-sm text-matrix-green-dim mb-4">
        Job yang gagal setelah seluruh percobaan retry habis. Voucher yang terdampak biasanya sudah ditandai
        <span class="text-red-400">FAILED</span> dan dapat di-retry langsung dari halaman voucher — retry di sini
        mengirim ulang job aslinya ke queue.
    </p>

    <div class="bg-matrix-panel border border-matrix-border rounded-md overflow-hidden">
        @if ($jobs->isEmpty())
            <div class="text-center py-16">
                <p class="text-matrix-green-dim text-sm">Tidak ada failed job saat ini.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-matrix-border text-left text-[11px] uppercase tracking-widest text-matrix-green-deep">
                            <th class="px-4 py-3">ID</th>
                            <th class="px-4 py-3">Job</th>
                            <th class="px-4 py-3">Queue</th>
                            <th class="px-4 py-3">Gagal Pada</th>
                            <th class="px-4 py-3">Exception</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-matrix-border/60">
                        @foreach ($jobs as $job)
                            <tr class="hover:bg-matrix-green/5 align-top">
                                <td class="px-4 py-3 text-matrix-green-dim">{{ $job->id }}</td>
                                <td class="px-4 py-3 text-matrix-green font-mono text-xs">{{ $job->display_name }}</td>
                                <td class="px-4 py-3 text-matrix-green-dim">{{ $job->queue }}</td>
                                <td class="px-4 py-3 text-matrix-green-dim whitespace-nowrap">{{ $job->failed_at }}</td>
                                <td class="px-4 py-3 text-red-400 text-xs max-w-lg">
                                    <details>
                                        <summary class="cursor-pointer">{{ \Illuminate\Support\Str::limit($job->exception, 80) }}</summary>
                                        <pre class="mt-2 whitespace-pre-wrap text-[10px] text-red-400/80">{{ \Illuminate\Support\Str::limit($job->exception, 2000) }}</pre>
                                    </details>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-3 text-xs">
                                        <form method="POST" action="{{ route('failed-jobs.retry', $job->id) }}">
                                            @csrf
                                            <button type="submit" class="text-sky-300 hover:underline">Retry</button>
                                        </form>
                                        <form method="POST" action="{{ route('failed-jobs.destroy', $job->id) }}"
                                            onsubmit="return confirm('Hapus failed job #{{ $job->id }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-400 hover:underline">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3 border-t border-matrix-border">
                {{ $jobs->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
