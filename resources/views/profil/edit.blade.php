@extends('layouts.app')
@section('judul', 'Ubah Profil')
@section('keterangan', 'Perbarui data wajib pajak dan data usaha')

@section('kembali')
    <x-back :href="route('profil.show')">Kembali ke Profil</x-back>
@endsection

@section('isi')
    {{-- NIK tercetak pada setiap dokumen laporan, jadi perubahannya ditegaskan
         lebih dulu lewat dialog. Kolomnya sendiri tidak dikunci supaya salah
         ketik tetap dapat diperbaiki. --}}
    <form id="form-profil" method="POST" action="{{ route('profil.update') }}"
        x-data="{ nikAwal: '{{ $profil['nik'] }}', ditegaskan: false }"
        @submit="if (! ditegaskan && $refs.nik.value !== nikAwal) { $event.preventDefault(); $dispatch('buka-dialog', 'ubah-nik'); }"
        @nik-ditegaskan.window="ditegaskan = true; $el.requestSubmit()">
        @csrf
        @method('PUT')

        @include('profil._form-data-diri', ['profil' => $profil, 'terkunci' => true])

        <x-info varian="biru-muda" class="mt-4">
            Perubahan berlaku pada dokumen laporan yang dicetak setelah ini. Dokumen yang sudah diunduh sebelumnya tidak berubah.
        </x-info>

        {{-- Tombol rata kiri, sama seperti formulir lain. --}}
        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
            <x-button type="submit" class="w-full sm:w-auto sm:min-w-48">Simpan Perubahan</x-button>
            <x-button varian="secondary" :href="route('profil.show')" class="w-full sm:w-auto sm:min-w-48">Batal</x-button>
        </div>
    </form>

    <x-dialog id="ubah-nik" judul="Ubah NIK?">
        <p>NIK tercetak pada seluruh dokumen laporan dan menjadi penanda data Anda di Direktorat Jenderal Pajak.</p>
        <div class="mt-4 rounded-field bg-warn-bg px-4 py-3.5 text-sm text-warn-ink">
            <p>Pastikan NIK yang baru sesuai kartu tanda penduduk. Dokumen laporan yang sudah diunduh sebelumnya tidak ikut berubah.</p>
        </div>
        <x-slot:aksi>
            <x-button @click="buka = false; $dispatch('nik-ditegaskan')">Ya, Ubah NIK</x-button>
        </x-slot:aksi>
    </x-dialog>
@endsection
