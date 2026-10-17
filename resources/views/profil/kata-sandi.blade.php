@extends('layouts.app')
@section('judul', 'Ubah Kata Sandi')
@section('keterangan', 'Perbarui kata sandi yang dipakai untuk masuk ke M-SmartTax')

@section('kembali')
    <x-back :href="route('profil.show')">Kembali ke Profil</x-back>
@endsection

@section('isi')
    <form method="POST" action="{{ route('profil.kata-sandi.update') }}">
        @csrf
        @method('PUT')

        <x-card judul="Kata Sandi">
            <x-input-password label="Kata Sandi Saat Ini" name="kata_sandi_lama" autocomplete="current-password"
                placeholder="Kata sandi yang Anda pakai sekarang" />

            <div class="grid grid-cols-2 gap-x-4">
                <x-input-password label="Kata Sandi Baru" name="kata_sandi" autocomplete="new-password"
                    placeholder="Minimal 8 karakter" bantuan="Gunakan kombinasi huruf dan angka." />
                <x-input-password label="Ulangi Kata Sandi Baru" name="kata_sandi_confirmation" autocomplete="new-password"
                    placeholder="Ketik ulang kata sandi baru" />
            </div>
        </x-card>

        <x-info varian="biru-muda" class="mt-4">
            Setelah kata sandi berubah, gunakan kata sandi baru pada setiap masuk berikutnya. Data penghasilan, harta, dan utang Anda tidak terpengaruh.
        </x-info>

        {{-- Tombol rata kiri, sama seperti formulir lain. --}}
        <div class="mt-4 flex flex-wrap gap-3">
            <x-button type="submit" class="min-w-48">Simpan Kata Sandi</x-button>
            <x-button varian="secondary" :href="route('profil.show')" class="min-w-48">Batal</x-button>
        </div>
    </form>
@endsection
