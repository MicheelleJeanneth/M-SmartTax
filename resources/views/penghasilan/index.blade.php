@extends('layouts.app')
@section('judul', 'Data Penghasilan')
@section('keterangan', 'Catat setiap penerimaan usaha sebagai dasar draf pajak')

@section('isi')
    {{-- Tombol tambah berdiri sendiri di antara keterangan halaman dan kartu ringkasan. --}}
    <div class="mb-5 flex justify-end">
        <x-button :href="route('penghasilan.create')" class="min-w-[150px]">+ Tambah</x-button>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <x-stat label="Total Penghasilan" :nilai="rupiah($total)" />
        <x-stat varian="putih" label="Jumlah Transaksi" :nilai="$jumlahTransaksi" />
    </div>

    {{-- Pencarian dan filter dikirim lewat query string agar tautan halaman tetap membawanya. --}}
    <form method="GET" class="mt-4 flex flex-wrap items-center gap-3">
        <x-input name="cari" rapat :value="$cari" placeholder="Cari keterangan" class="min-w-[280px] flex-1" />
        <x-select name="bulan" rapat kosong="Semua bulan" :pilihan="\App\Support\MockData::bulan()" :terpilih="$bulan ?: null"
            onchange="this.form.submit()" class="w-44" />
        <x-select name="tahun" rapat :kosong="false" :pilihan="collect($daftarTahun)->mapWithKeys(fn ($t) => [$t => $t])"
            :terpilih="$tahun" onchange="this.form.submit()" class="w-32" />
        <noscript><x-button type="submit" varian="secondary">Tampilkan</x-button></noscript>
    </form>

    <x-card class="mt-4" padat>
        @if($penghasilan->isEmpty())
            <x-empty judul="Belum ada penghasilan pada periode ini" tombol="Tambah Penghasilan" :href="route('penghasilan.create')"
                class="border-0">
                Ubah bulan atau tahun pada filter di atas, atau catat penerimaan usaha yang baru.
            </x-empty>
        @else
            <x-table :kepala="['No', 'Tanggal', 'Keterangan', ['teks' => 'Nominal', 'kanan' => true], 'Status', '']">
                @foreach($penghasilan as $p)
                    <tr>
                        <td class="text-ink-2">{{ $penghasilan->firstItem() + $loop->index }}</td>
                        <td class="whitespace-nowrap">{{ tanggal_singkat($p['tanggal']) }}</td>
                        <td>{{ $p['keterangan'] }}</td>
                        <td class="text-right tabular-nums whitespace-nowrap">{{ rupiah($p['nominal']) }}</td>
                        <td><x-badge :status="$p['terkunci'] ? 'terkunci' : 'aktif'" /></td>
                        <td>
                            @include('partials.aksi-baris', [
                                'lihat' => route('penghasilan.show', $p['id']),
                                'terkunci' => $p['terkunci'],
                                'alasanKunci' => 'Sudah masuk draf pajak bulanan',
                                'ubah' => route('penghasilan.edit', $p['id']),
                                'dialog' => 'hapus-penghasilan-' . $p['id'],
                            ])
                        </td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-line-soft pt-4">
                <p class="text-sm text-ink-2">
                    Menampilkan {{ $penghasilan->count() }} dari {{ $penghasilan->total() }} transaksi
                </p>
                @include('partials.halaman', ['data' => $penghasilan])
            </div>
        @endif
    </x-card>

    <x-info varian="biru-muda" class="mt-4">
        Data bertanda terkunci sudah masuk draf pajak bulanan sehingga tidak dapat diubah. Batalkan draf bulan tersebut bila perlu memperbaikinya.
    </x-info>

    @foreach($penghasilan->where('terkunci', false) as $p)
        @include('partials.dialog-hapus', [
            'id' => 'hapus-penghasilan-' . $p['id'],
            'pesan' => 'Penghasilan ' . tanggal_id($p['tanggal']) . ' sebesar ' . rupiah($p['nominal']) . ' akan dihapus permanen.',
            'action' => route('penghasilan.destroy', $p['id']),
        ])
    @endforeach
@endsection
