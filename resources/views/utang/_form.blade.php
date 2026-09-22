@php $u = $utang ?? []; @endphp
<x-card class="max-w-[720px]">
    <div class="grid grid-cols-2 gap-x-4">
        <x-input label="Kode Utang" name="kode" wajib :value="$u['kode'] ?? ''" />
        <x-input label="Tahun Peminjaman" name="tahun" wajib inputmode="numeric" maxlength="4" :value="$u['tahun'] ?? ''" />
    </div>
    <x-input label="Nama Kreditur" name="kreditur" wajib :value="$u['kreditur'] ?? ''" placeholder="Bank, koperasi, atau perorangan" />
    <x-select label="Jenis Utang" name="jenis" wajib :terpilih="$u['jenis'] ?? null"
        :pilihan="['Kredit Pemilikan Rumah', 'Kredit Kendaraan Bermotor', 'Kartu Kredit', 'Utang Usaha', 'Utang Lainnya']" />
    <x-input label="Saldo Akhir Tahun" name="saldo" wajib inputmode="numeric" placeholder="Rp 0" :value="isset($u['saldo']) ? angka($u['saldo']) : ''"
        bantuan="Sisa pokok utang per 31 Desember." />
    <x-textarea label="Keterangan" name="keterangan" :baris="2" />
</x-card>
<div class="mt-6 flex max-w-[720px] justify-end gap-3">
    <x-button varian="secondary" :href="route('utang.index')">Batal</x-button>
    <x-button type="submit">{{ $tombol }}</x-button>
</div>
