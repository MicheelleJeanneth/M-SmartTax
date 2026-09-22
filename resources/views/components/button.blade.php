@props(['varian' => 'primary', 'type' => 'button', 'href' => null])
@php
    $gaya = [
        'primary'   => 'bg-primary text-white hover:bg-primary-ink',
        'secondary' => 'bg-white text-ink border border-line hover:bg-page',
        'danger'    => 'bg-danger text-white hover:opacity-90',
        'ghost'     => 'bg-transparent text-primary hover:bg-primary-soft/50',
    ][$varian] ?? 'bg-primary text-white hover:bg-primary-ink';

    $dasar = "inline-flex h-11 items-center justify-center gap-2 rounded-field px-5 text-base font-medium
              transition focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2
              disabled:cursor-not-allowed disabled:opacity-40
              aria-disabled:cursor-not-allowed aria-disabled:opacity-40 $gaya";
@endphp
@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $dasar]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $dasar]) }}>{{ $slot }}</button>
@endif
