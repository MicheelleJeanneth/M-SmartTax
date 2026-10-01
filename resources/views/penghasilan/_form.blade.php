@php $p = $penghasilan ?? []; @endphp
<x-card>
    <x-input label="Tanggal" name="tanggal" type="date" wajib :value="$p['tanggal'] ?? ''"
        bantuan="Tanggal penerimaan uang, bukan tanggal pemesanan." />
    <x-input label="Nominal" name="nominal" wajib uang placeholder="0"
        :value="isset($p['nominal']) ? angka($p['nominal']) : ''"
        bantuan="Isi dengan peredaran bruto, sebelum dikurangi diskon atau potongan penjualan." />
    <x-input label="Keterangan" name="keterangan" placeholder="Contoh: Penjualan toko" :value="$p['keterangan'] ?? ''"
        bantuan="Opsional, membantu dalam mengenali transaksi nanti." />

    {{-- Tombol berada di dalam kartu dan rata kiri. --}}
    <div class="flex flex-wrap gap-3">
        <x-button type="submit" class="min-w-39">{{ $tombol }}</x-button>
        <x-button varian="secondary" :href="route('penghasilan.index')" class="min-w-39">Batal</x-button>
    </div>
</x-card>

<x-info varian="biru-muda" class="mt-4">
    Data yang sudah masuk draf pajak bulanan tidak dapat diubah. Pastikan tanggal dan nominalnya benar sebelum menyimpan.
</x-info>
