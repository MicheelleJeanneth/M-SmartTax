{{-- Pilihan kategori di bagian atas --}}
<nav class="mb-6 flex flex-wrap gap-2" aria-label="Kategori harta">
    @foreach($semuaKategori as $kunci => $k)
        <a href="{{ route('harta.index', $kunci) }}" @if($kunci === $kategori) aria-current="page" @endif
           class="rounded-full border px-4 py-2 text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-accent
                  {{ $kunci === $kategori ? 'border-primary bg-primary text-white' : 'border-line bg-white text-ink-2 hover:border-primary hover:text-primary' }}">
            {{ $k['nama'] }}
        </a>
    @endforeach
</nav>
