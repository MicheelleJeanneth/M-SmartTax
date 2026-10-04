@extends('layouts.app')
@section('judul', 'Detail Penghasilan')
@section('keterangan', 'Rincian data penghasilan yang tercatat')

@section('kembali')
    <x-back :href="route('penghasilan.index')">Kembali ke Data Penghasilan</x-back>
@endsection

@section('isi')
    {{-- Halaman ini hanya untuk melihat. Mengubah dan menghapus dilakukan dari tabel Data Penghasilan. --}}
    <x-card>
        <x-kolom-baca label="Tanggal">{{ tanggal_id($penghasilan['tanggal']) }}</x-kolom-baca>
        <x-kolom-baca label="Nominal">{{ rupiah($penghasilan['nominal']) }}</x-kolom-baca>
        <x-kolom-baca label="Keterangan">{{ $penghasilan['keterangan'] }}</x-kolom-baca>
    </x-card>
@endsection
