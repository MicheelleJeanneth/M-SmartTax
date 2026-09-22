@extends('layouts.app')
@section('judul', 'Data Harta')
@section('keterangan', 'Catat harta per kategori untuk lampiran SPT Tahunan.')

@section('isi')
    @include('harta._tab')

    @if(empty($harta))
        <x-empty judul="Belum ada {{ strtolower($info['nama']) }}" tombol="Tambah {{ $info['nama'] }}" :href="route('harta.create', $kategori)">
            Tambahkan harta pertama pada kategori ini agar ikut terlampir di draf tahunan.
        </x-empty>
    @else
        <x-card :judul="$info['nama']" :keterangan="count($harta) . ' harta tercatat'">
            <x-slot:aksi>
                <x-button :href="route('harta.create', $kategori)"><x-icon name="plus" :size="18" /> Tambah Harta</x-button>
            </x-slot:aksi>

            {{-- Nilai Saat Ini sengaja tidak ditampilkan di tabel, hanya di halaman detail (panduan 8.5). --}}
            <x-table :kepala="array_merge(['Kode', 'Nama Harta', 'Tahun Perolehan'], $info['kolom'], [['teks' => $info['label_nilai'], 'kanan' => true], ['teks' => 'Aksi', 'kanan' => true]])">
                @foreach($harta as $h)
                    <tr>
                        <td class="text-ink-2 tabular-nums">{{ $h['kode'] }}</td>
                        <td class="font-medium">{{ $h['nama'] }}</td>
                        <td class="tabular-nums">{{ $h['tahun'] }}</td>
                        @foreach($h['khas'] as $nilai)
                            <td class="text-ink-2">{{ $nilai }}</td>
                        @endforeach
                        <td class="text-right tabular-nums whitespace-nowrap">{{ rupiah($h['nilai']) }}</td>
                        <td>
                            @include('partials.aksi-baris', [
                                'lihat' => route('harta.show', [$kategori, $h['id']]),
                                'terkunci' => $h['terkunci'],
                                'alasanKunci' => 'Sudah masuk draf tahunan',
                                'ubah' => route('harta.edit', [$kategori, $h['id']]),
                                'dialog' => 'hapus-harta-' . $h['id'],
                            ])
                        </td>
                    </tr>
                @endforeach
                <x-slot:kaki>
                    <tr>
                        <td colspan="{{ 3 + count($info['kolom']) }}">Total {{ $info['nama'] }}</td>
                        <td class="text-right tabular-nums whitespace-nowrap">{{ rupiah(collect($harta)->sum('nilai')) }}</td>
                        <td></td>
                    </tr>
                </x-slot:kaki>
            </x-table>
        </x-card>

        @foreach(collect($harta)->where('terkunci', false) as $h)
            @include('partials.dialog-hapus', [
                'id' => 'hapus-harta-' . $h['id'],
                'pesan' => $h['nama'] . ' akan dihapus dari daftar harta.',
                'action' => route('harta.destroy', [$kategori, $h['id']]),
            ])
        @endforeach
    @endif
@endsection
