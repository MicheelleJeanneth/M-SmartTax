{{-- Keadaan kosong: kalimat singkat + tombol yang mengarahkan ke tindakan. --}}
@props(['judul', 'tombol' => null, 'href' => null, 'ikon' => 'inbox'])
<div {{ $attributes->merge(['class' => 'flex flex-col items-center rounded-card border border-dashed border-line bg-white px-6 py-14 text-center']) }}>
    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-page text-ink-3">
        <x-icon :name="$ikon" :size="22" />
    </span>
    <p class="mt-4 text-base font-medium text-ink">{{ $judul }}</p>
    @if($slot->isNotEmpty())<p class="mt-1 max-w-md text-sm text-ink-2">{{ $slot }}</p>@endif
    @if($tombol && $href)
        <x-button :href="$href" class="mt-5">{{ $tombol }}</x-button>
    @endif
</div>
