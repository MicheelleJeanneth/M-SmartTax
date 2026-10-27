{{-- Dipakai bersama oleh Lengkapi Profil dan Ubah Profil. $profil kosong = semua kolom kosong.
     $terkunci menandai halaman Ubah Profil: username sudah terpasang dan tidak dapat diubah,
     sedangkan email boleh diisi, diganti, atau dikosongkan kapan saja. --}}
@php
    $p = $profil ?? [];
    $terkunci = $terkunci ?? false;
@endphp

<x-card judul="Data Diri">
    <x-input label="Nama Lengkap" name="nama" wajib placeholder="Sesuai kartu tanda penduduk" :value="$p['nama'] ?? ''" />

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4">
        <x-input label="Tempat Lahir" name="tempat_lahir" wajib placeholder="Contoh: Surabaya" :value="$p['tempat_lahir'] ?? ''" />
        {{-- Kolomnya bertipe date, jadi nilainya harus berbentuk Y-m-d. Cast
             'date' pada model menghasilkan "1980-09-25 00:00:00" yang ditolak
             peramban, sehingga kolomnya tampil kosong dan tombol Simpan
             tertahan karena kolom wajib dianggap belum terisi. --}}
        <x-input label="Tanggal Lahir" name="tanggal_lahir" type="date" wajib :value="tanggal_isian($p['tanggal_lahir'] ?? null)" />
    </div>

    <x-radio label="Jenis Kelamin" name="jenis_kelamin" wajib :pilihan="['Pria', 'Wanita']" :terpilih="$p['jenis_kelamin'] ?? null" />

    {{-- Lengkapi Profil memakai layout tengah selebar 560px, sehingga satu
         kolomnya hanya sekitar 223px — setengah lebar kolom di Ubah Profil.
         Baris yang punya teks petunjuk karena itu dibuat bertumpuk di sana,
         supaya petunjuknya tidak pecah menjadi tiga baris dan membuat kolom
         di sebelahnya menggantung. --}}
    <div @class(['grid grid-cols-1 sm:grid-cols-2 gap-x-4' => $terkunci])>
        {{-- M-SmartTax hanya untuk wajib pajak WNI, jadi kolom ini tetap tampil tetapi terkunci. --}}
        <x-input-locked label="Kewarganegaraan" name="kewarganegaraan"
            :value="\App\Support\MockData::KEWARGANEGARAAN"
            bantuan="Warga Negara Indonesia. M-SmartTax hanya melayani wajib pajak WNI." />
        {{-- Hanya angka yang boleh diketik. Spasinya dibubuhkan saat
             ditampilkan lewat telepon(), bukan saat diketik. --}}
        <x-input label="Nomor Handphone" name="nomor_handphone" type="tel" wajib
            inputmode="numeric" maxlength="13" data-digit placeholder="08xxxxxxxxxx"
            :value="telepon_angka($p['nomor_handphone'] ?? '')"
            bantuan="Diketik tanpa spasi, contoh 081234567890." />
    </div>

    @if($terkunci)
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4">
            <x-input-locked label="Username" name="username" :value="$p['username'] ?? ''"
                bantuan="Username tidak dapat diubah karena digunakan untuk masuk ke sistem." />
            <x-input label="Email" name="email" type="email" :value="$p['email'] ?? ''"
                placeholder="nama@email.com"
                bantuan="Boleh dikosongkan. Email tidak dipakai untuk masuk dan tidak tercetak pada dokumen laporan." />
        </div>
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
