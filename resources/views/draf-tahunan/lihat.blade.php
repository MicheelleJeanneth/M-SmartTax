@extends('layouts.app')
@section('judul', 'Draf Tahunan ' . $tahun)
@section('keterangan', 'Draf SPT Tahunan tersusun.')

@section('isi')
    <div class="flex items-center justify-between">
        <x-back :href="route('draf-tahunan.index')">Kembali ke Draf Tahunan</x-back>
        <x-button varian="secondary" :href="route('laporan.draf-tahunan', ['tahun' => $tahun])" class="mb-5">
            <x-icon name="file-text" :size="16" /> Lihat Laporan
        </x-button>
    </div>

    @include('draf-tahunan._isi', ['ringkas' => true])

    <x-info varian="abu" class="mt-6">
        Draf bulanan, data harta, dan data utang tahun {{ $tahun }} terkunci. Batalkan draf tahunan dari halaman daftar untuk mengubahnya.
    </x-info>
@endsection
