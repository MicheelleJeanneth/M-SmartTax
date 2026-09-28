{{-- Tombol pindah halaman tabel. --}}
@if($data->hasPages())
    @php
        $gaya = 'inline-flex h-9 min-w-9 items-center justify-center rounded-field border px-3 text-sm transition
                 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent';
    @endphp
    <nav class="flex items-center gap-2" aria-label="Pindah halaman">
        <a href="{{ $data->previousPageUrl() ?? '#' }}" @disabled(! $data->previousPageUrl())
           class="{{ $gaya }} {{ $data->previousPageUrl() ? 'border-line bg-white text-ink hover:bg-page' : 'pointer-events-none border-line-soft bg-white text-ink-3' }}">
            Sebelumnya
        </a>

        @foreach(range(1, $data->lastPage()) as $nomor)
            <a href="{{ $data->url($nomor) }}" @if($nomor === $data->currentPage()) aria-current="page" @endif
               class="{{ $gaya }} {{ $nomor === $data->currentPage() ? 'border-primary bg-primary text-white' : 'border-line bg-white text-ink hover:bg-page' }}">
                {{ $nomor }}
            </a>
        @endforeach

        <a href="{{ $data->nextPageUrl() ?? '#' }}" @disabled(! $data->nextPageUrl())
           class="{{ $gaya }} {{ $data->nextPageUrl() ? 'border-line bg-white text-ink hover:bg-page' : 'pointer-events-none border-line-soft bg-white text-ink-3' }}">
            Berikutnya
        </a>
    </nav>
@endif
