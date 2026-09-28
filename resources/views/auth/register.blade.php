@extends('layouts.guest')
@section('judul', 'Daftar')

@section('isi')
    @include('partials.logo')

    {{-- Registrasi tidak memakai penanda langkah (panduan 8.1). --}}
    <h1 class="mt-14 text-[34px] leading-tight font-medium text-ink">Buat akun</h1>
    <p class="mt-1.5 text-base text-ink-2">Mulai menyusun draf pajak Anda sendiri</p>

    <form method="POST" action="{{ route('register') }}" class="mt-8">
        @csrf
        <x-input label="Email" name="email" type="email" wajib autocomplete="email" placeholder="nama@email.com" />
        <x-input-password label="Kata Sandi" name="password" autocomplete="new-password"
            placeholder="Minimal 8 karakter" bantuan="Gunakan kombinasi huruf dan angka" />
        <x-input-password label="Ulangi Kata Sandi" name="password_confirmation" autocomplete="new-password"
            placeholder="Ketik ulang kata sandi" />

        <x-button type="submit" class="mt-1 w-full">Daftar</x-button>
    </form>

    <p class="mt-7 text-center text-[15px] font-medium text-ink">
        Sudah punya akun?
        <a href="{{ route('login') }}" class="rounded text-accent hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">Masuk</a>
    </p>
@endsection
