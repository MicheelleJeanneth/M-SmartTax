@extends('layouts.app')
@section('judul', 'Tambah Utang')
@section('keterangan', 'Masukkan utang yang masih berjalan.')

@section('isi')
    <x-back :href="route('utang.index')">Kembali ke Data Utang</x-back>
    <form method="POST" action="{{ route('utang.store') }}">
        @csrf
        @include('utang._form', ['tombol' => 'Simpan Utang'])
    </form>
@endsection
