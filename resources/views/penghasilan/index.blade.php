@extends('layouts.app')
@section('judul', 'Data Penghasilan')
@section('keterangan', 'Catat setiap penghasilan usaha. Data yang sudah masuk draf bulanan terkunci.')

@section('isi')
    <x-card>
        <div class="mb-4 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <x-select name="filter_bulan" :pilihan="\App\Support\MockData::bulan()" :terpilih="7" kosong="Semua bulan" rapat class="w-48" />
            </div>
            <x-button :href="route('penghasilan.create')"><x-icon name="plus" :size="18" /> Tambah Penghasilan</x-button>
        </div>

        <x-table :kepala="['Tanggal', ['teks' => 'Nominal', 'kanan' => true], 'Keterangan', 'Status', ['teks' => 'Aksi', 'kanan' => true]]">
            @foreach($penghasilan as $p)
                <tr>
                    <td class="whitespace-nowrap">{{ tanggal_id($p['tanggal']) }}</td>
                    <td class="text-right tabular-nums whitespace-nowrap">{{ rupiah($p['nominal']) }}</td>
                    <td class="text-ink-2">{{ $p['keterangan'] }}</td>
                    <td>
                        @if($p['terkunci'])
                            <span class="inline-flex items-center gap-1.5 text-ink-3"><x-icon name="lock" :size="14" /> Masuk draf</span>
                        @else
                            <span class="text-ink-2">Dapat diubah</span>
                        @endif
                    </td>
                    <td>
                        @include('partials.aksi-baris', [
                            'terkunci' => $p['terkunci'],
                            'alasanKunci' => 'Sudah masuk draf bulanan',
                            'ubah' => route('penghasilan.edit', $p['id']),
                            'dialog' => 'hapus-penghasilan-' . $p['id'],
                        ])
                    </td>
                </tr>
            @endforeach
        </x-table>
    </x-card>

    @foreach(collect($penghasilan)->where('terkunci', false) as $p)
        @include('partials.dialog-hapus', [
            'id' => 'hapus-penghasilan-' . $p['id'],
            'pesan' => 'Penghasilan ' . tanggal_id($p['tanggal']) . ' sebesar ' . rupiah($p['nominal']) . ' akan dihapus permanen.',
            'action' => route('penghasilan.destroy', $p['id']),
        ])
    @endforeach
@endsection
