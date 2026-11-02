@extends('layouts.app')
@section('judul', 'Susun Draf Tahunan ' . $tahun)
@section('keterangan', 'Periksa rekap dan lampiran sebelum menyimpan draf')

@section('kembali')
    <x-back :href="route('draf-tahunan.index')">Kembali ke Draf Pajak Penghasilan Tahunan</x-back>
@endsection

@section('isi')
    @include('draf-tahunan._isi')

    {{-- Urutannya menentukan: draf menyalin angka yang ada saat ia disimpan,
         jadi saldo dan nilai harus diperbarui lebih dulu. Kalau terbalik,
         satu-satunya jalan memperbaiki adalah membatalkan drafnya. --}}
    <x-info varian="kuning" class="mt-4">
        Pastikan saldo utang dan nilai harta sudah sesuai posisi 31 Desember {{ $tahun }} sebelum menyimpan.
        Draf menyalin angka yang ada saat ini, dan salinan itu yang dipakai seluruh laporan tahun {{ $tahun }} seterusnya.
    </x-info>

    <x-info varian="kuning" class="mt-4">
        @if($statusKonsistensi !== 'normal')
            Konsistensi harta perlu diperhatikan. Pastikan harta yang diperoleh dari hibah, warisan, atau penghasilan di luar usaha sudah dicatat.
        @endif
        Batas lapor 31 Maret {{ $tahun + 1 }}. Setelah draf disimpan, draf bulanan tahun {{ $tahun }} terkunci,
        dan data harta serta utang tidak dapat lagi berganti kode, tahun, maupun rinciannya kecuali draf dibatalkan.
        Nilai dan saldonya tetap dapat diperbarui untuk keperluan tahun berikutnya, tanpa mengubah laporan tahun {{ $tahun }}.
    </x-info>

    {{-- Tombol rata kiri, sama seperti formulir lain. --}}
    <form method="POST" action="{{ route('draf-tahunan.store') }}" id="formulir-draf-tahunan"
          data-konfirmasi="simpan-draf-tahunan" class="mt-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
        @csrf
        <input type="hidden" name="tahun" value="{{ $tahun }}">
        <x-button type="submit" class="w-full sm:w-auto sm:min-w-39">Simpan</x-button>
        {{-- Tetap berupa tautan, bukan tombol biasa: bila Alpine gagal dimuat,
             Batal masih membawa pengguna kembali ke daftar. --}}
        <x-button varian="secondary" :href="route('draf-tahunan.index')" class="w-full sm:w-auto sm:min-w-39"
            x-data @click.prevent="$dispatch('buka-dialog', 'batal-susun')">Batal</x-button>
    </form>

    {{-- Di halaman ini tidak ada isian yang hilang; yang ditinggalkan adalah
         pemeriksaan rekap dan lampirannya, jadi kalimatnya pun menyebut itu. --}}
    @include('partials.dialog-batal', [
        'id' => 'batal-susun',
        'href' => route('draf-tahunan.index'),
        'judul' => 'Batalkan penyusunan draf?',
        'pesan' => '',
        'tombol' => 'Ya, Batalkan',
        'lanjut' => 'Lanjut Memeriksa',
    ])

    {{-- Total harta dan utang ikut diringkas karena keduanya dibekukan saat
         draf disimpan, dan salinan itu yang dipakai laporan tahun ini
         seterusnya. Angkanya dihitung server, jadi dikirim sebagai teks jadi. --}}
    @include('partials.dialog-simpan', [
        'id' => 'simpan-draf-tahunan',
        'form' => 'formulir-draf-tahunan',
        'judul' => 'Simpan draf tahunan ' . $tahun . '?',
        'pesan' => 'Menyimpan draf membekukan posisi harta dan utang di bawah ini, dan salinan itu yang dipakai seluruh laporan tahun ' . $tahun . ' seterusnya. Draf bulanan tahun ini ikut terkunci.',
        'nilai' => [
            'Tahun Pajak' => (string) $tahun,
            'Total Peredaran Bruto' => rupiah($bruto),
            'PPh Final Terutang' => rupiah($pph),
            'Total Harta' => rupiah($totalHarta),
            'Total Utang' => rupiah($totalUtang),
        ],
    ])
@endsection
