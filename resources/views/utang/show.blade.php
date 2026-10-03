@extends('layouts.app')
@section('judul', 'Detail Utang')
@section('keterangan', 'Rincian data utang yang tercatat')

@section('kembali')
    <x-back :href="route('utang.index')">Kembali ke Data Utang</x-back>
@endsection

@section('isi')
    {{-- Halaman ini hanya untuk melihat. Mengubah dan menghapus dilakukan dari tabel Data Utang. --}}
    <x-card>
        <h2 class="mb-4 text-base font-medium text-subjudul">Data Utang</h2>

        <div class="grid grid-cols-2 gap-x-6">
            <x-kolom-baca label="Kode Utang">{{ \App\Support\MockData::kodeUtang()[$utang['kode']] }}</x-kolom-baca>
            <x-kolom-baca label="Deskripsi Utang">{{ $utang['deskripsi'] }}</x-kolom-baca>
        </div>

        <div class="grid grid-cols-2 gap-x-6">
            <x-kolom-baca label="Cicilan Bulanan" bantuan="Kosongkan bila utang tidak diangsur secara berkala">
                {{ $utang['cicilan'] ? rupiah($utang['cicilan']) : 'Tidak diangsur berkala' }}
            </x-kolom-baca>
        </div>

        <x-kolom-baca label="Saldo Utang">{{ rupiah($utang['saldo']) }}</x-kolom-baca>

        <div class="grid grid-cols-2 gap-x-6">
            <x-kolom-baca label="Tahun Peminjaman">{{ $utang['tahun'] }}</x-kolom-baca>
            <x-kolom-baca label="Tahun Pelunasan">{{ $utang['tahun_pelunasan'] ?? 'Belum lunas' }}</x-kolom-baca>
        </div>
        <p class="-mt-3 mb-6 text-sm text-ink-3">
            Biarkan &ldquo;Belum lunas&rdquo; jika utang masih berjalan. Saldo tercatat per akhir tahun pajak, bukan nilai pinjaman awal
        </p>

        <div class="mb-6 border-t border-line-soft"></div>

        <h2 class="mb-4 text-base font-medium text-subjudul">Data Kreditur</h2>

        <x-kolom-baca label="Nama Kreditur">{{ $utang['kreditur'] }}</x-kolom-baca>

        <div class="grid grid-cols-2 gap-x-6">
            <x-kolom-baca label="NIK Kreditur" bantuan="NIK boleh dikosongkan jika kreditur berupa lembaga keuangan.">
                {{ $utang['nik_kreditur'] ?? '—' }}
            </x-kolom-baca>
            <x-kolom-baca label="Negara Kreditur">{{ $utang['negara'] }}</x-kolom-baca>
        </div>

        <div class="mb-6 border-t border-line-soft"></div>

        <x-kolom-baca label="Keterangan">{{ $utang['keterangan'] ?? '—' }}</x-kolom-baca>
    </x-card>

    @if($utang['terkunci'])
        <x-info varian="abu" class="mt-4">
            Utang ini sudah masuk draf pajak tahunan. Batalkan draf tahun tersebut terlebih dahulu untuk mengubahnya.
        </x-info>
    @endif
@endsection
