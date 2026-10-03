@php
    $u = $utang ?? [];
    $tahun = collect(range(date('Y'), 1990))->mapWithKeys(fn (int $t): array => [$t => $t])->all();
@endphp
<x-card>
    <h2 class="mb-4 text-base font-medium text-subjudul">Data Utang</h2>

    <div class="grid grid-cols-2 gap-x-6">
        <x-select label="Kode Utang" name="kode" wajib :pilihan="\App\Support\MockData::kodeUtang()"
            :terpilih="$u['kode'] ?? '101'" :kosong="false" />
        <x-input label="Deskripsi Utang" name="deskripsi" wajib placeholder="Contoh: KPR rumah tinggal"
            :value="$u['deskripsi'] ?? ''" />
    </div>

    <div class="grid grid-cols-2 gap-x-6">
        <x-input label="Cicilan Bulanan" name="cicilan_bulanan" uang placeholder="0"
            :value="isset($u['cicilan']) ? angka($u['cicilan']) : ''"
            bantuan="Kosongkan bila utang tidak diangsur secara berkala" />
    </div>

    <x-input label="Saldo Utang" name="saldo" wajib uang placeholder="0"
        :value="isset($u['saldo']) ? angka($u['saldo']) : ''" />

    <div class="grid grid-cols-2 gap-x-6">
        <x-select label="Tahun Peminjaman" name="tahun_peminjaman" wajib :pilihan="$tahun"
            :terpilih="$u['tahun'] ?? date('Y')" :kosong="false" />
        <x-select label="Tahun Pelunasan" name="tahun_pelunasan" :pilihan="$tahun"
            :terpilih="$u['tahun_pelunasan'] ?? null" kosong="Belum lunas" />
    </div>
    <p class="-mt-3 mb-6 text-sm text-ink-3">
        Biarkan &ldquo;Belum lunas&rdquo; jika utang masih berjalan. Saldo tercatat per akhir tahun pajak, bukan nilai pinjaman awal
    </p>

    <div class="mb-6 border-t border-line-soft"></div>

    <h2 class="mb-4 text-base font-medium text-subjudul">Data Kreditur</h2>

    <x-input label="Nama Kreditur" name="nama_kreditur" wajib placeholder="Contoh: Bank Mandiri" :value="$u['kreditur'] ?? ''" />

    <div class="grid grid-cols-2 gap-x-6">
        <x-input label="NIK Kreditur" name="nik_kreditur" inputmode="numeric" maxlength="16" placeholder="16 digit"
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
