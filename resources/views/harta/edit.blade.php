@extends('layouts.app')
@section('judul', 'Ubah Harta')
@section('keterangan', 'Kategori: ' . $info['nama'])

@section('kembali')
    <x-back :href="route('harta.show', [$kategori, $item['id']])">Kembali ke detail harta</x-back>
@endsection

@section('isi')
    <form method="POST" action="{{ route('harta.update', [$kategori, $item['id']]) }}">
        @csrf
        @method('PUT')
        @include('harta._form', ['tombol' => 'Simpan Perubahan'])
    </form>
@endsection
