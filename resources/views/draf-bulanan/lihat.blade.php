@extends('layouts.app')
@section('judul', 'Draf ' . $namaBulan . ' ' . $tahun)
@section('keterangan', 'Draf tersusun. Data penghasilan bulan ini terkunci.')

@section('isi')
    <div class="flex items-center justify-between">
        <x-back :href="route('draf-bulanan.index')">Kembali ke Draf Bulanan</x-back>
        <x-button varian="secondary" :href="route('laporan.draf-bulanan', ['tahun' => $tahun, 'bulan' => $bulan])" class="mb-5">
            <x-icon name="file-text" :size="16" /> Lihat Laporan
        </x-button>
    </div>

    @include('draf-bulanan._perhitungan')

    <x-info varian="abu" class="mt-6">
        Draf ini terkunci. Data penghasilan {{ $namaBulan }} hanya dapat diubah setelah draf dibatalkan.
    </x-info>
@endsection
