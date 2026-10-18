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
    {{-- Penyaring dan tombol unduh berdiri di dalam satu kartu; kolom tahun
         melebar mengisi sisa baris, tombol tetap di ujung kanan. --}}
    <x-card class="mb-6">
        <form method="GET" class="flex flex-wrap items-end gap-4">
            <div class="min-w-50 flex-1">
                <x-select label="Tahun Pajak" name="tahun" :pilihan="collect($daftarTahun)->mapWithKeys(fn ($t) => [$t => $t])" :terpilih="$tahun" :kosong="false" rapat onchange="this.form.submit()" />
            </div>
            @if($jenis === 'draf-bulanan')
                <div class="min-w-50 flex-1">
                    <x-select label="Bulan" name="bulan" :pilihan="\App\Support\MockData::bulan()" :terpilih="$bulan" :kosong="false" rapat onchange="this.form.submit()" />
                </div>
            @endif
            <noscript><x-button type="submit" varian="secondary">Tampilkan</x-button></noscript>
            @if($tersedia)
                <x-button :href="route('laporan.unduh', ['jenis' => $jenis, ...$query])" class="min-w-55">
                    <x-icon name="download" :size="18" /> Unduh PDF
                </x-button>
            @endif
        </form>
    </x-card>

    @if($tersedia)
        {{-- Pratinjau: dua lembar kertas bertumpuk di dalam bingkai frame --}}
        <div class="daftar-gulir overflow-x-auto rounded-card bg-frame px-3 py-5 sm:px-6 sm:py-10">
            <div class="relative mx-auto w-[794px] max-w-full">
                <div class="absolute inset-0 translate-x-3 translate-y-3 rounded-sm bg-white shadow-sm" aria-hidden="true"></div>
                <iframe src="{{ route('laporan.pratinjau', ['jenis' => $jenis, ...$query]) }}" title="Pratinjau {{ $judul }}"
                    class="relative block h-[60vh] w-full rounded-sm bg-white shadow-md sm:h-[1123px]"></iframe>
            </div>
        </div>
    @else
        <x-empty ikon="file-text" :judul="$jenis === 'draf-tahunan' ? 'Draf tahunan ' . $tahun . ' belum tersusun' : 'Draf ' . (\App\Support\MockData::bulan()[$bulan] ?? '') . ' ' . $tahun . ' belum tersusun'"
            :tombol="$kePenyusunan[1]" :href="$kePenyusunan[0]">
            Laporan dapat diunduh setelah draf pada periode ini disusun.
        </x-empty>
    @endif
@endsection
