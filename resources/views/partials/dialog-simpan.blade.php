{{--
    Dialog konfirmasi simpan, sejajar dengan partials/dialog-hapus.

    'id'      Pengenal dialog; disebut oleh data-konfirmasi pada formulirnya.
    'form'    Pengenal formulir yang dikirim saat tombol konfirmasinya ditekan.
    'judul'   Pertanyaan di bagian atas, misalnya "Simpan data penghasilan?".
    'pesan'   Keterangan di bawah judul.
    'rincian' Label => nama kolom pada formulir, ['Tanggal' => 'tanggal', ...].
              Nilainya dibaca app.js langsung dari kolomnya saat dialog dibuka,
              bukan disusun di sini, karena yang perlu ditinjau adalah apa yang
              baru diketik pengguna di browser.
    'nilai'   Label => teks jadi, ['Masa Pajak' => 'Agustus 2026', ...], untuk
              formulir yang angkanya dihitung server dan tidak berasal dari
              kolom isian mana pun, misalnya halaman Susun Draf.
    'tombol'  Tulisan pada tombol konfirmasi.
--}}
@php
    $judul = $judul ?? 'Simpan data ini?';
    $pesan = $pesan ?? 'Periksa kembali isinya sebelum disimpan.';
    $rincian = $rincian ?? [];
    $nilai = $nilai ?? [];
    $tombol = $tombol ?? 'Simpan';
@endphp
<x-dialog :id="$id" :judul="$judul">
    {{ $pesan }}

    @if($rincian || $nilai)
        <dl class="mt-4 rounded-field bg-page px-4 py-3 text-[15px]">
            {{-- Susunannya mengikuti dialog hapus: berdampingan pada layar lebar,
                 menumpuk rata kiri pada layar sempit. --}}
            @foreach($nilai as $label => $isi)
                <div class="grid grid-cols-1 items-baseline gap-x-6 py-1 sm:grid-cols-[2fr_3fr]">
                    <dt class="truncate text-ink-2">{{ $label }}</dt>
                    <dd class="truncate text-ink sm:text-right" title="{{ $isi }}">{{ $isi }}</dd>
                </div>
            @endforeach
            @foreach($rincian as $label => $kolom)
                <div class="grid grid-cols-1 items-baseline gap-x-6 py-1 sm:grid-cols-[2fr_3fr]">
                    <dt class="truncate text-ink-2">{{ $label }}</dt>
                    <dd data-ringkas="{{ $kolom }}" class="truncate text-ink sm:text-right"></dd>
                </div>
            @endforeach
        </dl>
    @endif

    <x-slot:aksi>
        <x-button type="button" data-kirim="{{ $form }}">{{ $tombol }}</x-button>
    </x-slot:aksi>
</x-dialog>
