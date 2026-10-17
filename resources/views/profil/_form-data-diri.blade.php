{{-- Dipakai bersama oleh Lengkapi Profil dan Ubah Profil. $profil kosong = semua kolom kosong.
     $terkunci menandai halaman Ubah Profil: email sudah terpasang dan tidak dapat diubah. --}}
@php
    $p = $profil ?? [];
    $terkunci = $terkunci ?? false;
@endphp

<x-card judul="Data Diri">
    {{-- Pada Ubah Profil, NIK berbagi baris dengan nama. Di
         Lengkapi Profil kolomnya lebih sempit, jadi keduanya tetap bertumpuk
         agar petunjuk isiannya tidak terpotong. --}}
    <div @class(['grid grid-cols-1 sm:grid-cols-2 gap-x-4' => $terkunci])>
        <x-input label="NIK" name="nik" wajib inputmode="numeric" maxlength="16" pattern="\d{16}" data-digit
            x-ref="nik" placeholder="16 digit sesuai kartu tanda penduduk" :value="$p['nik'] ?? ''"
            :bantuan="$terkunci ? false : 'Enam digit tengah NIK memuat tanggal lahir Anda dan akan diperiksa terhadap kolom di bawah.'" />

        <x-input label="Nama Lengkap" name="nama" wajib placeholder="Sesuai kartu tanda penduduk" :value="$p['nama'] ?? ''" />
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4">
        <x-input label="Tempat Lahir" name="tempat_lahir" wajib placeholder="Contoh: Surabaya" :value="$p['tempat_lahir'] ?? ''" />
        <x-input label="Tanggal Lahir" name="tanggal_lahir" type="date" wajib :value="$p['tanggal_lahir'] ?? ''" />
    </div>

    <x-radio label="Jenis Kelamin" name="jenis_kelamin" wajib :pilihan="['Pria', 'Wanita']" :terpilih="$p['jenis_kelamin'] ?? null" />

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4">
        {{-- M-SmartTax hanya untuk wajib pajak WNI, jadi kolom ini tetap tampil tetapi terkunci. --}}
        <x-input-locked label="Kewarganegaraan" name="kewarganegaraan" value="WNI"
            :bantuan="$terkunci ? false : 'M-SmartTax hanya untuk wajib pajak berkewarganegaraan Indonesia.'" />
        <x-input label="Nomor Handphone" name="nomor_handphone" type="tel" wajib
            placeholder="08xx xxxx xxxx" :value="$p['telepon'] ?? ''" />
    </div>

    @if($terkunci)
        <x-input-locked label="Email" name="email" :value="$p['email'] ?? ''"
            bantuan="Email tidak dapat diubah karena digunakan untuk masuk ke sistem. NIK masih dapat diperbaiki, dan akan diperiksa terhadap tanggal lahir Anda." />
    @endif
</x-card>

<x-card judul="Alamat" class="mt-4">
    <x-input label="Alamat" name="alamat" wajib placeholder="Nama jalan dan nomor rumah" :value="$p['alamat'] ?? ''" />

    <div class="grid grid-cols-1 sm:grid-cols-[1fr_1fr_2fr] gap-x-4">
        <x-input label="RT" name="rt" wajib inputmode="numeric" maxlength="3" data-digit placeholder="000" :value="$p['rt'] ?? ''" />
        <x-input label="RW" name="rw" wajib inputmode="numeric" maxlength="3" data-digit placeholder="000" :value="$p['rw'] ?? ''" />
        <x-input label="Kelurahan" name="kelurahan" wajib placeholder="Contoh: Kalirungkut" :value="$p['kelurahan'] ?? ''" />
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4">
        <x-input label="Kecamatan" name="kecamatan" wajib placeholder="Contoh: Rungkut" :value="$p['kecamatan'] ?? ''" />
        <x-input label="Kota" name="kota" wajib placeholder="Contoh: Surabaya" :value="$p['kota'] ?? ''" />
        <x-input label="Provinsi" name="provinsi" wajib placeholder="Contoh: Jawa Timur" :value="$p['provinsi'] ?? ''" />
        <x-input-locked label="Negara" name="negara" value="Indonesia" :bantuan="false" />
    </div>
    <p class="-mt-3 text-sm text-ink-3">Kedelapan bagian alamat digabung menjadi satu baris pada dokumen laporan.</p>
</x-card>
