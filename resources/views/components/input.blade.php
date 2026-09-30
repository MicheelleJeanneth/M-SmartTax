@props(['rapat' => false, 'label' => null, 'name', 'wajib' => false, 'bantuan' => null, 'type' => 'text', 'value' => null])
@php
    // Kolom tanggal bawaan browser membolehkan tahun lebih dari 4 digit.
    // Batasnya disimpan sebagai data-* lalu ditegakkan di app.js sesudah kolom
    // selesai diisi. Memakai atribut min/max asli membuat Chrome menimpa tahun
    // di tengah pengetikan: menekan "2" langsung berubah menjadi 1900.
    $batas = $type === 'date'
        ? ['data-min' => '1900-01-01', 'data-max' => now()->toDateString()]
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
