{{-- Kotak keterangan: biru (informasi), kuning (peringatan), abu (netral). Tanpa garis. --}}
@props(['varian' => 'biru', 'judul' => null])
@php
    [$gaya, $ikon, $warnaIkon] = match ($varian) {
        'kuning' => ['bg-warn-bg text-warn-ink', 'triangle-alert', 'text-warn-ink'],
        'abu' => ['bg-page text-ink-2', 'lock', 'text-ink-3'],
        'biru-muda' => ['bg-[#F5FBFD] border border-primary-soft text-primary', null, ''],
        default => ['bg-primary-soft/60 text-primary-ink', 'info', 'text-primary'],
    };
@endphp
<div role="note" {{ $attributes->merge(['class' => "flex gap-3 rounded-field px-4 py-3.5 text-sm leading-relaxed $gaya"]) }}>
    @if($ikon)<x-icon :name="$ikon" :size="18" class="mt-0.5 {{ $warnaIkon }}" />@endif
    <div>
        @if($judul)<p class="mb-0.5 font-medium">{{ $judul }}</p>@endif
        {{ $slot }}
    </div>
</div>
