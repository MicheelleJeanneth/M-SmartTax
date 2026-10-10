@extends('layouts.app')
@section('judul', 'Data Harta - ' . $info['nama'])
@section('keterangan', 'Catat harta yang dimiliki sebagai lampiran draf tahunan')

@php
    // Susunan kolom tabel. Kategori yang tidak menentukannya memakai pola umum:
    // kode, deskripsi, kolom khas, tahun, lalu nilai.
    $kolomTabel = $info['tabel'] ?? array_merge(
        [['judul' => 'Kode Harta', 'isi' => 'kode'], ['judul' => 'Deskripsi', 'isi' => 'deskripsi']],
        collect($info['kolom'])->map(fn (string $k, int $i): array => ['judul' => $k, 'isi' => 'khas:'.$i])->all(),
        [
            ['judul' => $info['label_tahun'], 'isi' => 'tahun'],
            ['judul' => $info['label_nilai'], 'isi' => 'nilai', 'kanan' => true],
        ],
    );

    $isiKolom = function (array $baris, string $isi): string {
        return match (true) {
            $isi === 'kode' => $baris['kode'],
            $isi === 'deskripsi' => $baris['nama'],
            $isi === 'keterangan' => $baris['keterangan'] ?? '—',
            $isi === 'lokasi' => $baris['negara'],
            $isi === 'tahun' => (string) $baris['tahun'],
            $isi === 'nilai' => rupiah($baris['nilai']),
            $isi === 'nilai_kini' => rupiah($baris['nilai_kini']),
            str_starts_with($isi, 'khas:') => (string) ($baris['khas'][(int) substr($isi, 5)] ?? '—'),
            default => '',
        };
    };
@endphp

@section('isi')
    {{-- Tombol tambah berdiri sendiri di antara keterangan halaman dan deretan tab. --}}
    <div class="mb-5 flex justify-end">
        <x-button :href="route('harta.create', $kategori)" class="min-w-[150px]">+ Tambah</x-button>
    </div>

    @include('harta._tab')

    <div class="grid grid-cols-2 gap-4">
        <x-stat :label="'Total ' . $info['nama']" :nilai="rupiah($total)" />
        <x-stat varian="putih" label="Jumlah Item" :nilai="$jumlah" />
    </div>

    {{-- Pencarian dan filter dikirim lewat query string agar tautan halaman tetap membawanya. --}}
    <form method="GET" class="mt-4 flex items-center gap-3">
        <div class="min-w-0 flex-1">
            <x-input name="cari" rapat :value="$cari" :placeholder="$petunjukCari" />
        </div>
        <div class="w-52 shrink-0">
            <x-select name="kode" rapat kosong="Semua kode" :pilihan="collect($daftarKode)->mapWithKeys(fn ($k) => [$k => $k])"
                :terpilih="$kode ?: null" onchange="this.form.submit()" />
        </div>
        <div class="w-52 shrink-0">
            <x-select name="tahun" rapat kosong="Semua tahun" :pilihan="collect($daftarTahun)->mapWithKeys(fn ($t) => [$t => $t])"
                :terpilih="$tahun ?: null" onchange="this.form.submit()" />
        </div>
        <noscript><x-button type="submit" varian="secondary">Tampilkan</x-button></noscript>
    </form>

    <x-card class="mt-4" padat>
        @if($harta->isEmpty())
            <x-empty judul="Belum ada {{ strtolower($info['nama']) }}" tombol="Tambah {{ $info['nama'] }}"
                :href="route('harta.create', $kategori)" class="border-0">
                Tambahkan harta pada kategori ini agar ikut terlampir di draf tahunan.
            </x-empty>
        @else
            <x-table rapat :kepala="array_merge(
                ['No'],
                collect($kolomTabel)->map(fn (array $k) => ['teks' => $k['judul'], 'kanan' => $k['kanan'] ?? false])->all(),
                ['Status', '']
            )">
                @foreach($harta as $h)
                    <tr>
                        <td class="text-ink-2">{{ $harta->firstItem() + $loop->index }}</td>
                        @foreach($kolomTabel as $k)
                            @php $isi = $isiKolom($h, $k['isi']); @endphp
                            {{-- Kolom angka tidak pernah dipotong: nominal yang terpotong bisa salah dibaca.
                                 Hanya kolom teks yang mengalah bila ruangnya kurang. --}}
                            <td @class([
                                'max-w-26 truncate' => ! ($k['kanan'] ?? false),
                                'text-right tabular-nums whitespace-nowrap' => $k['kanan'] ?? false,
                                'tabular-nums text-ink-2' => $k['isi'] === 'kode',
                                'tabular-nums' => $k['isi'] === 'tahun',
                            ]) title="{{ $isi }}">{{ $isi }}</td>
                        @endforeach
                        <td><x-badge :status="$h['terkunci'] ? 'terkunci' : 'aktif'" /></td>
                        <td>
                            @include('partials.aksi-baris', [
                                'lihat' => route('harta.show', [$kategori, $h['id']]),
                                'terkunci' => $h['terkunci'],
                                'alasanKunci' => 'Sudah masuk draf pajak tahunan',
                                'ubah' => route('harta.edit', [$kategori, $h['id']]),
                                'dialog' => 'hapus-harta-' . $h['id'],
                            ])
                        </td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-line-soft pt-4">
                <p class="text-sm text-ink-2">
                    Menampilkan {{ $harta->count() }} dari {{ $harta->total() }} item
                </p>
                @include('partials.halaman', ['data' => $harta])
            </div>
        @endif
    </x-card>

    <x-info varian="biru-muda" class="mt-4">{{ $info['catatan'] }}</x-info>

    @foreach($harta->where('terkunci', false) as $h)
        @include('partials.dialog-hapus', [
            'id' => 'hapus-harta-' . $h['id'],
            'judul' => 'Hapus data harta?',
            'rincian' => [
                'Kode Harta' => $h['kode'] . ' - ' . $h['nama'],
                'Keterangan' => $h['keterangan'] ?? '—',
                'Tahun Perolehan' => $h['tahun'],
                $info['label_nilai'] => rupiah($h['nilai']),
            ],
            'action' => route('harta.destroy', [$kategori, $h['id']]),
        ])
    @endforeach
@endsection
