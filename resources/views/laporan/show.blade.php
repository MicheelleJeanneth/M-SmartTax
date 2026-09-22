@extends('layouts.app')
@section('judul', $judul)
@section('keterangan', 'Pratinjau laporan sebelum diunduh sebagai PDF.')

@php
    $kePenyusunan = [
        'draf-bulanan' => [route('draf-bulanan.index'), 'Buka Draf Bulanan'],
        'draf-tahunan' => [route('draf-tahunan.index'), 'Buka Draf Tahunan'],
        'harta' => [route('harta.index'), 'Buka Data Harta'],
        'utang' => [route('utang.index'), 'Buka Data Utang'],
    ][$jenis];
    $query = array_filter(['tahun' => $tahun, 'bulan' => $jenis === 'draf-bulanan' ? $bulan : null]);
@endphp

@section('isi')
    <form method="GET" class="mb-6 flex flex-wrap items-end gap-4">
        <div class="w-40">
            <x-select label="Tahun" name="tahun" :pilihan="[2026 => '2026', 2025 => '2025', 2024 => '2024']" :terpilih="$tahun" :kosong="false" rapat onchange="this.form.submit()" />
        </div>
        @if($jenis === 'draf-bulanan')
            <div class="w-48">
                <x-select label="Bulan" name="bulan" :pilihan="\App\Support\MockData::bulan()" :terpilih="$bulan" :kosong="false" rapat onchange="this.form.submit()" />
            </div>
        @endif
        <noscript><x-button type="submit" varian="secondary">Tampilkan</x-button></noscript>
        @if($tersedia)
            <x-button :href="route('laporan.unduh', ['jenis' => $jenis, ...$query])" class="ml-auto">
                <x-icon name="download" :size="18" /> Unduh PDF
            </x-button>
        @endif
    </form>

    @if($tersedia)
        {{-- Pratinjau: dua lembar kertas bertumpuk di dalam bingkai frame --}}
        <div class="rounded-card bg-frame px-6 py-10">
            <div class="relative mx-auto w-full max-w-[794px]">
                <div class="absolute inset-0 translate-x-3 translate-y-3 rounded-sm bg-white shadow-sm" aria-hidden="true"></div>
                <iframe src="{{ route('laporan.pratinjau', ['jenis' => $jenis, ...$query]) }}" title="Pratinjau {{ $judul }}"
                    class="relative block h-[1123px] w-full rounded-sm bg-white shadow-md"></iframe>
            </div>
        </div>
    @else
        <x-empty ikon="file-text" :judul="$jenis === 'draf-tahunan' ? 'Draf tahunan ' . $tahun . ' belum tersusun' : 'Draf ' . (\App\Support\MockData::bulan()[$bulan] ?? '') . ' ' . $tahun . ' belum tersusun'"
            :tombol="$kePenyusunan[1]" :href="$kePenyusunan[0]">
            Laporan dapat diunduh setelah draf pada periode ini disusun.
        </x-empty>
    @endif
@endsection
