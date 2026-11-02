@extends('layouts.app')
@section('judul', 'Susun Draf - ' . $namaBulan . ' ' . $tahun)
@section('keterangan', 'Periksa perhitungan sebelum menyimpan draf')

@section('kembali')
    <x-back :href="route('draf-bulanan.index', ['tahun' => $tahun])">Kembali ke Draf Pajak Penghasilan Bulanan</x-back>
@endsection

@section('isi')
    @include('draf-bulanan._perhitungan')

    <x-info varian="kuning" class="mt-4">
        Batas setor {{ tanggal_id($batasSetor) }}. Setelah draf disimpan, data penghasilan {{ $namaBulan }} tidak dapat diubah kecuali draf dibatalkan.
    </x-info>

    {{-- Tombol rata kiri, sama seperti formulir lain. --}}
    <form method="POST" action="{{ route('draf-bulanan.store') }}" id="formulir-draf-bulanan"
          data-konfirmasi="simpan-draf-bulanan" class="mt-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
        @csrf
        <input type="hidden" name="bulan" value="{{ $bulan }}">
        <input type="hidden" name="tahun" value="{{ $tahun }}">
        <x-button type="submit" class="w-full sm:w-auto sm:min-w-39">Simpan</x-button>
        {{-- Tetap berupa tautan, bukan tombol biasa: bila Alpine gagal dimuat,
             Batal masih membawa pengguna kembali ke daftar. --}}
        <x-button varian="secondary" :href="route('draf-bulanan.index', ['tahun' => $tahun])" class="w-full sm:w-auto sm:min-w-39"
            x-data @click.prevent="$dispatch('buka-dialog', 'batal-susun')">Batal</x-button>
    </form>

    {{-- Di halaman ini tidak ada isian yang hilang; yang ditinggalkan adalah
         pemeriksaan perhitungannya, jadi kalimatnya pun menyebut itu. --}}
    @include('partials.dialog-batal', [
        'id' => 'batal-susun',
        'href' => route('draf-bulanan.index', ['tahun' => $tahun]),
        'judul' => 'Batalkan penyusunan draf?',
        'pesan' => '',
        'tombol' => 'Ya, Batalkan',
        'lanjut' => 'Lanjut Memeriksa',
    ])

    {{-- Angkanya dihitung server, bukan diketik pengguna, jadi dikirim sebagai
         teks jadi lewat 'nilai' dan bukan dibaca dari kolom isian. --}}
    @include('partials.dialog-simpan', [
        'id' => 'simpan-draf-bulanan',
        'form' => 'formulir-draf-bulanan',
        'judul' => 'Simpan draf ' . $namaBulan . ' ' . $tahun . '?',
        'pesan' => 'Menyimpan draf mengunci data penghasilan ' . $namaBulan . ' ' . $tahun . '. Transaksi bulan itu tidak dapat ditambah, diubah, maupun dihapus kecuali drafnya dibatalkan.',
        'nilai' => [
            'Masa Pajak' => $namaBulan . ' ' . $tahun,
            'Peredaran Bruto' => rupiah($brutoBulanIni),
            'PPh Final Terutang' => rupiah($pphFinal),
            'Batas Setor' => tanggal_id($batasSetor),
        ],
    ])
@endsection
