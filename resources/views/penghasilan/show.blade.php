@extends('layouts.app')
@section('judul', 'Rincian Penghasilan')
@section('keterangan', tanggal_id($penghasilan['tanggal']))

@section('kembali')
    <x-back :href="route('penghasilan.index')">Kembali ke Data Penghasilan</x-back>
@endsection

@section('isi')
    {{-- Halaman ini hanya untuk melihat. Mengubah dan menghapus dilakukan dari tabel Data Penghasilan. --}}
    <x-card judul="Rincian">
        <div class="divide-y divide-line-soft">
            <x-row label="Tanggal">{{ tanggal_id($penghasilan['tanggal']) }}</x-row>
            <x-row label="Keterangan">{{ $penghasilan['keterangan'] }}</x-row>
            <x-row label="Status">{{ $penghasilan['terkunci'] ? 'Terkunci' : 'Aktif' }}</x-row>
            <x-row label="Nominal" tebal>{{ rupiah($penghasilan['nominal']) }}</x-row>
        </div>
    </x-card>

    @if($penghasilan['terkunci'])
        <x-info varian="abu" class="mt-4">
            Penghasilan ini sudah masuk draf pajak bulanan. Batalkan draf bulan tersebut terlebih dahulu untuk mengubahnya.
        </x-info>
    @endif
@endsection
