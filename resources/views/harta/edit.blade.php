@extends('layouts.app')
@section('judul', ($item['terkunci'] ? 'Perbarui Nilai - ' : 'Ubah Harta - ') . $info['nama'])
@section('keterangan', $item['terkunci']
    ? 'Perbarui nilai harta yang sudah masuk draf pajak tahunan'
    : $info['keterangan_ubah'])

@section('kembali')
    <x-back :href="route('harta.index', $kategori)">Kembali ke Data Harta</x-back>
@endsection

@section('isi')
    <form method="POST" action="{{ route('harta.update', [$kategori, $item['id']]) }}"
          id="formulir-harta" data-konfirmasi="simpan-harta">
        @csrf
        @method('PUT')
        {{-- Baris yang sudah masuk draf tahunan memakai formulir khusus nilai. --}}
        @include($item['terkunci'] ? 'harta._form-nilai' : 'harta._form', [
            'tombol' => $item['terkunci'] ? 'Perbarui Nilai' : 'Simpan',
        ])
    </form>

    {{-- Baris terkunci hanya dapat mengubah nilainya, jadi ringkasannya pun
         hanya memuat kolom yang benar-benar dapat diisi. --}}
    @include('partials.dialog-simpan', [
        'id' => 'simpan-harta',
        'form' => 'formulir-harta',
        'judul' => $item['terkunci'] ? 'Perbarui nilai harta?' : 'Simpan perubahan data harta?',
        'pesan' => $item['terkunci']
            ? 'Nilai yang tersimpan akan diganti dengan isi berikut. Laporan tahun yang sudah tersusun tetap memakai salinannya sendiri.'
            : 'Data yang tersimpan akan diganti dengan isi berikut. Setelah draf tersusun, maka kode, tahun, dan rinciannya tidak dapat diubah lagi.',
        'rincian' => array_filter([
            'Kode Harta' => $item['terkunci'] ? null : 'kode',
            $info['label_nilai'] => 'nilai',
            $info['label_nilai_kini'] => $info['label_nilai_kini'] ? 'nilai_kini' : null,
            $info['label_tahun'] => $item['terkunci'] ? null : 'tahun',
        ]),
        'tombol' => $item['terkunci'] ? 'Perbarui Nilai' : 'Simpan',
    ])
@endsection
