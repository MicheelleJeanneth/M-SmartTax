@extends('layouts.app')
@section('judul', 'Susun Draf - ' . $namaBulan . ' ' . $tahun)
@section('keterangan', 'Periksa perhitungan sebelum menyimpan draf')

@section('kembali')
    <x-back :href="route('draf-bulanan.index', ['tahun' => $tahun])">Kembali ke Draf Pajak Penghasilan Bulanan</x-back>
@endsection

@section('isi')
    @include('draf-bulanan._perhitungan')

    <x-info varian="kuning" class="mt-4">
        Batas setor {{ tanggal_id($batasSetor) }}. Setelah draf disimpan, data penghasilan {{ $namaBulan }} tidak dapat diubah kecuali draf dibatalkan.
    </x-info>

    {{-- Tombol rata kiri, sama seperti formulir lain. --}}
    <form method="POST" action="{{ route('draf-bulanan.store') }}" class="mt-4 flex flex-wrap gap-3">
        @csrf
        <input type="hidden" name="bulan" value="{{ $bulan }}">
        <input type="hidden" name="tahun" value="{{ $tahun }}">
        <x-button type="submit" class="min-w-39">{{ $nihil ? 'Simpan Draf Nihil' : 'Simpan Draf' }}</x-button>
        <x-button varian="secondary" :href="route('draf-bulanan.index', ['tahun' => $tahun])" class="min-w-39">Batal</x-button>
    </form>
@endsection
