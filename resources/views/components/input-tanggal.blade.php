@props(['label' => null, 'name', 'wajib' => false, 'bantuan' => null, 'value' => null, 'kunciSebelum' => null, 'kunciPesan' => null])
@php
    // Kolom bertipe date bawaan peramban menampilkan urutan tanggalnya
    // mengikuti setelan bahasa perangkat, dan urutan itu tidak dapat
    // ditentukan oleh halaman: di laptop berbahasa Indonesia terbaca
    // dd/mm/yyyy, di ponsel berbahasa Inggris terbaca mm/dd/yyyy. Karena itu
    // tanggal diketik sendiri dalam bentuk dd/mm/yyyy.
    //
    // Yang diketik pengguna ada di kolom tampak; yang dikirim ke server ada di
    // kolom tersembunyi bernama sama dan tetap berbentuk Y-m-d, sehingga
    // pemeriksaan di server tidak berubah sama sekali.
    $iso = tanggal_isian(old($name, $value));
    $tampil = $iso === '' ? '' : implode('/', array_reverse(explode('-', $iso)));

    $penanda = $wajib ? ['required' => true] : [];

    // Tanggal sebelum batas ini ditolak karena drafnya sudah disusun. Kalimat
    // peringatannya disusun server, karena hanya server yang tahu draf bulan
    // mana yang sebenarnya menahan tanggal tersebut.
    $kunci = $kunciSebelum
        ? ['data-kunci-sebelum' => $kunciSebelum, 'data-kunci-pesan' => $kunciPesan]
        : [];
@endphp
<div class="mb-5">
    @if($label)
        <label for="{{ $name }}-tampil" class="mb-2 block text-[15px] text-label">
            {{ $label }}@if($wajib)<span class="text-danger"> *</span>@endif
        </label>
    @endif
    <input id="{{ $name }}-tampil" type="text" inputmode="numeric" maxlength="10"
        placeholder="dd/mm/yyyy" autocomplete="off"
        data-tanggal-dmy="{{ $name }}" data-min="1900-01-01" data-max="{{ now()->toDateString() }}"
        value="{{ $tampil }}"
        {{ $attributes->merge([...$penanda, ...$kunci, 'class' => 'kolom-isian']) }}>
    {{-- Nilai yang sesungguhnya terkirim. Diperbarui app.js setiap kali kolom
         di atas diketik. Ditandai data-tanggal-iso supaya dialog penegasan
         membacanya sebagai tanggal, bukan sebagai teks biasa. --}}
    <input id="{{ $name }}" name="{{ $name }}" type="hidden" data-tanggal-iso value="{{ $iso }}">
    @if($bantuan)<p class="mt-1.5 text-sm text-ink-3">{{ titik($bantuan) }}</p>@endif
    <p id="{{ $name }}-salah" role="alert" class="mt-1.5 hidden text-sm text-danger"></p>
    @error($name)<p class="mt-1.5 text-sm text-danger">{{ $message }}</p>@enderror
</div>
