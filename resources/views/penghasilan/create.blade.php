@extends('layouts.app')
@section('judul', 'Tambah Penghasilan')
@section('keterangan', 'Isi data penerimaan usaha yang belum tercatat')

@section('kembali')
    <x-back :href="route('penghasilan.index')">Kembali ke Data Penghasilan</x-back>
@endsection

@section('isi')
    <form method="POST" action="{{ route('penghasilan.store') }}">
        @csrf
        @include('penghasilan._form', ['tombol' => 'Simpan'])
    </form>
@endsection
