@extends('layouts.app')
@section('judul', ($item['terkunci'] ? 'Perbarui Nilai - ' : 'Ubah Harta - ') . $info['nama'])
@section('keterangan', $item['terkunci']
    ? 'Perbarui nilai harta yang sudah masuk draf pajak tahunan'
    : $info['keterangan_ubah'])

@section('kembali')
    <x-back :href="route('harta.index', $kategori)">Kembali ke Data Harta</x-back>
@endsection

@section('isi')
    <form method="POST" action="{{ route('harta.update', [$kategori, $item['id']]) }}">
        @csrf
        @method('PUT')
        {{-- Baris yang sudah masuk draf tahunan memakai formulir khusus nilai. --}}
        @include($item['terkunci'] ? 'harta._form-nilai' : 'harta._form', [
            'tombol' => $item['terkunci'] ? 'Perbarui Nilai' : 'Simpan Perubahan',
        ])
    </form>
@endsection
