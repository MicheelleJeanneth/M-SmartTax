{{-- Dipakai bersama oleh Lengkapi Profil dan Ubah Profil. $profil kosong = semua kolom kosong. --}}
@php $p = $profil ?? []; $terkunci = $terkunci ?? false; @endphp
<x-card judul="Data Diri">
    @if($terkunci)
        <x-input-locked label="NIK" name="nik" :value="$p['nik'] ?? ''" bantuan="NIK tidak dapat diubah karena tercetak pada laporan." />
    @else
        <x-input label="NIK" name="nik" wajib inputmode="numeric" maxlength="16" placeholder="16 digit sesuai KTP" :value="$p['nik'] ?? ''" />
    @endif
    <x-input label="Nama Lengkap" name="nama" wajib placeholder="Sesuai KTP" :value="$p['nama'] ?? ''" />
    <div class="grid grid-cols-2 gap-x-4">
        <x-input label="Tempat Lahir" name="tempat_lahir" wajib :value="$p['tempat_lahir'] ?? ''" />
        <x-input label="Tanggal Lahir" name="tanggal_lahir" type="date" wajib :value="$p['tanggal_lahir'] ?? ''" />
    </div>
    <div class="grid grid-cols-2 gap-x-4">
        <x-select label="Jenis Kelamin" name="jenis_kelamin" wajib :pilihan="['Laki-laki', 'Perempuan']" :terpilih="$p['jenis_kelamin'] ?? null" />
        <x-select label="Kewarganegaraan" name="kewarganegaraan" wajib :pilihan="['WNI', 'WNA']" :terpilih="$p['kewarganegaraan'] ?? null" />
    </div>
    <x-input label="Nomor Telepon" name="telepon" type="tel" wajib placeholder="08xx-xxxx-xxxx" :value="$p['telepon'] ?? ''" />
</x-card>

<x-card judul="Alamat" class="mt-6">
    <x-textarea label="Alamat Lengkap" name="alamat" wajib :baris="2" placeholder="Nama jalan dan nomor rumah" :value="$p['alamat'] ?? ''" />
    <div class="grid grid-cols-2 gap-x-4">
        <x-input label="RT / RW" name="rt_rw" placeholder="001 / 002" :value="$p['rt_rw'] ?? ''" />
        <x-input label="Kode Pos" name="kode_pos" inputmode="numeric" :value="$p['kode_pos'] ?? ''" />
        <x-input label="Kelurahan / Desa" name="kelurahan" wajib :value="$p['kelurahan'] ?? ''" />
        <x-input label="Kecamatan" name="kecamatan" wajib :value="$p['kecamatan'] ?? ''" />
        <x-input label="Kota / Kabupaten" name="kota" wajib :value="$p['kota'] ?? ''" />
        <x-input label="Provinsi" name="provinsi" wajib :value="$p['provinsi'] ?? ''" />
    </div>
    {{-- Negara satu-satunya kolom yang terisi sejak awal. --}}
    <x-input label="Negara" name="negara" wajib :value="$p['negara'] ?? 'Indonesia'" />
</x-card>
