@extends('layouts.app')
@section('judul', 'Perbarui Saldo Utang')
@section('keterangan', 'Perbarui saldo utang sesuai posisi akhir tahun pajak')

@section('kembali')
    <x-back :href="route('utang.index')">Kembali ke Data Utang</x-back>
@endsection

@section('isi')
    <form method="POST" action="{{ route('utang.update', $utang['id']) }}">
        @csrf
        @method('PUT')
        {{-- Satu formulir untuk semua: terkunci draf tahunan maupun tidak,
             yang dapat diubah hanya saldonya. --}}
        @include('utang._form-nilai', ['tombol' => 'Perbarui'])
    </form>
@endsection
