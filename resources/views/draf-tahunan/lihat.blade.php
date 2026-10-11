@extends('layouts.app')
@section('judul', 'Draf Tahunan ' . $tahun)
@section('keterangan', 'Rincian draf SPT Tahunan yang sudah tersimpan')

@section('kembali')
    <x-back :href="route('draf-tahunan.index')">Kembali ke Draf Pajak Penghasilan Tahunan</x-back>
@endsection

@section('aksi-header')
    <x-button varian="secondary" :href="route('laporan.draf-tahunan', ['tahun' => $tahun])">
        <x-icon name="file-text" :size="16" /> Lihat Laporan
    </x-button>
@endsection

@section('isi')
    @include('draf-tahunan._isi')

    <x-info varian="kuning" class="mt-4">
        Draf bulanan, data harta, dan data utang tahun {{ $tahun }} terkunci. Batalkan draf tahunan dari halaman daftar untuk mengubahnya.
    </x-info>
@endsection
