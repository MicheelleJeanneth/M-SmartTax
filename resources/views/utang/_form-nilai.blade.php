{{--
    Formulir pengubahan utang.

    Yang dapat diubah hanya saldo dan tahun pelunasan. Jati diri dan tahun
    peminjamannya ditetapkan sekali saat utang dicatat, karena itulah yang
    menentukan sejak draf tahun mana baris ini termuat. Saldo harus tetap
    terbuka karena menyusut tiap tahun, dan tahun pelunasan karena jadwal lunas
    dapat bergeser. Susunannya mengikuti halaman detail agar tidak terasa
    pindah tempat.
--}}
@php
    // Batas bawah tahun pelunasan, dari sumber yang sama dengan halaman lain.
    $berjalan = app(\App\Services\FilterTahun::class)->tahunBerjalan();
@endphp
<x-card>
    <x-info class="mb-6">
        Yang dapat diubah hanya saldo dan tahun pelunasan. Saldo tercatat per akhir tahun pajak, dan laporan tahun
        yang drafnya sudah tersusun tetap memakai salinan tersendiri sehingga tidak ikut berubah.
    </x-info>

    <h2 class="mb-4 text-base font-medium text-subjudul">Data Utang</h2>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6">
        <x-kolom-baca label="Kode Utang">{{ \App\Models\KodeUtang::daftarSingkat()[$utang['kode']] }}</x-kolom-baca>
        <x-kolom-baca label="Deskripsi Utang">{{ $utang['deskripsi'] }}</x-kolom-baca>
    </div>

    {{-- Satu-satunya kolom yang masih dapat diisi. --}}
    <x-input label="Saldo Utang" name="saldo" wajib uang placeholder="0" :value="angka($utang['saldo'])"
        bantuan="Saldo tercatat per akhir tahun pajak." />

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6">
        <x-kolom-baca label="Tahun Peminjaman">{{ $utang['tahun'] }}</x-kolom-baca>
        {{-- Satu-satunya kolom periode yang terbuka di sini. --}}
        <x-input label="Tahun Pelunasan" name="tahun_pelunasan" wajib inputmode="numeric" maxlength="4"
            pattern="\d{4}" data-digit :value="$utang['tahun_pelunasan'] ?? ''"
            :bantuan="'Paling awal ' . $berjalan . '; utang tidak lagi masuk lampiran draf tahun tersebut dan sesudahnya.'" />
    </div>

    <div class="mb-5 border-t border-line-soft"></div>

    <h2 class="mb-4 text-base font-medium text-subjudul">Data Kreditur</h2>

    <x-kolom-baca label="Nama Kreditur">{{ $utang['kreditur'] }}</x-kolom-baca>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6">
        <x-kolom-baca label="NIK Kreditur">{{ $utang['nik_kreditur'] ?? '—' }}</x-kolom-baca>
        <x-kolom-baca label="Negara Kreditur">{{ $utang['negara'] }}</x-kolom-baca>
    </div>

    <div class="mb-5 border-t border-line-soft"></div>

    <x-kolom-baca label="Keterangan">{{ $utang['keterangan'] ?? '—' }}</x-kolom-baca>

    <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
        <x-button type="submit" class="w-full sm:w-auto sm:min-w-39">{{ $tombol }}</x-button>
        {{-- Tetap berupa tautan, bukan tombol biasa: bila Alpine gagal dimuat,
             Batal masih membawa pengguna kembali ke daftar. x-data kosong
             karena Alpine hanya memproses @click bila ada cakupan di atasnya. --}}
        <x-button varian="secondary" :href="route('utang.index')" class="w-full sm:w-auto sm:min-w-39"
            x-data @click.prevent="$dispatch('buka-dialog', 'batal-utang')">Batal</x-button>
    </div>
</x-card>
