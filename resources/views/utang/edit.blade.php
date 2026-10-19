@extends('layouts.app')
@section('judul', $utang['terkunci'] ? 'Perbarui Saldo Utang' : 'Ubah Utang')
@section('keterangan', $utang['terkunci']
    ? 'Perbarui saldo utang yang sudah masuk draf pajak tahunan'
    : 'Perbarui data utang yang sudah tercatat')

@section('kembali')
    <x-back :href="route('utang.index')">Kembali ke Data Utang</x-back>
@endsection

@section('isi')
    <form method="POST" action="{{ route('utang.update', $utang['id']) }}">
        @csrf
        @method('PUT')
        {{-- Baris yang sudah masuk draf tahunan memakai formulir khusus saldo. --}}
        @include($utang['terkunci'] ? 'utang._form-nilai' : 'utang._form', [
            'tombol' => $utang['terkunci'] ? 'Perbarui Saldo' : 'Simpan Perubahan',
        ])
    </form>
@endsection
