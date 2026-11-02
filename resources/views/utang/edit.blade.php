@extends('layouts.app')
@section('judul', 'Perbarui Utang')
@section('keterangan', 'Perbarui saldo dan jadwal pelunasan utang')

@section('kembali')
    <x-back :href="route('utang.index')">Kembali ke Data Utang</x-back>
@endsection

@section('isi')
    <form method="POST" action="{{ route('utang.update', $utang['id']) }}"
          id="formulir-utang" data-konfirmasi="simpan-utang">
        @csrf
        @method('PUT')
        {{-- Satu formulir untuk semua: terkunci draf tahunan maupun tidak,
             yang dapat diubah hanya saldonya. --}}
        @include('utang._form-nilai', ['tombol' => 'Perbarui'])
    </form>

    {{-- Dialognya di tingkat halaman, bukan di dalam _form: @include mewarisi
         cakupan pemanggilnya, dan di sana $tombol sudah dipakai formulir. --}}
    @include('partials.dialog-batal', [
        'id' => 'batal-utang',
        'href' => route('utang.index'),
        'judul' => 'Batalkan pengisian?',
        'pesan' => '',
        'tombol' => 'Ya, Batalkan',
        'lanjut' => 'Lanjut Mengisi',
    ])

    {{-- Hanya saldo yang berganti, jadi hanya itu pula yang diringkas. --}}
    @include('partials.dialog-simpan', [
        'id' => 'simpan-utang',
        'form' => 'formulir-utang',
        'judul' => 'Perbarui saldo utang?',
        'pesan' => 'Nilai yang tersimpan akan diganti dengan isi berikut. Laporan tahun yang drafnya sudah tersusun tetap memakai salinannya sendiri.',
        'rincian' => ['Saldo Utang' => 'saldo', 'Tahun Pelunasan' => 'tahun_pelunasan'],
        'tombol' => 'Perbarui',
    ])
@endsection
