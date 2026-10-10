{{-- Kartu rincian transaksi + kartu perhitungan. Dipakai halaman Susun dan Lihat. --}}
<x-card judul="Data Penghasilan">
    @if($nihil)
        <div class="rounded-field bg-page px-5 py-8 text-center">
            <p class="font-medium text-ink">Tidak ada penghasilan tercatat pada {{ $namaBulan }}.</p>
            <p class="mt-1 text-sm text-ink-2">Draf nihil tetap sah dan dihitung sebagai salah satu dari dua belas draf.</p>
        </div>
    @else
        <x-table :kepala="['Tanggal', 'Keterangan', ['teks' => 'Nominal', 'kanan' => true]]">
            @foreach($transaksi as $t)
                <tr>
                    <td class="whitespace-nowrap">{{ tanggal_singkat($t['tanggal']) }}</td>
                    <td class="text-ink-2">{{ $t['keterangan'] }}</td>
                    <td class="text-right tabular-nums">{{ rupiah($t['nominal']) }}</td>
                </tr>
            @endforeach
            <x-slot:kaki>
                <tr><td colspan="2">Peredaran Bruto {{ $namaBulan }}</td><td class="text-right tabular-nums">{{ rupiah($brutoBulanIni) }}</td></tr>
            </x-slot:kaki>
        </x-table>
    @endif
</x-card>

<x-card judul="Perhitungan PPh Final" class="mt-4">
    {{-- Satu daftar menurun mengikuti urutan hitungannya: akumulasi, lalu bagian
         yang kena pajak, lalu tarif dan hasilnya. --}}
    <div class="text-[15px]">
        <x-row label="Akumulasi sampai {{ $bulanLalu }}">{{ rupiah($akumulasiSebelum) }}</x-row>
        <x-row label="Peredaran bruto {{ $namaBulan }} {{ $tahun }}">{{ rupiah($brutoBulanIni) }}</x-row>
        <x-row label="Akumulasi sampai {{ $namaBulan }} {{ $tahun }}">{{ rupiah($akumulasi) }}</x-row>

        <div class="my-3 border-t border-line-soft"></div>

        <x-row label="Ambang bebas pajak">{{ rupiah($batasBebas) }}</x-row>
        @if($terlampauiSejak)
            <x-row label="Sudah terlampaui sejak">{{ $terlampauiSejak }}</x-row>
        @else
            <x-row label="Sisa ambang bebas pajak">{{ rupiah($sisaBebas) }}</x-row>
        @endif
        <x-row label="Omzet kena pajak {{ $namaBulan }}">{{ rupiah($omzetKenaPajak) }}</x-row>

        <div class="my-3 border-t border-line-soft"></div>

        <x-row label="Tarif PPh Final">{{ persen($tarif) }}</x-row>
    </div>

    <div class="mt-3 flex items-center justify-between gap-6 rounded-field bg-primary-soft px-5 py-4">
        <p class="text-[15px] text-primary-ink">PPh Final Terutang {{ $namaBulan }} {{ $tahun }}</p>
        <p class="text-[28px] leading-tight font-medium tracking-tight tabular-nums text-primary-ink">{{ rupiah($pphFinal) }}</p>
    </div>

    <p class="mt-3 text-sm text-ink-3">Terbilang: {{ ucfirst(terbilang($pphFinal)) }}</p>
</x-card>
