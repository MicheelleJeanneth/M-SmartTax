@extends('layouts.app')
@section('judul', 'Rincian Draf - ' . $namaBulan . ' ' . $tahun)
@section('keterangan', 'Rincian perhitungan draf yang sudah tersimpan')

@section('kembali')
    <x-back :href="route('draf-bulanan.index', ['tahun' => $tahun])">Kembali ke Draf Pajak Penghasilan Bulanan</x-back>
@endsection

@section('isi')
    <div class="flex items-center justify-between">
        <x-button varian="secondary" :href="route('laporan.draf-bulanan', ['tahun' => $tahun, 'bulan' => $bulan])" class="mb-5">
            <x-icon name="file-text" :size="16" /> Lihat Laporan
        </x-button>
    </div>

    @include('draf-bulanan._perhitungan')

    <x-info varian="biru-muda" class="mt-4">
        Draf ini terkunci. Data penghasilan {{ $namaBulan }} hanya dapat diubah setelah draf dibatalkan.
    </x-info>
@endsection
