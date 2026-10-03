{{-- Kolom tampilan: bentuknya sama seperti kolom isian, tetapi hanya untuk dibaca. --}}
@props(['label' => null, 'rapat' => false])
<div class="{{ $rapat ? '' : 'mb-5' }}">
    @if($label)
        <p class="mb-2 text-[15px] text-label">{{ $label }}</p>
    @endif
    <div {{ $attributes->merge(['class' => 'kolom-isian flex items-center']) }}>{{ $slot }}</div>
</div>
