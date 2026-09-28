@props(['rapat' => false, 'label' => null, 'name', 'wajib' => false, 'bantuan' => null, 'type' => 'text', 'value' => null])
@php
    // Kolom tanggal bawaan browser membolehkan tahun lebih dari 4 digit.
    // Rentang ini membatasinya, dan masih bisa ditimpa lewat atribut min/max.
    $batas = $type === 'date'
        ? ['min' => '1900-01-01', 'max' => now()->toDateString()]
        : [];
@endphp
<div class="{{ $rapat ? '' : 'mb-5' }}">
    @if($label)
        <label for="{{ $name }}" class="mb-2 block text-[15px] text-label">
            {{ $label }}@if($wajib)<span class="text-danger"> *</span>@endif
        </label>
    @endif
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}"
        {{ $attributes->merge([...$batas, 'class' => 'kolom-isian']) }}>
    @if($bantuan)<p class="mt-1.5 text-sm text-ink-3">{{ $bantuan }}</p>@endif
    @error($name)<p class="mt-1.5 text-sm text-danger">{{ $message }}</p>@enderror
</div>
