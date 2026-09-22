@extends('layouts.app')
@section('judul', 'Tambah Harta')
@section('keterangan', 'Kategori: ' . $info['nama'])

@section('isi')
    <x-back :href="route('harta.index', $kategori)">Kembali ke Data Harta</x-back>
    <form method="POST" action="{{ route('harta.store', $kategori) }}">
        @csrf
        @include('harta._form', ['tombol' => 'Simpan Harta'])
    </form>
@endsection
