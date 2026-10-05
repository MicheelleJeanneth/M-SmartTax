@extends('layouts.app')
@section('judul', 'Detail Harta - ' . $info['nama'])
@section('keterangan', 'Rincian data ' . strtolower($info['nama']) . ' yang tercatat')

@section('kembali')
    <x-back :href="route('harta.index', $kategori)">Kembali ke Data Harta</x-back>
@endsection

@section('isi')
    {{-- Halaman ini hanya untuk melihat. Mengubah dan menghapus dilakukan dari tabel Data Harta. --}}
    <x-card>
        <h2 class="mb-4 text-base font-medium text-subjudul">Data Harta</h2>

        <div class="grid grid-cols-2 gap-x-6">
            <x-kolom-baca label="Kode Harta">{{ $item['kode'] }}</x-kolom-baca>
            <x-kolom-baca label="Deskripsi">{{ $item['nama'] }}</x-kolom-baca>
        </div>

        <x-kolom-baca label="Keterangan">{{ $item['keterangan'] ?? '—' }}</x-kolom-baca>

        <x-kolom-baca :label="$info['label_nilai']">{{ rupiah($item['nilai']) }}</x-kolom-baca>

        <div class="grid grid-cols-2 gap-x-6">
            <x-kolom-baca label="Tahun Perolehan">{{ $item['tahun'] }}</x-kolom-baca>
            <x-kolom-baca label="Tahun Pelepasan">{{ $item['tahun_pelepasan'] }}</x-kolom-baca>
        </div>

        {{-- Garis pemisah memakai jarak yang sama dengan antar kotak: 20px di atas dan di bawah. --}}
        <div class="mb-5 border-t border-line-soft"></div>

        <h2 class="mb-4 text-base font-medium text-subjudul">{{ $info['judul_rincian'] }}</h2>

        <div class="grid grid-cols-2 gap-x-6">
            @foreach($info['kolom'] as $i => $kolom)
                <x-kolom-baca :label="$kolom">{{ $item['khas'][$i] }}</x-kolom-baca>
            @endforeach
            <x-kolom-baca label="Lokasi / Negara">{{ $item['negara'] }}</x-kolom-baca>
        </div>
    </x-card>
@endsection
