{{-- Dipakai bersama oleh Lengkapi Profil dan Ubah Profil. $profil kosong = semua kolom kosong. --}}
@php
    $p = $profil ?? [];
    $terkunci = $terkunci ?? false;
@endphp

<x-card judul="Data Diri">
    @if($terkunci)
        <x-input-locked label="NIK" name="nik" :value="$p['nik'] ?? ''" bantuan="NIK tidak dapat diubah setelah disimpan." />
    @else
        <x-input label="NIK" name="nik" wajib inputmode="numeric" maxlength="16"
            placeholder="16 digit sesuai kartu tanda penduduk" :value="$p['nik'] ?? ''" />
    @endif

    <x-input label="Nama Lengkap" name="nama" wajib placeholder="Sesuai kartu tanda penduduk" :value="$p['nama'] ?? ''" />

    <div class="grid grid-cols-2 gap-x-4">
        <x-input label="Tempat Lahir" name="tempat_lahir" wajib placeholder="Contoh: Surabaya" :value="$p['tempat_lahir'] ?? ''" />
        <x-input label="Tanggal Lahir" name="tanggal_lahir" type="date" wajib :value="$p['tanggal_lahir'] ?? ''" />
    </div>

    <x-radio label="Jenis Kelamin" name="jenis_kelamin" wajib :pilihan="['Pria', 'Wanita']" :terpilih="$p['jenis_kelamin'] ?? null" />

    <div class="grid grid-cols-2 gap-x-4">
        {{-- M-SmartTax hanya untuk wajib pajak WNI, jadi kolom ini tetap tampil tetapi terkunci. --}}
        <x-input-locked label="Kewarganegaraan" name="kewarganegaraan" value="WNI"
            bantuan="M-SmartTax hanya untuk wajib pajak berkewarganegaraan Indonesia." />
        <x-input label="Nomor Handphone" name="nomor_handphone" type="tel" wajib
            placeholder="08xx xxxx xxxx" :value="$p['telepon'] ?? ''" />
    </div>
</x-card>

<x-card judul="Alamat" class="mt-6">
    <x-input label="Alamat" name="alamat" wajib placeholder="Nama jalan dan nomor rumah" :value="$p['alamat'] ?? ''" />

    <div class="grid grid-cols-[1fr_1fr_2fr] gap-x-4">
        <x-input label="RT" name="rt" wajib inputmode="numeric" maxlength="3" placeholder="000" :value="$p['rt'] ?? ''" />
        <x-input label="RW" name="rw" wajib inputmode="numeric" maxlength="3" placeholder="000" :value="$p['rw'] ?? ''" />
        <x-input label="Kelurahan" name="kelurahan" wajib placeholder="Contoh: Kalirungkut" :value="$p['kelurahan'] ?? ''" />
    </div>

    <div class="grid grid-cols-2 gap-x-4">
        <x-input label="Kecamatan" name="kecamatan" wajib placeholder="Contoh: Rungkut" :value="$p['kecamatan'] ?? ''" />
        <x-input label="Kota" name="kota" wajib placeholder="Contoh: Surabaya" :value="$p['kota'] ?? ''" />
        <x-input label="Provinsi" name="provinsi" wajib placeholder="Contoh: Jawa Timur" :value="$p['provinsi'] ?? ''" />
        <x-input-locked label="Negara" name="negara" value="Indonesia" :bantuan="false" />
    </div>
</x-card>
