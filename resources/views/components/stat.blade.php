{{--
    Kartu ringkasan (Figma Dashboard).
    varian "biru": latar primary-soft, garis biru. varian "putih": latar putih, garis line.
    varian "cekung": untuk yang berada di dalam kartu putih lain, supaya tidak
    hilang sebagai putih di atas putih.
--}}
@props(['label', 'nilai', 'catatan' => null, 'varian' => 'biru'])
@php
    [$kotak, $warnaLabel] = match ($varian) {
        'putih' => ['border-line bg-white', 'text-ink-2'],
        'cekung' => ['border-line-soft bg-page', 'text-ink-2'],
        default => ['border-accent/40 bg-primary-soft', 'text-primary'],
    };
@endphp
<div {{ $attributes->merge(['class' => "rounded-card border px-5 py-5 $kotak"]) }}>
    <p class="text-sm {{ $warnaLabel }}">{{ $label }}</p>
    <p class="mt-1.5 text-[28px] leading-tight font-medium tracking-tight whitespace-nowrap text-ink tabular-nums">{{ $nilai }}</p>
    @if($catatan)<p class="mt-1 text-[13px] text-ink-3">{{ $catatan }}</p>@endif
</div>
