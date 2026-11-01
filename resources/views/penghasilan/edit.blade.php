@extends('layouts.app')
@section('judul', 'Ubah Penghasilan')
@section('keterangan', 'Perubahan hanya dapat dilakukan sebelum data masuk draf bulanan')

@section('kembali')
    <x-back :href="route('penghasilan.index')">Kembali ke Data Penghasilan</x-back>
@endsection

@section('isi')
    <form method="POST" action="{{ route('penghasilan.update', $penghasilan['id']) }}"
          id="formulir-penghasilan" data-konfirmasi="simpan-penghasilan">
        @csrf
        @method('PUT')
        @include('penghasilan._form', ['tombol' => 'Simpan'])
    </form>

    @include('partials.dialog-simpan', [
        'id' => 'simpan-penghasilan',
        'form' => 'formulir-penghasilan',
        'judul' => 'Simpan perubahan data penghasilan?',
        'pesan' => 'Data yang tersimpan akan diganti dengan isi berikut. Setelah draf tersusun, maka data tidak dapat diubah maupun dihapus lagi.',
        'rincian' => ['Tanggal' => 'tanggal', 'Nominal' => 'nominal', 'Keterangan' => 'keterangan'],
    ])
@endsection
