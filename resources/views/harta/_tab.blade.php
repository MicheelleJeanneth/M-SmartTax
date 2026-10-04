{{-- Pilihan kategori bergaya tab bergaris bawah, sejajar dengan tepi kartu di bawahnya. --}}
<nav class="mb-6 flex gap-8 overflow-x-auto border-b border-line" aria-label="Kategori harta">
    @foreach($semuaKategori as $kunci => $k)
        <a href="{{ route('harta.index', $kunci) }}" @if($kunci === $kategori) aria-current="page" @endif
           class="-mb-px shrink-0 border-b-2 pb-3 text-[15px] whitespace-nowrap transition focus:outline-none focus-visible:ring-2 focus-visible:ring-accent
                  {{ $kunci === $kategori ? 'border-primary font-medium text-primary' : 'border-transparent text-ink-2 hover:text-primary' }}">
            {{ $k['nama'] }}
        </a>
    @endforeach
</nav>
