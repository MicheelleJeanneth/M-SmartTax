@extends('layouts.app')
@section('judul', 'Draf Pajak Penghasilan Bulanan')
@section('keterangan', 'Draf PPh Final 0,5% per bulan, tahun ' . $tahun . '. Susun berurutan dari Januari.')

@section('isi')
    <div class="grid grid-cols-3 gap-4">
        <x-stat label="Akumulasi Penghasilan" :nilai="rupiah($akumulasi)" catatan="Dari draf tersusun" />
        <x-stat label="Total PPh Final" :nilai="rupiah($totalPph)" catatan="Terutang tahun {{ $tahun }}" />
        <x-stat label="Draf Tersusun" :nilai="$jumlahTersusun . ' dari 12'" catatan="Bulan berikutnya: {{ \App\Support\MockData::bulan()[$jumlahTersusun + 1] ?? '-' }}" />
    </div>

    <x-info varian="biru" class="mt-6">
        Draf hanya dapat dibatalkan mulai dari bulan terakhir yang tersusun. Untuk mengubah draf Maret, batalkan dulu draf Juni, Mei, dan April.
    </x-info>

    <x-card class="mt-6">
        <x-table :kepala="['Bulan', ['teks' => 'Peredaran Bruto', 'kanan' => true], ['teks' => 'Akumulasi', 'kanan' => true], ['teks' => 'Omzet Kena Pajak', 'kanan' => true], ['teks' => 'PPh Final', 'kanan' => true], 'Status', ['teks' => 'Aksi', 'kanan' => true]]">
            @foreach($draf as $d)
                @php $sudah = in_array($d['status'], ['tersusun', 'nihil']); @endphp
                <tr class="{{ $sudah || $d['boleh_susun'] ? '' : 'text-ink-3' }}">
                    <td class="font-medium">{{ $d['nama'] }}</td>
                    <td class="text-right tabular-nums">{{ $sudah || $d['boleh_susun'] ? rupiah($d['bruto']) : '-' }}</td>
                    <td class="text-right tabular-nums">{{ $sudah ? rupiah($d['akumulasi']) : '-' }}</td>
                    <td class="text-right tabular-nums">{{ $sudah ? rupiah($d['omzet_kena_pajak']) : '-' }}</td>
                    <td class="text-right tabular-nums">{{ $sudah ? rupiah($d['pph_final']) : '-' }}</td>
                    <td><x-badge :status="$d['status']" /></td>
                    <td>
                        {{-- Aturan ikon: panduan bagian 5.3 --}}
                        <div class="flex items-center justify-end gap-1">
                            @if($sudah)
                                <a href="{{ route('draf-bulanan.show', $d['bulan']) }}" class="rounded-field p-2 text-ink-3 hover:bg-page hover:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-accent" aria-label="Lihat draf {{ $d['nama'] }}">
                                    <x-icon name="eye" :size="18" />
                                </a>
                                @if($d['terkunci_tahunan'])
                                    <span class="p-2 text-locked" title="Terkunci draf tahunan"><x-icon name="lock" :size="18" /></span>
                                @elseif($d['boleh_batal'])
                                    <button type="button" @click="$dispatch('buka-dialog', 'batal-{{ $d['bulan'] }}')"
                                        class="rounded-field p-2 text-danger hover:bg-danger/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent" aria-label="Batalkan draf {{ $d['nama'] }}">
                                        <x-icon name="circle-x" :size="18" />
                                    </button>
                                @else
                                    <span class="p-2 text-muted" title="Batalkan draf bulan setelahnya terlebih dahulu" aria-hidden="true"><x-icon name="circle-x" :size="18" /></span>
                                @endif
                            @elseif($d['boleh_susun'])
                                <x-button :href="route('draf-bulanan.create', ['bulan' => $d['bulan']])" class="!h-9 !px-4 !text-sm">Susun</x-button>
                            @else
                                <x-button disabled class="!h-9 !px-4 !text-sm">Susun</x-button>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
            <x-slot:kaki>
                <tr>
                    <td>Total</td>
                    <td class="text-right tabular-nums">{{ rupiah($akumulasi) }}</td>
                    <td></td>
                    <td class="text-right tabular-nums">{{ rupiah(collect($draf)->sum('omzet_kena_pajak')) }}</td>
                    <td class="text-right tabular-nums">{{ rupiah($totalPph) }}</td>
                    <td colspan="2"></td>
                </tr>
            </x-slot:kaki>
        </x-table>
    </x-card>

    <x-info varian="kuning" class="mt-6">
        Penyetoran PPh Final dilakukan paling lambat tanggal 15 bulan berikutnya melalui saluran resmi Direktorat Jenderal Pajak.
    </x-info>

    @foreach(collect($draf)->where('boleh_batal', true) as $d)
        <x-dialog :id="'batal-' . $d['bulan']" :judul="'Batalkan draf ' . $d['nama'] . '?'">
            Data penghasilan {{ $d['nama'] }} akan terbuka kembali dan dapat diubah. Anda perlu menyusun ulang draf ini sebelum menyusun bulan berikutnya.
            <x-slot:aksi>
                <form method="POST" action="{{ route('draf-bulanan.destroy', $d['bulan']) }}">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" varian="danger">Batalkan Draf</x-button>
                </form>
            </x-slot:aksi>
        </x-dialog>
    @endforeach
@endsection
