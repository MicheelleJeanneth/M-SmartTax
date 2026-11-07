@php $p = $penghasilan ?? []; @endphp
<x-card>
    <x-input-tanggal label="Tanggal" name="tanggal" wajib :value="tanggal_isian($p['tanggal'] ?? null)"
        :kunci-sebelum="$awalTerbuka" :kunci-pesan="$pesanKunci"
        bantuan="Urutannya hari/bulan/tahun, contoh 25/09/1980. Tanggal penerimaan uang, bukan tanggal pemesanan." />
    <x-input label="Nominal" name="nominal" wajib uang placeholder="0"
        :value="isset($p['nominal']) ? angka($p['nominal']) : ''"
        bantuan="Isi dengan peredaran bruto, sebelum dikurangi diskon atau potongan penjualan." />
    <x-input label="Keterangan" name="keterangan" wajib placeholder="Contoh: Penjualan toko" :value="$p['keterangan'] ?? ''"
        bantuan="Membantu mengenali transaksi ini nanti, dan menjadi kolom yang dicari pada daftar penghasilan." />

    {{-- Tombol berada di dalam kartu dan rata kiri. --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
        <x-button type="submit" class="w-full sm:w-auto sm:min-w-39">{{ $tombol }}</x-button>
        {{-- Tetap berupa tautan, bukan tombol biasa: bila Alpine gagal dimuat,
             Batal masih membawa pengguna kembali ke daftar. x-data kosong
             karena Alpine hanya memproses @click bila ada cakupan di atasnya. --}}
        <x-button varian="secondary" :href="route('penghasilan.index')" class="w-full sm:w-auto sm:min-w-39"
            x-data @click.prevent="$dispatch('buka-dialog', 'batal-penghasilan')">Batal</x-button>
    </div>
</x-card>
