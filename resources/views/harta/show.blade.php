@extends('layouts.app')
@section('judul', 'Detail Harta - ' . $info['nama'])
@section('keterangan', 'Rincian data ' . strtolower($info['nama']) . ' yang tercatat')

@section('kembali')
    <x-back :href="route('harta.index', $kategori)">Kembali ke Data Harta</x-back>
@endsection

@php
    // Susunannya mengikuti formulir: bila jumlah kolom khas ditambah Lokasi ganjil,
    // kolom khas pertama dibuat selebar penuh supaya grid dua kolom terisi penuh.
    $khasPenuh = (count($info['kolom']) + 1) % 2 === 1;
@endphp

@section('isi')
    {{-- Halaman ini hanya untuk melihat. Mengubah dan menghapus dilakukan dari tabel Data Harta. --}}
    <x-card>
        <h2 class="mb-4 text-base font-medium text-subjudul">Data Harta</h2>

        {{-- Kode dan uraiannya tampil menyatu, sama seperti pilihan di formulir. --}}
        <x-kolom-baca label="Kode Harta">{{ $item['kode'] }} - {{ $item['nama'] }}</x-kolom-baca>

        <x-kolom-baca label="Keterangan">{{ $item['keterangan'] ?? '—' }}</x-kolom-baca>

        @if($info['label_nilai_kini'])
            <div class="grid grid-cols-2 gap-x-6">
                <x-kolom-baca :label="$info['label_nilai']">{{ rupiah($item['nilai']) }}</x-kolom-baca>
                <x-kolom-baca :label="$info['label_nilai_kini']">{{ rupiah($item['nilai_kini']) }}</x-kolom-baca>
            </div>
        @else
            <x-kolom-baca :label="$info['label_nilai']">{{ rupiah($item['nilai']) }}</x-kolom-baca>
        @endif

        <div class="grid grid-cols-2 gap-x-6">
            <x-kolom-baca :label="$info['label_tahun']">{{ $item['tahun'] }}</x-kolom-baca>
            <x-kolom-baca label="Tahun Pelepasan">{{ $item['tahun_pelepasan'] }}</x-kolom-baca>
        </div>

        {{-- Garis pemisah memakai jarak yang sama dengan antar kotak: 20px di atas dan di bawah. --}}
        <div class="mb-5 border-t border-line-soft"></div>

        <h2 class="mb-4 text-base font-medium text-subjudul">{{ $info['judul_rincian'] }}</h2>

        @if($khasPenuh)
            <x-kolom-baca :label="$info['kolom'][0]">{{ $item['khas'][0] }}</x-kolom-baca>
        @endif

        <div class="grid grid-cols-2 gap-x-6">
            @foreach($info['kolom'] as $i => $kolom)
                @continue($khasPenuh && $i === 0)
                <x-kolom-baca :label="$kolom">{{ $item['khas'][$i] ?? '—' }}</x-kolom-baca>
            @endforeach
            <x-kolom-baca :label="$info['label_lokasi']">{{ $item['negara'] }}</x-kolom-baca>
        </div>
    </x-card>
@endsection
