@props(['label' => 'Kata Sandi', 'name' => 'password', 'wajib' => true, 'bantuan' => null, 'rapat' => false])
<div class="{{ $rapat ? '' : 'mb-5' }}" x-data="{ tampil: false }">
    <label for="{{ $name }}" class="mb-2 block text-[15px] text-label">
        {{ $label }}@if($wajib)<span class="text-danger"> *</span>@endif
    </label>
    <div class="relative">
        <input id="{{ $name }}" name="{{ $name }}" :type="tampil ? 'text' : 'password'"
            {{ $attributes->merge(['class' => 'kolom-isian pr-11']) }}>
        <button type="button" @click="tampil = !tampil"
            class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-field text-ink-3
                   focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
            :aria-label="tampil ? 'Sembunyikan kata sandi' : 'Lihat kata sandi'">
            <template x-if="!tampil"><x-icon name="eye" :size="18" /></template>
            <template x-if="tampil"><x-icon name="eye-off" :size="18" /></template>
        </button>
    </div>
    @if($bantuan)<p class="mt-1.5 text-sm text-ink-3">{{ $bantuan }}</p>@endif
    @error($name)<p class="mt-1.5 text-sm text-danger">{{ $message }}</p>@enderror
</div>
