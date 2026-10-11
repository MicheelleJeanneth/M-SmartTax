@extends('layouts.app')
@section('judul', 'Susun Draf Tahunan ' . $tahun)
@section('keterangan', 'Periksa rekap dan lampiran sebelum menyimpan draf')

@section('kembali')
    <x-back :href="route('draf-tahunan.index')">Kembali ke Draf Pajak Penghasilan Tahunan</x-back>
@endsection

@section('isi')
    @include('draf-tahunan._isi')

    <x-info varian="kuning" class="mt-4">
        @if($statusKonsistensi !== 'normal')
            Konsistensi harta perlu diperhatikan. Pastikan harta yang diperoleh dari hibah, warisan, atau penghasilan di luar usaha sudah dicatat.
        @endif
        Batas lapor 31 Maret {{ $tahun + 1 }}. Setelah draf disimpan, draf bulanan, data harta, dan data utang tahun {{ $tahun }} tidak dapat diubah kecuali draf dibatalkan.
    </x-info>

    {{-- Tombol rata kiri, sama seperti formulir lain. --}}
    <form method="POST" action="{{ route('draf-tahunan.store') }}" class="mt-4 flex flex-wrap gap-3">
        @csrf
        <input type="hidden" name="tahun" value="{{ $tahun }}">
        <x-button type="submit" class="min-w-39">Simpan Draf</x-button>
        <x-button varian="secondary" :href="route('draf-tahunan.index')" class="min-w-39">Batal</x-button>
    </form>
@endsection
