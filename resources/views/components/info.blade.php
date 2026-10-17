{{--
    Kotak keterangan, dinamai menurut maksudnya, bukan warnanya.

    - catatan: keterangan biasa, latar netral bergaris tipis, tanpa ikon.
    - info: keterangan yang perlu lebih menonjol, dengan ikon.
    - kuning: peringatan.
    - abu: keadaan terkunci.
    - biru-muda: hanya catatan penutup Simulasi Aset, atas permintaan.
--}}
@props(['varian' => 'catatan', 'judul' => null])
@php
    [$gaya, $ikon, $warnaIkon] = match ($varian) {
        'kuning' => ['bg-warn-bg text-warn-ink', 'triangle-alert', 'text-warn-ink'],
        'kuning-polos' => ['bg-warn-bg text-warn-ink', null, ''],
        'abu' => ['bg-page text-ink-2', 'lock', 'text-ink-3'],
        'info' => ['bg-frame text-ink-2', 'info', 'text-ink-3'],
        'biru-muda' => ['bg-[#F5FBFD] border border-primary-soft text-primary', null, ''],
        default => ['border border-line-soft bg-page text-ink-2', null, ''],
    };
@endphp
<div role="note" {{ $attributes->merge(['class' => "flex gap-3 rounded-field px-4 py-3.5 text-sm leading-relaxed $gaya"]) }}>
    @if($ikon)<x-icon :name="$ikon" :size="18" class="mt-0.5 {{ $warnaIkon }}" />@endif
    <div>
        @if($judul)<p class="mb-0.5 font-medium">{{ $judul }}</p>@endif
        {{ $slot }}
    </div>
</div>
