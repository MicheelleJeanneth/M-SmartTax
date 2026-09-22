@extends('layouts.guest')
@section('judul', 'Daftar')

@section('isi')
    @include('partials.logo')

    {{-- Registrasi tidak memakai penanda langkah (panduan 8.1). --}}
    <h1 class="mt-8 text-[28px] leading-tight font-medium text-ink">Buat akun baru</h1>
    <p class="mt-2 text-base text-ink-2">Daftar dengan email Anda. Data diri dilengkapi setelah masuk.</p>

    <form method="POST" action="{{ route('register') }}" class="mt-8">
        @csrf
        <x-input label="Email" name="email" type="email" wajib autocomplete="email" placeholder="nama@email.com" />
        <x-input-password label="Kata Sandi" name="password" autocomplete="new-password"
            bantuan="Gunakan kombinasi huruf dan angka" />
        <x-input-password label="Ulangi Kata Sandi" name="password_confirmation" autocomplete="new-password" />

        <x-button type="submit" class="mt-2 w-full">Daftar</x-button>
    </form>

    <p class="mt-6 text-center text-[15px] text-ink-2">
        Sudah punya akun?
        <a href="{{ route('login') }}" class="font-medium text-primary hover:underline">Masuk</a>
    </p>
@endsection
