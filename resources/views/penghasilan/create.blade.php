@extends('layouts.app')
@section('judul', 'Tambah Penghasilan')
@section('keterangan', 'Isi data penerimaan usaha yang belum tercatat')

@section('kembali')
    <x-back :href="route('penghasilan.index')">Kembali ke Data Penghasilan</x-back>
@endsection

@section('isi')
    <form method="POST" action="{{ route('penghasilan.store') }}"
          id="formulir-penghasilan" data-konfirmasi="simpan-penghasilan">
        @csrf
        @include('penghasilan._form', ['tombol' => 'Simpan'])
    </form>

    @include('partials.dialog-simpan', [
        'id' => 'simpan-penghasilan',
        'form' => 'formulir-penghasilan',
        'judul' => 'Simpan data penghasilan?',
        'pesan' => 'Nominal ini ikut dihitung pada draf pajak bulan tersebut. Setelah draf tersusun, maka data tidak dapat diubah maupun dihapus lagi.',
        'rincian' => ['Tanggal' => 'tanggal', 'Nominal' => 'nominal', 'Keterangan' => 'keterangan'],
    ])

    <x-info varian="biru-muda" class="mt-4">
        Data yang sudah masuk draf pajak bulanan tidak dapat diubah. Pastikan tanggal dan nominalnya benar sebelum menyimpan.
    </x-info>
@endsection
