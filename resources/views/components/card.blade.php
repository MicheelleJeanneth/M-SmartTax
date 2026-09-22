@props(['judul' => null, 'keterangan' => null, 'padat' => false, 'judulInk' => false])
<section {{ $attributes->merge(['class' => 'rounded-card border border-line bg-white [&>:last-child]:mb-0 ' . ($padat ? 'p-5' : 'p-6')]) }}>
    @if($judul || isset($aksi))
        <div class="mb-4 flex items-start justify-between gap-4">
            <div>
                @if($judul)<h2 class="text-base font-medium {{ $judulInk ? 'text-ink' : 'text-primary' }}">{{ $judul }}</h2>@endif
                @if($keterangan)<p class="mt-1 text-sm text-ink-2">{{ $keterangan }}</p>@endif
            </div>
            @isset($aksi)<div class="shrink-0">{{ $aksi }}</div>@endisset
        </div>
    @endif
    {{ $slot }}
</section>
