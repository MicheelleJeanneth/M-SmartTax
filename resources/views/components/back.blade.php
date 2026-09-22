@props(['href'])
<a href="{{ $href }}" class="mb-5 inline-flex items-center gap-1.5 rounded text-sm font-medium text-primary hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">
    <x-icon name="arrow-left" :size="16" /> {{ $slot }}
</a>
