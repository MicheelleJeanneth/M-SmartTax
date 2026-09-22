@extends('layouts.app')
@section('judul', 'Data Utang')
@section('keterangan', 'Catat utang yang masih berjalan untuk lampiran SPT Tahunan.')

@section('isi')
    <x-card judul="Daftar Utang" :keterangan="count($utang) . ' utang tercatat'">
        <x-slot:aksi>
            <x-button :href="route('utang.create')"><x-icon name="plus" :size="18" /> Tambah Utang</x-button>
        </x-slot:aksi>

        <x-table :kepala="['Kode', 'Nama Kreditur', 'Jenis Utang', 'Tahun Peminjaman', ['teks' => 'Saldo', 'kanan' => true], ['teks' => 'Aksi', 'kanan' => true]]">
            @foreach($utang as $u)
                <tr>
                    <td class="text-ink-2 tabular-nums">{{ $u['kode'] }}</td>
                    <td class="font-medium">{{ $u['kreditur'] }}</td>
                    <td class="text-ink-2">{{ $u['jenis'] }}</td>
                    <td class="tabular-nums">{{ $u['tahun'] }}</td>
                    <td class="text-right tabular-nums whitespace-nowrap">{{ rupiah($u['saldo']) }}</td>
                    <td>
                        @include('partials.aksi-baris', [
                            'lihat' => route('utang.show', $u['id']),
                            'terkunci' => $u['terkunci'],
                            'alasanKunci' => 'Sudah masuk draf tahunan',
                            'ubah' => route('utang.edit', $u['id']),
                            'dialog' => 'hapus-utang-' . $u['id'],
                        ])
                    </td>
                </tr>
            @endforeach
            <x-slot:kaki>
                <tr>
                    <td colspan="4">Total Utang</td>
                    <td class="text-right tabular-nums whitespace-nowrap">{{ rupiah(collect($utang)->sum('saldo')) }}</td>
                    <td></td>
                </tr>
            </x-slot:kaki>
        </x-table>
    </x-card>

    @foreach(collect($utang)->where('terkunci', false) as $u)
        @include('partials.dialog-hapus', [
            'id' => 'hapus-utang-' . $u['id'],
            'pesan' => 'Utang kepada ' . $u['kreditur'] . ' sebesar ' . rupiah($u['saldo']) . ' akan dihapus.',
            'action' => route('utang.destroy', $u['id']),
        ])
    @endforeach
@endsection
