{{-- Kolom terkunci: tidak bisa diketik, ditandai ikon gembok. --}}
@props(['label' => null, 'name', 'value' => null, 'bantuan' => 'Kolom ini tidak dapat diubah.'])
<div class="mb-5">
    @if($label)
        <label for="{{ $name }}" class="mb-2 block text-[15px] text-label">{{ $label }}</label>
    @endif
    <div class="relative">
        <input id="{{ $name }}" value="{{ $value }}" readonly disabled tabindex="-1"
            class="h-11 w-full cursor-not-allowed rounded-field border border-line-soft bg-page pl-3.5 pr-11
                   text-[15px] text-ink-2">
        <span class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-locked">
            <x-icon name="lock" :size="16" />
        </span>
    </div>
    @if($bantuan)<p class="mt-1.5 text-sm text-ink-3">{{ $bantuan }}</p>@endif
</div>
