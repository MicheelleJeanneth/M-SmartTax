@extends('layouts.app')
@section('judul', 'Susun Draf Tahunan ' . $tahun)
@section('keterangan', 'Rekap dua belas draf bulanan beserta lampiran harta dan utang.')

@section('isi')
    <x-back :href="route('draf-tahunan.index')">Kembali ke Draf Tahunan</x-back>

    @include('draf-tahunan._isi')

    <x-info varian="kuning" class="mt-6" judul="Periksa sebelum menyimpan">
        @if($statusKonsistensi !== 'normal')
            Konsistensi harta perlu diperhatikan. Pastikan harta yang diperoleh dari hibah, warisan, atau penghasilan di luar usaha sudah dicatat.
        @endif
        Setelah disimpan, draf bulanan, data harta, dan data utang tahun {{ $tahun }} terkunci.
    </x-info>

    <form method="POST" action="{{ route('draf-tahunan.store') }}" class="mt-6 flex justify-end gap-3">
        @csrf
        <input type="hidden" name="tahun" value="{{ $tahun }}">
        <x-button varian="secondary" :href="route('draf-tahunan.index')">Batal</x-button>
        <x-button type="submit">Simpan Draf</x-button>
    </form>
@endsection
