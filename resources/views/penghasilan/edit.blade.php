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

    {{-- Dialognya di sini, bukan di dalam _form.blade.php: @include mewarisi
         cakupan pemanggilnya, dan di sana $tombol sudah dipakai formulir. --}}
    @include('partials.dialog-batal', [
        'id' => 'batal-penghasilan',
        'href' => route('penghasilan.index'),
        'judul' => 'Batalkan pengisian?',
        'pesan' => '',
        'tombol' => 'Ya, Batalkan',
        'lanjut' => 'Lanjut Mengisi',
    ])

    @include('partials.dialog-simpan', [
        'id' => 'simpan-penghasilan',
        'form' => 'formulir-penghasilan',
        'judul' => 'Simpan perubahan data penghasilan?',
        'pesan' => 'Data yang tersimpan akan diganti dengan isi berikut. Setelah draf tersusun, maka data tidak dapat diubah maupun dihapus lagi.',
        'rincian' => ['Tanggal' => 'tanggal', 'Nominal' => 'nominal', 'Keterangan' => 'keterangan'],
    ])
@endsection
