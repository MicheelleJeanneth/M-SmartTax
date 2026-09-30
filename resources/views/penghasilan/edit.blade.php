@extends('layouts.app')
@section('judul', 'Ubah Penghasilan')
@section('keterangan', 'Perubahan hanya dapat dilakukan sebelum data masuk draf bulanan')

@section('kembali')
    <x-back :href="route('penghasilan.index')">Kembali ke Data Penghasilan</x-back>
@endsection

@section('isi')
    <form method="POST" action="{{ route('penghasilan.update', $penghasilan['id']) }}">
        @csrf
        @method('PUT')
        @include('penghasilan._form', ['tombol' => 'Simpan Perubahan'])
    </form>
@endsection
