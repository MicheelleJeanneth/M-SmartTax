@extends('layouts.app')
@section('judul', 'Tambah Harta - ' . $info['nama'])
@section('keterangan', $info['keterangan_isi'])

@section('kembali')
    <x-back :href="route('harta.index', $kategori)">Kembali ke Data Harta</x-back>
@endsection

@section('isi')
    <form method="POST" action="{{ route('harta.store', $kategori) }}"
          id="formulir-harta" data-konfirmasi="simpan-harta">
        @csrf
        @include('harta._form', ['tombol' => 'Simpan'])
    </form>

    {{-- Yang diringkas hanya kode, nilai, dan tahun: ketiganya yang menentukan
         lampiran draf tahun mana yang memuat baris ini. --}}
    {{-- Dialognya di tingkat halaman, bukan di dalam _form: @include mewarisi
         cakupan pemanggilnya, dan di sana $tombol sudah dipakai formulir. --}}
    @include('partials.dialog-batal', [
        'id' => 'batal-harta',
        'href' => route('harta.index', $kategori),
        'judul' => 'Batalkan pengisian?',
        'pesan' => '',
        'tombol' => 'Ya, Batalkan',
        'lanjut' => 'Lanjut Mengisi',
    ])

    @include('partials.dialog-simpan', [
        'id' => 'simpan-harta',
        'form' => 'formulir-harta',
        'judul' => 'Simpan data harta?',
        'pesan' => 'Harta ini ikut tercantum pada lampiran draf pajak tahunan. Setelah draf tersusun, maka kode, tahun, dan rinciannya tidak dapat diubah lagi.',
        'rincian' => array_filter([
            'Kode Harta' => 'kode',
            $info['label_nilai'] => 'nilai',
            $info['label_nilai_kini'] => $info['label_nilai_kini'] ? 'nilai_kini' : null,
            $info['label_tahun'] => 'tahun',
        ]),
    ])
@endsection
