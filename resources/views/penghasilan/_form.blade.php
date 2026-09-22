@php $p = $penghasilan ?? []; @endphp
<x-card class="max-w-[640px]">
    <x-input label="Tanggal" name="tanggal" type="date" wajib :value="$p['tanggal'] ?? ''" />
    <x-input label="Nominal" name="nominal" wajib inputmode="numeric" placeholder="Rp 0" :value="isset($p['nominal']) ? angka($p['nominal']) : ''"
        bantuan="Peredaran bruto sebelum dikurangi biaya apa pun." />
    <x-textarea label="Keterangan" name="keterangan" :baris="2" placeholder="Contoh: Penjualan katalog minggu ke-1" :value="$p['keterangan'] ?? ''" />
</x-card>
<div class="mt-6 flex max-w-[640px] justify-end gap-3">
    <x-button varian="secondary" :href="route('penghasilan.index')">Batal</x-button>
    <x-button type="submit">{{ $tombol }}</x-button>
</div>
