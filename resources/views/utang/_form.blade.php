@php $u = $utang ?? []; @endphp
<x-card>
    <h2 class="mb-4 text-base font-medium text-subjudul">Data Utang</h2>

    <div class="grid grid-cols-2 gap-x-6">
        <x-select label="Kode Utang" name="kode" wajib :pilihan="\App\Support\MockData::kodeUtang()"
            :terpilih="$u['kode'] ?? '101'" :kosong="false" />
        <x-input label="Deskripsi Utang" name="deskripsi" wajib placeholder="Contoh: KPR rumah tinggal"
            :value="$u['deskripsi'] ?? ''" />
    </div>

    <x-input label="Cicilan Bulanan" name="cicilan_bulanan" uang placeholder="0"
        :value="isset($u['cicilan']) ? angka($u['cicilan']) : ''"
        bantuan="Kosongkan bila utang tidak diangsur secara berkala." />

    <x-input label="Saldo Utang" name="saldo" wajib uang placeholder="0"
        :value="isset($u['saldo']) ? angka($u['saldo']) : ''" />

    {{-- Tahun diketik langsung, bukan dipilih, supaya pengguna bebas mengisi tahun berapa pun. --}}
    <div class="grid grid-cols-2 gap-x-6">
        <x-input label="Tahun Peminjaman" name="tahun_peminjaman" wajib inputmode="numeric" maxlength="4"
            pattern="\d{4}" data-digit placeholder="Contoh: 2024" :value="$u['tahun'] ?? ''" />
        <x-input label="Tahun Pelunasan" name="tahun_pelunasan" inputmode="numeric" maxlength="4"
            pattern="\d{4}" data-digit placeholder="Kosongkan bila belum lunas" :value="$u['tahun_pelunasan'] ?? ''" />
    </div>
    <p class="-mt-3 mb-6 text-sm text-ink-3">
        Kosongkan Tahun Pelunasan bila utang masih berjalan. Saldo tercatat per akhir tahun pajak, bukan nilai pinjaman awal.
    </p>

    <div class="mb-6 border-t border-line-soft"></div>

    <h2 class="mb-4 text-base font-medium text-subjudul">Data Kreditur</h2>

    <x-input label="Nama Kreditur" name="nama_kreditur" wajib placeholder="Contoh: Bank Mandiri" :value="$u['kreditur'] ?? ''" />

    <div class="grid grid-cols-2 gap-x-6">
        <x-input label="NIK Kreditur" name="nik_kreditur" inputmode="numeric" maxlength="16" data-digit placeholder="16 digit"
            :value="$u['nik_kreditur'] ?? ''"
            bantuan="NIK boleh dikosongkan jika kreditur berupa lembaga keuangan." />
        {{-- Kreditur selalu dalam negeri: terlihat tetapi tidak dapat diubah, sama seperti Negara di Lengkapi Profil. --}}
        <x-input-locked label="Negara Kreditur" name="negara_kreditur" value="Indonesia" />
    </div>

    <div class="mb-6 border-t border-line-soft"></div>

    <x-input label="Keterangan" name="keterangan" placeholder="Opsional" :value="$u['keterangan'] ?? ''" />

    {{-- Tombol berada di dalam kartu dan rata kiri, sama seperti formulir Penghasilan. --}}
    <div class="flex flex-wrap gap-3">
        <x-button type="submit" class="min-w-39">{{ $tombol }}</x-button>
        <x-button varian="secondary" :href="route('utang.index')" class="min-w-39">Batal</x-button>
    </div>
</x-card>
