@extends('layouts.app')
@section('judul', 'Ubah Harta - ' . $info['nama'])
@section('keterangan', 'Perbarui data ' . strtolower($info['nama']) . ' yang sudah tercatat')

@section('kembali')
    <x-back :href="route('harta.index', $kategori)">Kembali ke Data Harta</x-back>
@endsection

@section('isi')
    <form method="POST" action="{{ route('harta.update', [$kategori, $item['id']]) }}">
        @csrf
        @method('PUT')
        @include('harta._form', ['tombol' => 'Simpan Perubahan'])
    </form>
@endsection
