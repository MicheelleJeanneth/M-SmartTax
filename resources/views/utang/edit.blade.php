@extends('layouts.app')
@section('judul', 'Ubah Utang')
@section('keterangan', 'Perubahan hanya dapat dilakukan sebelum data masuk draf tahunan.')

@section('isi')
    <x-back :href="route('utang.show', $utang['id'])">Kembali ke detail utang</x-back>
    <form method="POST" action="{{ route('utang.update', $utang['id']) }}">
        @csrf
        @method('PUT')
        @include('utang._form', ['tombol' => 'Simpan Perubahan'])
    </form>
@endsection
