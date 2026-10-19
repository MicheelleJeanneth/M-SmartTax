{{--
    Kotak keterangan di bawah isi halaman.

    - biru-muda: keterangan biasa, dipakai hampir di seluruh halaman. Ini bawaannya.
    - kuning: peringatan, dengan ikon segitiga.
    - kuning-polos: peringatan di dalam kartu, tanpa ikon.
--}}
@props(['varian' => 'biru-muda', 'judul' => null])
@php
    [$gaya, $ikon] = match ($varian) {
        'kuning' => ['bg-warn-bg text-warn-ink', 'triangle-alert'],
        'kuning-polos' => ['bg-warn-bg text-warn-ink', null],
        default => ['bg-[#F5FBFD] border border-primary-soft text-primary', null],
    };
@endphp
<div role="note" {{ $attributes->merge(['class' => "flex gap-3 rounded-field px-4 py-3.5 text-sm leading-relaxed $gaya"]) }}>
    @if($ikon)<x-icon :name="$ikon" :size="18" class="mt-0.5 text-warn-ink" />@endif
    <div>
        @if($judul)<p class="mb-0.5 font-medium">{{ $judul }}</p>@endif
        {{ $slot }}
    </div>
</div>
