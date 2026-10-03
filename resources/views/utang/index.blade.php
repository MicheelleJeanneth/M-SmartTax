@extends('layouts.app')
@section('judul', 'Data Utang')
@section('keterangan', 'Catat utang yang masih berjalan sebagai lampiran draf tahunan')

@section('isi')
    {{-- Tombol tambah berdiri sendiri di antara keterangan halaman dan kartu ringkasan. --}}
    <div class="mb-5 flex justify-end">
        <x-button :href="route('utang.create')" class="min-w-[150px]">+ Tambah</x-button>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <x-stat label="Total Utang" :nilai="rupiah($total)" />
        <x-stat varian="putih" label="Jumlah Utang" :nilai="$jumlah" />
    </div>

    {{-- Pencarian dan filter dikirim lewat query string agar tautan halaman tetap membawanya. --}}
    <form method="GET" class="mt-4 flex items-center gap-3">
        <div class="min-w-0 flex-1">
            <x-input name="cari" rapat :value="$cari" placeholder="Cari deskripsi atau nama kreditur" />
        </div>
        <div class="w-52 shrink-0">
            <x-select name="kode" rapat kosong="Semua kode" :pilihan="\App\Support\MockData::kodeUtang()"
                :terpilih="$kode ?: null" onchange="this.form.submit()" />
        </div>
        <div class="w-40 shrink-0">
            <x-select name="tahun" rapat kosong="Semua tahun" :pilihan="collect($daftarTahun)->mapWithKeys(fn ($t) => [$t => $t])"
                :terpilih="$tahun ?: null" onchange="this.form.submit()" />
        </div>
        <noscript><x-button type="submit" varian="secondary">Tampilkan</x-button></noscript>
    </form>

    <x-card class="mt-4" padat>
        @if($utang->isEmpty())
            <x-empty judul="Belum ada utang pada saringan ini" tombol="Tambah Utang" :href="route('utang.create')"
                class="border-0">
                Ubah kode atau tahun pada filter di atas, atau catat utang yang baru.
            </x-empty>
        @else
            {{-- Negara Kreditur tidak ditampilkan: aplikasi ini khusus WNI sehingga nilainya selalu Indonesia. --}}
            <x-table :kepala="['No', 'Kode', 'Deskripsi', 'Tahun Peminjaman', 'NIK Kreditur', 'Nama Kreditur', ['teks' => 'Saldo Utang', 'kanan' => true], 'Status', '']">
                @foreach($utang as $u)
                    @php $namaKode = \App\Support\MockData::kodeUtang()[$u['kode']]; @endphp
                    <tr>
                        <td class="text-ink-2">{{ $utang->firstItem() + $loop->index }}</td>
                        <td class="tabular-nums text-ink-2" title="{{ $namaKode }}">{{ $u['kode'] }}</td>
                        <td class="w-full max-w-0 min-w-36 truncate" title="{{ $u['deskripsi'] }}">{{ $u['deskripsi'] }}</td>
                        <td class="tabular-nums">{{ $u['tahun'] }}</td>
                        <td class="tabular-nums whitespace-nowrap">{{ $u['nik_kreditur'] ?? '—' }}</td>
                        <td class="max-w-38 truncate" title="{{ $u['kreditur'] }}">{{ $u['kreditur'] }}</td>
                        <td class="text-right tabular-nums whitespace-nowrap">{{ rupiah($u['saldo']) }}</td>
                        <td><x-badge :status="$u['terkunci'] ? 'terkunci' : 'aktif'" /></td>
                        <td>
                            @include('partials.aksi-baris', [
                                'lihat' => route('utang.show', $u['id']),
                                'terkunci' => $u['terkunci'],
                                'alasanKunci' => 'Sudah masuk draf pajak tahunan',
                                'ubah' => route('utang.edit', $u['id']),
                                'dialog' => 'hapus-utang-' . $u['id'],
                            ])
                        </td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-line-soft pt-4">
                <p class="text-sm text-ink-2">
                    Menampilkan {{ $utang->count() }} dari {{ $utang->total() }} utang
                </p>
                @include('partials.halaman', ['data' => $utang])
            </div>
        @endif
    </x-card>

    <x-info varian="biru-muda" class="mt-4">
        Saldo yang dicatat adalah sisa utang pada akhir tahun pajak, bukan nilai pinjaman awal.
    </x-info>

    @foreach($utang->where('terkunci', false) as $u)
        @include('partials.dialog-hapus', [
            'id' => 'hapus-utang-' . $u['id'],
            'judul' => 'Hapus data utang?',
            'rincian' => [
                'Deskripsi' => $u['deskripsi'],
                'Nama Kreditur' => $u['kreditur'],
                'Saldo Utang' => rupiah($u['saldo']),
            ],
            'action' => route('utang.destroy', $u['id']),
        ])
    @endforeach
@endsection
