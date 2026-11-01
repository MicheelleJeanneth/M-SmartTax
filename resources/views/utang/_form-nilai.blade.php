{{--
    Formulir pengubahan utang.

    Hanya saldo yang dapat diubah. Jati diri dan periodenya ditetapkan sekali
    saat utang dicatat, karena itulah yang menentukan draf tahun mana yang
    memuat baris ini. Saldonya sendiri harus tetap terbuka: nilainya menyusut
    tiap tahun, dan draf tahun berikutnya memakai angka yang terbaru.
    Susunannya mengikuti halaman detail agar tidak terasa pindah tempat.
--}}
<x-card>
    <x-info class="mb-6">
        Hanya saldo yang dapat diubah. Saldo tercatat per akhir tahun pajak, dan laporan tahun yang drafnya sudah
        tersusun tetap memakai salinan tersendiri sehingga tidak ikut berubah.
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
        <x-kolom-baca label="Tahun Pelunasan">{{ $utang['tahun_pelunasan'] ?? '—' }}</x-kolom-baca>
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
        <x-button varian="secondary" :href="route('utang.index')" class="w-full sm:w-auto sm:min-w-39">Batal</x-button>
    </div>
</x-card>
