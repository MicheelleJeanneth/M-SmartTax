@php
    $u = $utang ?? [];
    $tahun = collect(range(date('Y'), 1990))->mapWithKeys(fn (int $t): array => [$t => $t])->all();
@endphp
<x-card class="max-w-[840px]">
    <h2 class="mb-4 text-base font-medium text-subjudul">Data Utang</h2>

    <div class="grid grid-cols-2 gap-x-6">
        <x-select label="Kode Utang" name="kode" wajib :pilihan="\App\Support\MockData::kodeUtang()"
            :terpilih="$u['kode'] ?? '101'" :kosong="false" />
        <x-input label="Saldo Utang" name="saldo" wajib inputmode="numeric" placeholder="Rp 0"
            :value="isset($u['saldo']) ? angka($u['saldo']) : ''" />
        <x-input label="Cicilan Bulanan" name="cicilan_bulanan" inputmode="numeric" placeholder="Rp 0"
            :value="isset($u['cicilan']) ? angka($u['cicilan']) : ''"
            bantuan="Kosongkan bila utang tidak diangsur secara berkala" />
    </div>

    <x-input label="Deskripsi Utang" name="deskripsi" wajib placeholder="Contoh: KPR rumah tinggal" :value="$u['deskripsi'] ?? ''" />

    <div class="grid grid-cols-2 gap-x-6">
        <x-select label="Tahun Peminjaman" name="tahun_peminjaman" wajib :pilihan="$tahun"
            :terpilih="$u['tahun'] ?? date('Y')" :kosong="false" />
        <x-select label="Tahun Pelunasan" name="tahun_pelunasan" :pilihan="$tahun"
            :terpilih="$u['tahun_pelunasan'] ?? null" kosong="Belum lunas" />
    </div>
    <p class="-mt-3 mb-6 text-sm text-ink-3">
        Biarkan &ldquo;Belum lunas&rdquo; jika utang masih berjalan. Saldo tercatat per akhir tahun pajak, bukan nilai pinjaman awal.
    </p>

    <div class="mb-6 border-t border-line-soft"></div>

    <h2 class="mb-4 text-base font-medium text-subjudul">Data Kreditur</h2>

    <x-input label="Nama Kreditur" name="nama_kreditur" wajib placeholder="Contoh: Bank Mandiri" :value="$u['kreditur'] ?? ''" />

    <div class="grid grid-cols-2 gap-x-6">
        <x-input label="NIK / NPWP Kreditur" name="nik_kreditur" inputmode="numeric" maxlength="16" placeholder="16 digit"
            :value="$u['nik_kreditur'] ?? ''"
            bantuan="NIK atau NPWP boleh dikosongkan jika kreditur berupa lembaga keuangan." />
        <x-select label="Negara Kreditur" name="negara_kreditur" wajib :kosong="false"
            :pilihan="['Indonesia', 'Singapura', 'Malaysia', 'Hong Kong', 'Lainnya']"
            :terpilih="$u['negara'] ?? 'Indonesia'" />
    </div>

    <div class="mb-6 border-t border-line-soft"></div>

    <x-input label="Keterangan" name="keterangan" placeholder="Opsional" :value="$u['keterangan'] ?? ''" />

    <div class="flex gap-3">
        <x-button type="submit" class="min-w-[140px]">{{ $tombol }}</x-button>
        <x-button varian="secondary" :href="route('utang.index')" class="min-w-[140px]">Batal</x-button>
    </div>
</x-card>
