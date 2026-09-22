@props(['rapat' => false, 'label' => null, 'name', 'wajib' => false, 'bantuan' => null, 'type' => 'text', 'value' => null])
<div class="{{ $rapat ? '' : 'mb-5' }}">
    @if($label)
        <label for="{{ $name }}" class="mb-2 block text-[15px] text-label">
            {{ $label }}@if($wajib)<span class="text-danger"> *</span>@endif
        </label>
    @endif
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}"
        {{ $attributes->merge(['class' =>
            'h-11 w-full rounded-field border border-line bg-white px-3.5 text-[15px] text-ink
             placeholder:text-ink-3/70 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary']) }}>
    @if($bantuan)<p class="mt-1.5 text-sm text-ink-3">{{ $bantuan }}</p>@endif
    @error($name)<p class="mt-1.5 text-sm text-danger">{{ $message }}</p>@enderror
</div>
