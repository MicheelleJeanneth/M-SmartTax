@props(['label' => null, 'name', 'wajib' => false, 'bantuan' => null, 'baris' => 3, 'value' => null])
<div class="mb-5">
    @if($label)
        <label for="{{ $name }}" class="mb-2 block text-[15px] text-label">
            {{ $label }}@if($wajib)<span class="text-danger"> *</span>@endif
        </label>
    @endif
    <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $baris }}"
        {{ $attributes->merge(['class' => 'kolom-isian !h-auto py-2.5', ...($wajib ? ['required' => true] : [])]) }}>{{ old($name, $value) }}</textarea>
    @if($bantuan)<p class="mt-1.5 text-sm text-ink-3">{{ titik($bantuan) }}</p>@endif
    @error($name)<p class="mt-1.5 text-sm text-danger">{{ $message }}</p>@enderror
</div>
