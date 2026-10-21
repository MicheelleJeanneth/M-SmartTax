@extends('layouts.app')
@section('judul', 'Draf Pajak Penghasilan Bulanan')
@section('keterangan', 'Susunan draf PPh Final setiap bulan berdasarkan data penghasilan')

@section('aksi-header')
    <form method="GET" action="{{ route('draf-bulanan.index') }}">
        <label for="tahun" class="sr-only">Tahun pajak</label>
        <select id="tahun" name="tahun" onchange="this.form.submit()" class="kolom-isian w-auto pr-9">
            @foreach($daftarTahun as $t)
                <option value="{{ $t }}" @selected($t === $tahun)>Tahun {{ $t }}</option>
            @endforeach
        </select>
    </form>
@endsection

@section('isi')
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <x-stat label="Penghasilan Tercatat" :nilai="rupiah($brutoTahun)" />
        <x-stat label="Total PPh Final" :nilai="rupiah($totalPph)" />
        <x-stat varian="putih" label="Draf Tersusun" :nilai="$jumlahTersusun . ' dari ' . max($jumlahWajib, $jumlahTersusun)" />
    </div>

    <x-info varian="kuning" class="mt-4">
        Penyetoran PPh Final dilakukan paling lambat tanggal 15 bulan berikutnya melalui saluran resmi Direktorat Jenderal Pajak.
    </x-info>

    <x-card class="mt-4" padat>
        <x-table :kepala="['Bulan', ['teks' => 'Peredaran Bruto', 'kanan' => true], ['teks' => 'Akumulasi', 'kanan' => true], ['teks' => 'Omzet Kena Pajak', 'kanan' => true], ['teks' => 'PPh Final', 'kanan' => true], 'Status', ['teks' => 'Aksi', 'kanan' => true]]">
            @foreach($draf as $d)
                @php $sudah = in_array($d['status'], ['tersusun', 'nihil']); @endphp
                <tr @class(['text-ink-3' => ! $sudah && ! $d['boleh_susun']])>
                    <td class="font-medium">{{ $d['nama'] }}</td>
                    {{-- Peredaran bruto selalu ditampilkan apa adanya: angkanya
                         fakta dari Data Penghasilan, bukan hasil penyusunan draf.
                         Menuliskannya Rp 0 selama drafnya belum disusun membuat
                         tabel ini menyatakan hal yang tidak benar. --}}
                    <td class="text-right tabular-nums">{{ rupiah($d['bruto']) }}</td>
                    <td class="text-right tabular-nums">{{ $sudah ? rupiah($d['akumulasi']) : '-' }}</td>
                    <td class="text-right tabular-nums">{{ $sudah ? rupiah($d['omzet_kena_pajak']) : '-' }}</td>
                    <td class="text-right tabular-nums">{{ $sudah ? rupiah($d['pph_final']) : '-' }}</td>
                    <td>
                        @if($sudah || $d['wajib'])
                            <x-badge :status="$d['status']" />
                        @else
                            <span class="text-[13px] text-muted">&mdash;</span>
                        @endif
                    </td>
                    <td>
                        <div class="flex items-center justify-end gap-1">
                            @if($sudah)
                                <a href="{{ route('draf-bulanan.show', ['bulan' => $d['bulan'], 'tahun' => $tahun]) }}" class="rounded-field p-2 text-ink-3 hover:bg-page hover:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-accent" aria-label="Lihat draf {{ $d['nama'] }}">
                                    <x-icon name="eye" :size="18" />
                                </a>
                                {{-- Bulan tersusun selalu dapat dibatalkan, kecuali yang
                                     terkunci draf tahunan: lapisan itu dibuka dari halaman
                                     Draf Tahunan. --}}
                                @if($d['terkunci_tahunan'])
                                    <span class="p-2 text-locked" title="Terkunci draf tahunan, batalkan draf tahunan terlebih dahulu"><x-icon name="lock" :size="18" /></span>
                                @else
                                    <button type="button" x-data @click="$dispatch('buka-dialog', 'batal-{{ $d['bulan'] }}')"
                                        class="rounded-field p-2 text-danger hover:bg-danger/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                                        aria-label="Batalkan draf {{ $d['nama'] }}"
                                        title="{{ $d['jumlah_ikut'] > 1 ? 'Batalkan draf ' . $d['nama'] . ' beserta ' . ($d['jumlah_ikut'] - 1) . ' bulan sesudahnya' : 'Batalkan draf ' . $d['nama'] }}">
                                        <x-icon name="circle-x" :size="18" />
                                    </button>
                                @endif
                            @elseif($d['boleh_susun'])
                                <x-button :href="route('draf-bulanan.create', ['bulan' => $d['bulan'], 'tahun' => $tahun])" class="!h-9 !px-5 !text-sm">Susun</x-button>
                            @elseif($d['menunggu_akhir'])
                                {{-- Gilirannya sudah tiba tetapi bulannya masih berjalan. --}}
                                <span class="px-3 py-2 text-sm text-ink-3" title="Draf dapat disusun setelah {{ $d['nama'] }} berakhir">Menunggu akhir bulan</span>
                            @elseif(! $d['wajib'])
                                {{-- Sebelum penghasilan pertama tercatat: bukan masa pajaknya,
                                     jadi tidak ada draf yang perlu disusun di sini. --}}
                                <span class="px-5 py-2 text-sm text-muted" title="Belum ada penghasilan tercatat sampai bulan ini">&mdash;</span>
                            @else
                                {{-- Bulan yang belum tiba gilirannya: tulisan biasa, bukan tombol mati. --}}
                                <span class="px-5 py-2 text-sm text-ink-3" title="Susun draf bulan sebelumnya terlebih dahulu">Susun</span>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
            <x-slot:kaki>
                {{-- Peredaran Bruto menjumlahkan seluruh bulan, karena kolomnya
                     memang menampilkan seluruh bulan. Tiga kolom sisanya lahir dari
                     draf, jadi totalnya hanya dari bulan yang drafnya sudah tersusun.
                     Kolom Akumulasi memakai nilai akhir, bukan jumlah tiap baris —
                     menjumlahkannya akan menghitung Januari berulang kali. --}}
                <tr>
                    <td>Total</td>
                    <td class="text-right tabular-nums">{{ rupiah($brutoTahun) }}</td>
                    <td class="text-right tabular-nums">{{ $jumlahTersusun > 0 ? rupiah($akumulasi) : '-' }}</td>
                    <td class="text-right tabular-nums">{{ $jumlahTersusun > 0 ? rupiah(collect($draf)->sum('omzet_kena_pajak')) : '-' }}</td>
                    <td class="text-right tabular-nums">{{ $jumlahTersusun > 0 ? rupiah($totalPph) : '-' }}</td>
                    <td colspan="2"></td>
                </tr>
            </x-slot:kaki>
        </x-table>
    </x-card>

    <x-info varian="biru-muda" class="mt-4">
        Akumulasi peredaran bruto dihitung berurutan, sehingga membatalkan draf satu bulan ikut membatalkan draf bulan-bulan sesudahnya pada tahun yang sama.
        Draf yang sudah masuk draf pajak tahunan dibatalkan dari halaman Draf Pajak Penghasilan Tahunan terlebih dahulu.
    </x-info>

    @foreach(collect($draf)->where('boleh_batal', true) as $d)
        <x-dialog :id="'batal-' . $d['bulan']"
            :judul="$d['jumlah_ikut'] > 1
                ? 'Batalkan draf ' . $d['nama'] . ' sampai ' . $d['ikut_sampai'] . '?'
                : 'Batalkan draf ' . $d['nama'] . '?'">
            @if($d['jumlah_ikut'] > 1)
                {{-- Jumlahnya disebut terang-terangan: pengguna menekan satu baris
                     tetapi yang terbatalkan beberapa draf sekaligus. --}}
                <strong>{{ $d['jumlah_ikut'] }} draf</strong> akan dibatalkan sekaligus, yaitu {{ $d['nama'] }} sampai {{ $d['ikut_sampai'] }} {{ $tahun }},
                karena akumulasi peredaran bruto bulan sesudahnya ikut berubah. Seluruh data penghasilannya terbuka kembali dan perlu disusun ulang mulai dari {{ $d['nama'] }}.
            @else
                Data penghasilan {{ $d['nama'] }} akan terbuka kembali dan dapat diubah. Anda perlu menyusun ulang draf ini sebelum menyusun bulan berikutnya.
            @endif
            <x-slot:aksi>
                <form method="POST" action="{{ route('draf-bulanan.destroy', $d['bulan']) }}">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="tahun" value="{{ $tahun }}">
                    <x-button type="submit" varian="danger">Batalkan Draf</x-button>
                </form>
            </x-slot:aksi>
        </x-dialog>
    @endforeach
@endsection
