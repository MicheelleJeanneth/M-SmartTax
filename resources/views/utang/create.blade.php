@extends('layouts.app')
@section('judul', 'Tambah Utang')
@section('keterangan', 'Isi data utang yang masih berjalan')

@section('kembali')
    <x-back :href="route('utang.index')">Kembali ke Data Utang</x-back>
@endsection

@section('isi')
    <form method="POST" action="{{ route('utang.store') }}"
          id="formulir-utang" data-konfirmasi="simpan-utang">
        @csrf
        @include('utang._form', ['tombol' => 'Simpan'])
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

    {{-- Yang diringkas adalah kolom yang menentukan isi lampiran draf tahunan:
         krediturnya, saldonya, dan rentang tahun utang ini berjalan. --}}
    @include('partials.dialog-simpan', [
        'id' => 'simpan-utang',
        'form' => 'formulir-utang',
        'judul' => 'Simpan data utang?',
        'pesan' => 'Utang ini ikut tercantum pada lampiran draf pajak tahunan. Setelah draf tersusun, maka hanya saldonya yang masih dapat diperbarui.',
        'rincian' => [
            'Kode Utang' => 'kode',
            'Nama Kreditur' => 'nama_kreditur',
            'Saldo Utang' => 'saldo',
            'Tahun Peminjaman' => 'tahun_peminjaman',
            'Tahun Pelunasan' => 'tahun_pelunasan',
        ],
    ])
@endsection
