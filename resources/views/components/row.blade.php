{{-- Baris label–nilai pada kartu rincian dan kartu perhitungan. --}}
@props(['label', 'tebal' => false])
<div {{ $attributes->merge(['class' => 'flex items-baseline justify-between gap-6 py-2.5 text-[15px]']) }}>
    <span class="text-ink-2">{{ $label }}</span>
    <span class="text-right whitespace-nowrap tabular-nums {{ $tebal ? 'font-semibold text-ink' : 'text-ink' }}">{{ $slot }}</span>
</div>
