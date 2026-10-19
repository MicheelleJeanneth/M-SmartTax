@php $p = $penghasilan ?? []; @endphp
<x-card>
    <x-input label="Tanggal" name="tanggal" type="date" wajib :value="tanggal_isian($p['tanggal'] ?? null)" :kunci-sebelum="$awalTerbuka"
        bantuan="Tanggal penerimaan uang, bukan tanggal pemesanan." />
    <x-input label="Nominal" name="nominal" wajib uang placeholder="0"
        :value="isset($p['nominal']) ? angka($p['nominal']) : ''"
        bantuan="Isi dengan peredaran bruto, sebelum dikurangi diskon atau potongan penjualan." />
    <x-input label="Keterangan" name="keterangan" placeholder="Contoh: Penjualan toko" :value="$p['keterangan'] ?? ''"
        bantuan="Opsional, membantu dalam mengenali transaksi nanti." />

    {{-- Tombol berada di dalam kartu dan rata kiri. --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
        <x-button type="submit" class="w-full sm:w-auto sm:min-w-39">{{ $tombol }}</x-button>
        <x-button varian="secondary" :href="route('penghasilan.index')" class="w-full sm:w-auto sm:min-w-39">Batal</x-button>
    </div>
</x-card>
