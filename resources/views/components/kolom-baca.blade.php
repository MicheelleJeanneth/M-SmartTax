{{-- Kolom tampilan: bentuknya sama seperti kolom isian, tetapi hanya untuk dibaca. --}}
@props(['label' => null, 'bantuan' => null, 'rapat' => false])
<div class="{{ $rapat ? '' : 'mb-5' }}">
    @if($label)
        <p class="mb-2 text-[15px] text-label">{{ $label }}</p>
    @endif
    <div {{ $attributes->merge(['class' => 'kolom-isian flex items-center']) }}>{{ $slot }}</div>
    @if($bantuan)<p class="mt-1.5 text-sm text-ink-3">{{ $bantuan }}</p>@endif
</div>
