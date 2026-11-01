{{--
    Dialog konfirmasi batal, untuk tombol yang meninggalkan formulir.

    'id'     Pengenal dialog; disebut tombol pemanggilnya lewat $dispatch.
    'href'   Tujuan bila pengguna jadi membatalkan.
    'judul'  Pertanyaan di bagian atas.
    'pesan'  Keterangan di bawah judul.
    'tombol' Tulisan pada tombol penegasan.
    'lanjut' Tulisan pada tombol kiri, yang artinya tetap di formulir.
--}}
@php
    $judul = $judul ?? 'Batalkan pengisian?';
    $pesan = $pesan ?? 'Isian yang belum disimpan akan hilang.';
    $tombol = $tombol ?? 'Ya, Batalkan';
    $lanjut = $lanjut ?? 'Lanjut Mengisi';
@endphp
<x-dialog :id="$id" :judul="$judul" :batal="$lanjut">
    {{ $pesan }}

    <x-slot:aksi>
        <x-button varian="danger" :href="$href">{{ $tombol }}</x-button>
    </x-slot:aksi>
</x-dialog>
