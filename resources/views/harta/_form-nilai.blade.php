{{--
    Formulir untuk baris harta yang sudah masuk draf pajak tahunan.

    Hanya kolom nilai yang dapat diubah. Kode, tahun, dan rinciannya ditampilkan
    apa adanya karena itulah yang menentukan draf tahun mana yang memuat baris
    ini; mengubahnya berarti memindahkan baris keluar atau masuk periode yang
    sudah berdraf. Susunannya mengikuti halaman detail agar tidak terasa pindah
    tempat.
--}}
@php
    $seksi = $info['seksi'] ?? true;
    $khasPenuh = (count($info['kolom']) + ($info['label_lokasi'] ? 1 : 0)) % 2 === 1;
    $khasDiAtas = $info['khas_sebelum_keterangan'] ?? false;
@endphp
<x-card>
    <x-info class="mb-6">
        Data ini sudah masuk draf pajak tahunan, sehingga kode, tahun, dan rinciannya tidak dapat diubah.
        Yang masih dapat diperbarui hanya nilainya, karena laporan tahun yang sudah tersusun memakai salinan tersendiri.
    </x-info>

    @if($seksi)
        <h2 class="mb-4 text-base font-medium text-subjudul">Data Harta</h2>
    @endif

    <x-kolom-baca label="Kode Harta">{{ $item['kode'] }} - {{ $item['nama'] }}</x-kolom-baca>

    @if(! $seksi && $khasDiAtas)
        @include('harta._khas-baca', ['bagian' => 'penuh'])
    @endif

    <x-kolom-baca label="Keterangan">{{ $item['keterangan'] ?? '—' }}</x-kolom-baca>

    @unless($seksi)
        @include('harta._khas-baca', ['bagian' => $khasDiAtas ? 'sisa' : 'semua'])
    @endunless

    {{-- Satu-satunya bagian yang masih dapat diisi. --}}
    @if($info['label_nilai_kini'])
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6">
            <x-input :label="$info['label_nilai']" name="nilai" wajib uang :value="angka($item['nilai'])" />
            <x-input :label="$info['label_nilai_kini']" name="nilai_kini" uang
                :value="$item['nilai_kini'] ? angka($item['nilai_kini']) : ''" />
        </div>
    @else
        <x-input :label="$info['label_nilai']" name="nilai" wajib uang :value="angka($item['nilai'])" />
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6">
        <x-kolom-baca :label="$info['label_tahun']">{{ $item['tahun'] }}</x-kolom-baca>
        <x-kolom-baca label="Tahun Pelepasan">{{ $item['tahun_pelepasan'] ?? '—' }}</x-kolom-baca>
    </div>

    @if($seksi)
        <div class="mb-5 border-t border-line-soft"></div>

        <h2 class="mb-4 text-base font-medium text-subjudul">{{ $info['judul_rincian'] }}</h2>

        @include('harta._khas-baca')
    @endif

    <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
        <x-button type="submit" class="w-full sm:w-auto sm:min-w-39">{{ $tombol }}</x-button>
        <x-button varian="secondary" :href="route('harta.index', $kategori)" class="w-full sm:w-auto sm:min-w-39">Batal</x-button>
    </div>
</x-card>
