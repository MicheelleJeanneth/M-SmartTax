@props(['label' => 'Kata Sandi', 'name' => 'password', 'wajib' => true, 'bantuan' => null])
<div class="mb-5" x-data="{ tampil: false }">
    <label for="{{ $name }}" class="mb-2 block text-[15px] text-label">
        {{ $label }}@if($wajib)<span class="text-danger"> *</span>@endif
    </label>
    <div class="relative">
        <input id="{{ $name }}" name="{{ $name }}" :type="tampil ? 'text' : 'password'"
            {{ $attributes->merge(['class' =>
                'h-11 w-full rounded-field border border-line bg-white pl-3.5 pr-11 text-[15px] text-ink
                 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary']) }}>
        <button type="button" @click="tampil = !tampil"
            class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-ink-3
                   focus:outline-none focus-visible:ring-2 focus-visible:ring-accent rounded-r-field"
            :aria-label="tampil ? 'Sembunyikan kata sandi' : 'Lihat kata sandi'">
            <template x-if="!tampil"><x-icon name="eye" :size="18" /></template>
            <template x-if="tampil"><x-icon name="eye-off" :size="18" /></template>
        </button>
    </div>
    @if($bantuan)<p class="mt-1.5 text-sm text-ink-3">{{ $bantuan }}</p>@endif
    @error($name)<p class="mt-1.5 text-sm text-danger">{{ $message }}</p>@enderror
</div>
