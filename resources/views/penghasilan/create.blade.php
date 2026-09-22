@extends('layouts.app')
@section('judul', 'Tambah Penghasilan')
@section('keterangan', 'Masukkan penghasilan bruto dari kegiatan usaha.')

@section('isi')
    <x-back :href="route('penghasilan.index')">Kembali ke Data Penghasilan</x-back>
    <form method="POST" action="{{ route('penghasilan.store') }}">
        @csrf
        @include('penghasilan._form', ['tombol' => 'Simpan Penghasilan'])
    </form>
@endsection
