@extends('layouts.app')
@section('judul', 'Tambah Harta - ' . $info['nama'])
@section('keterangan', $info['keterangan_isi'])

@section('kembali')
    <x-back :href="route('harta.index', $kategori)">Kembali ke Data Harta</x-back>
@endsection

@section('isi')
    <form method="POST" action="{{ route('harta.store', $kategori) }}">
        @csrf
        @include('harta._form', ['tombol' => 'Simpan'])
    </form>
@endsection
