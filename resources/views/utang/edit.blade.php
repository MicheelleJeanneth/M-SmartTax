@extends('layouts.app')
@section('judul', 'Ubah Utang')
@section('keterangan', 'Perubahan hanya dapat dilakukan sebelum data masuk draf tahunan')

@section('kembali')
    <x-back :href="route('utang.index')">Kembali ke Data Utang</x-back>
@endsection

@section('isi')
    <form method="POST" action="{{ route('utang.update', $utang['id']) }}">
        @csrf
        @method('PUT')
        @include('utang._form', ['tombol' => 'Simpan Perubahan'])
    </form>
@endsection
