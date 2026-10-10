{{-- Kolom terkunci: tidak bisa diketik, ditandai ikon gembok. --}}
{{-- Teks bantuan disembunyikan dengan :bantuan="false"; null tidak bisa dipakai karena
     @props menerjemahkannya menjadi "$bantuan ?? bawaan" sehingga nilai bawaan kembali muncul. --}}
@props(['label' => null, 'name', 'value' => null, 'bantuan' => 'Kolom ini tidak dapat diubah.'])
<div class="mb-5">
    @if($label)
        <label for="{{ $name }}" class="mb-2 block text-[15px] text-label">{{ $label }}</label>
    @endif
    <div class="relative">
        {{-- readonly, bukan disabled, supaya nilainya tetap ikut tersimpan. --}}
        <input id="{{ $name }}" name="{{ $name }}" value="{{ $value }}" readonly tabindex="-1"
            class="kolom-isian cursor-not-allowed border-line-soft bg-page pr-11 text-ink-2
                   focus:border-line-soft focus:ring-0">
        <span class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-locked">
            <x-icon name="lock" :size="16" />
        </span>
    </div>
    @if($bantuan)<p class="mt-1.5 text-sm text-ink-3">{{ titik($bantuan) }}</p>@endif
</div>
