@extends('layouts.app')
@section('judul', 'Draf Pajak Penghasilan Tahunan')
@section('keterangan', 'Draf SPT Tahunan disusun setelah dua belas draf bulanan lengkap.')

@section('isi')
    <x-info varian="kuning" class="mb-4">
        Pelaporan SPT Tahunan dilakukan paling lambat 31 Maret tahun berikutnya melalui saluran resmi Direktorat Jenderal Pajak.
    </x-info>

    <x-card padat>
        <x-table :kepala="['Tahun', 'Draf Bulanan', ['teks' => 'Peredaran Bruto', 'kanan' => true], ['teks' => 'Total PPh Final', 'kanan' => true], ['teks' => 'Kekayaan Bersih', 'kanan' => true], 'Status', ['teks' => 'Aksi', 'kanan' => true]]">
            @foreach($draf as $d)
                <tr>
                    <td class="font-medium tabular-nums">{{ $d['tahun'] }}</td>
                    <td class="tabular-nums {{ $d['draf_bulanan'] < 12 ? 'text-warn-ink' : '' }}">{{ $d['draf_bulanan'] }} dari 12</td>
                    <td class="text-right tabular-nums">{{ rupiah($d['bruto']) }}</td>
                    <td class="text-right tabular-nums">{{ rupiah($d['pph_final']) }}</td>
                    <td class="text-right tabular-nums">{{ rupiah($d['kekayaan_bersih']) }}</td>
                    <td><x-badge :status="$d['status']" /></td>
                    <td>
                        <div class="flex items-center justify-end gap-1">
                            @if($d['status'] === 'tersusun')
                                <a href="{{ route('draf-tahunan.show', $d['tahun']) }}" class="rounded-field p-2 text-ink-3 hover:bg-page hover:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-accent" aria-label="Lihat draf {{ $d['tahun'] }}">
                                    <x-icon name="eye" :size="18" />
                                </a>
                                @if($loop->index === 1)
                                    <button type="button" x-data @click="$dispatch('buka-dialog', 'batal-tahunan-{{ $d['tahun'] }}')"
                                        class="rounded-field p-2 text-danger hover:bg-danger/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent" aria-label="Batalkan draf {{ $d['tahun'] }}">
                                        <x-icon name="circle-x" :size="18" />
                                    </button>
                                @else
                                    <span class="p-2 text-muted" title="Batalkan draf tahun setelahnya terlebih dahulu" aria-hidden="true"><x-icon name="circle-x" :size="18" /></span>
                                @endif
                            @elseif($d['draf_bulanan'] === 12)
                                <x-button :href="route('draf-tahunan.create', ['tahun' => $d['tahun']])" class="!h-9 !px-4 !text-sm">Susun</x-button>
                            @else
                                {{-- Belum genap dua belas draf bulanan: tulisan biasa, bukan tombol mati. --}}
                                <span class="px-5 py-2 text-sm text-ink-3" title="Lengkapi dua belas draf bulanan terlebih dahulu">Susun</span>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-table>
    </x-card>

    <x-info varian="biru-muda" class="mt-4">
        Draf tahunan hanya dapat disusun setelah seluruh dua belas draf bulanan selesai, dan akan mengunci draf bulanan, data harta, serta data utang pada tahun tersebut.
        Pembatalan hanya dapat dimulai dari tahun terakhir; untuk membatalkan tahun sebelumnya, batalkan dulu draf tahun setelahnya.
    </x-info>

    @php $batal = $draf[1]; @endphp
    {{-- Dialog 520 × 520 dengan kotak kuning daftar data yang akan terbuka kembali --}}
    <x-dialog :id="'batal-tahunan-' . $batal['tahun']" :judul="'Batalkan draf tahunan ' . $batal['tahun'] . '?'">
        <p>Draf SPT Tahunan {{ $batal['tahun'] }} akan dihapus dan Anda perlu menyusunnya ulang.</p>
        <div class="mt-4 rounded-field bg-warn-bg px-4 py-3.5 text-sm text-warn-ink">
            <p class="font-medium">Data berikut akan terbuka kembali dan dapat diubah:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                <li>12 draf PPh Final bulanan tahun {{ $batal['tahun'] }}</li>
                <li>Seluruh data harta pada enam kategori</li>
                <li>Seluruh data utang</li>
            </ul>
        </div>
        <p class="mt-4">Laporan PDF yang sudah diunduh tidak ikut berubah.</p>
        <x-slot:aksi>
            <form method="POST" action="{{ route('draf-tahunan.destroy', $batal['tahun']) }}">
                @csrf
                @method('DELETE')
                <x-button type="submit" varian="danger">Batalkan Draf</x-button>
            </form>
        </x-slot:aksi>
    </x-dialog>
@endsection
