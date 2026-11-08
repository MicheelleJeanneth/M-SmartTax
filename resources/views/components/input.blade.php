@props(['rapat' => false, 'label' => null, 'name', 'wajib' => false, 'bantuan' => null, 'type' => 'text', 'value' => null, 'uang' => false, 'kunciSebelum' => null, 'kunciPesan' => null])
@php
    // Kolom uang: "Rp" jadi awalan tetap, pemisah ribuan dibubuhkan app.js saat mengetik.
    $gayaUang = $uang
        ? ['data-uang' => true, 'inputmode' => 'numeric', 'class' => 'kolom-isian pl-11']
        : ['class' => 'kolom-isian'];

    // Kolom bertanda wajib ikut menahan tombol kirim selama masih kosong.
    $penanda = $wajib ? ['required' => true] : [];

    // Tanggal sebelum batas ini ditolak karena drafnya sudah disusun; app.js memperingatkan lebih awal.
    // Kalimat peringatannya disusun server, karena hanya server yang tahu
    // draf bulan mana yang sebenarnya menahan tanggal tersebut.
    $kunci = $kunciSebelum
        ? ['data-kunci-sebelum' => $kunciSebelum, 'data-kunci-pesan' => $kunciPesan]
        : [];
@endphp
<div class="{{ $rapat ? '' : 'mb-5' }}">
    @if($label)
        <label for="{{ $name }}" class="mb-2 block text-[15px] text-label">
            {{ $label }}@if($wajib)<span class="text-danger"> *</span>@endif
        </label>
    @endif
    <div class="relative">
        @if($uang)
            <span class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center text-[15px] text-ink-3" aria-hidden="true">Rp</span>
        @endif
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}"
            {{ $attributes->merge([...$gayaUang, ...$penanda, ...$kunci]) }}>
    </div>
    @if($bantuan)<p class="mt-1.5 text-sm text-ink-3">{{ titik($bantuan) }}</p>@endif
    @if($kunciSebelum)<p id="{{ $name }}-kunci" role="alert" class="mt-1.5 hidden text-sm text-danger"></p>@endif
    @error($name)<p class="mt-1.5 text-sm text-danger">{{ $message }}</p>@enderror
</div>
