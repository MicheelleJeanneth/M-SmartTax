@extends('layouts.app')
@section('judul', 'Susun Draf ' . $namaBulan . ' ' . $tahun)
@section('keterangan', 'Periksa rincian dan perhitungan sebelum menyimpan draf.')

@section('isi')
    <x-back :href="route('draf-bulanan.index')">Kembali ke Draf Bulanan</x-back>

    @include('draf-bulanan._perhitungan')

    <x-info varian="kuning" class="mt-6">
        Setelah draf disimpan, data penghasilan {{ $namaBulan }} tidak dapat diubah kecuali draf dibatalkan.
    </x-info>

    <form method="POST" action="{{ route('draf-bulanan.store') }}" class="mt-6 flex justify-end gap-3">
        @csrf
        <input type="hidden" name="bulan" value="{{ $bulan }}">
        <x-button varian="secondary" :href="route('draf-bulanan.index')">Batal</x-button>
        <x-button type="submit">{{ $nihil ? 'Simpan Draf Nihil' : 'Simpan Draf' }}</x-button>
    </form>
@endsection
