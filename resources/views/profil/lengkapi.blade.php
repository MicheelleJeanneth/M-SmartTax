@extends('layouts.center')
@section('judul', 'Lengkapi Profil')

@section('isi')
    @include('partials.logo', ['susun' => true])

    {{-- Penanda dua langkah: Buat Akun selesai, Lengkapi Profil aktif. --}}
    <ol class="mt-6 flex items-center justify-center gap-3 text-sm" aria-label="Langkah pendaftaran">
        <li class="flex items-center gap-2.5 text-ink-2">
            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-primary text-white">
                <x-icon name="check" :size="15" :stroke="2.5" />
            </span>
            Buat Akun
        </li>
        <li class="h-px w-10 bg-line" aria-hidden="true"></li>
        <li class="flex items-center gap-2.5 text-ink" aria-current="step">
            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-primary text-[13px] font-medium text-white">2</span>
            Lengkapi Profil
        </li>
    </ol>

    <div class="mt-6 mb-6 text-center">
        <h1 class="text-2xl font-medium text-ink">Lengkapi Profil Anda</h1>
        <p class="mt-1.5 text-sm text-ink-2">Data ini digunakan pada seluruh perhitungan dan dokumen laporan</p>
    </div>

    <form method="POST" action="{{ route('profil.lengkapi') }}">
        @csrf
        @include('profil._form-data-diri', ['profil' => []])

        <x-info varian="biru-muda" class="mt-6">
            NIK berfungsi sebagai NPWP dan akan tercetak pada seluruh dokumen laporan. NIK tidak dapat diubah setelah disimpan.
        </x-info>

        <x-button type="submit" class="mt-6 w-full">Simpan dan Lanjutkan</x-button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-5 text-center text-[15px] text-ink-2">
        @csrf
        Isi nanti?
        <button type="submit" class="rounded font-medium text-ink hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">
            Keluar dari Akun
        </button>
    </form>
@endsection
