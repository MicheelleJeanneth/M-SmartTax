@php
    $u = $utang ?? [];

    // Utang selalu dicatat pada tahun berjalan; angkanya datang dari sumber
    // yang sama dengan penyaring tahun di halaman lain, bukan ditulis ulang.
    $berjalan = app(\App\Services\FilterTahun::class)->tahunBerjalan();
@endphp
<x-card>
    <h2 class="mb-4 text-base font-medium text-subjudul">Data Utang</h2>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6">
        <x-select label="Kode Utang" name="kode" wajib :pilihan="\App\Models\KodeUtang::daftarLengkap()"
            :terpilih="$u['kode'] ?? '101'" :kosong="false" />
        <x-input label="Deskripsi Utang" name="deskripsi" wajib placeholder="Contoh: KPR rumah tinggal"
            :value="$u['deskripsi'] ?? ''" />
    </div>

    <x-input label="Saldo Utang" name="saldo" wajib uang placeholder="0"
        :value="isset($u['saldo']) ? angka($u['saldo']) : ''" />

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6">
        {{-- Terkunci, bukan disabled, supaya nilainya tetap terkirim. Server
             tidak membacanya dan mengisinya sendiri dari tahun berjalan. --}}
        <x-input-locked label="Tahun Peminjaman" name="tahun_peminjaman" :value="$u['tahun'] ?? $berjalan"
            bantuan="Utang dicatat pada tahun berjalan." />
        <x-input label="Tahun Pelunasan" name="tahun_pelunasan" wajib inputmode="numeric" maxlength="4"
            pattern="\d{4}" data-digit :placeholder="'Contoh: ' . ($berjalan + 3)"
            :value="$u['tahun_pelunasan'] ?? ''" />
    </div>
    <p class="-mt-3 mb-6 text-sm text-ink-3">
        Tahun Pelunasan diisi tahun utang ini dijadwalkan lunas, paling awal {{ $berjalan }}; utang tidak lagi masuk
        lampiran draf tahun tersebut dan sesudahnya. Saldo tercatat per akhir tahun pajak, bukan nilai pinjaman awal.
    </p>

    <div class="mb-6 border-t border-line-soft"></div>

    <h2 class="mb-4 text-base font-medium text-subjudul">Data Kreditur</h2>

    <x-input label="Nama Kreditur" name="nama_kreditur" wajib placeholder="Contoh: Bank Mandiri" :value="$u['kreditur'] ?? ''" />

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6">
        <x-input label="NIK Kreditur" name="nik_kreditur" inputmode="numeric" maxlength="16" data-digit placeholder="16 digit"
            :value="$u['nik_kreditur'] ?? ''"
            bantuan="NIK boleh dikosongkan jika kreditur berupa lembaga keuangan." />
        {{-- Kreditur selalu dalam negeri: terlihat tetapi tidak dapat diubah, sama seperti Negara di Lengkapi Profil. --}}
        <x-input-locked label="Negara Kreditur" name="negara_kreditur" value="Indonesia" />
    </div>

    <div class="mb-6 border-t border-line-soft"></div>

    <x-input label="Keterangan" name="keterangan" placeholder="Opsional" :value="$u['keterangan'] ?? ''" />

    {{-- Tombol berada di dalam kartu dan rata kiri, sama seperti formulir Penghasilan. --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
        <x-button type="submit" class="w-full sm:w-auto sm:min-w-39">{{ $tombol }}</x-button>
        {{-- Tetap berupa tautan, bukan tombol biasa: bila Alpine gagal dimuat,
             Batal masih membawa pengguna kembali ke daftar. x-data kosong
             karena Alpine hanya memproses @click bila ada cakupan di atasnya. --}}
        <x-button varian="secondary" :href="route('utang.index')" class="w-full sm:w-auto sm:min-w-39"
            x-data @click.prevent="$dispatch('buka-dialog', 'batal-utang')">Batal</x-button>
    </div>
</x-card>
