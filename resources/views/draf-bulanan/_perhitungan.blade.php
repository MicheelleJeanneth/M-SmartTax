{{-- Kartu rincian transaksi + kartu perhitungan + kotak hasil. Dipakai Susun & Lihat. --}}
<x-card :judul="'Rincian Transaksi ' . $namaBulan . ' ' . $tahun">
    @if($nihil)
        <div class="rounded-field bg-page px-5 py-8 text-center">
            <p class="font-medium text-ink">Tidak ada penghasilan tercatat pada {{ $namaBulan }}.</p>
            <p class="mt-1 text-sm text-ink-2">Draf nihil tetap sah dan dihitung sebagai salah satu dari dua belas draf.</p>
        </div>
    @else
        <x-table :kepala="['Tanggal', 'Keterangan', ['teks' => 'Nominal', 'kanan' => true]]">
            @foreach($transaksi as $t)
                <tr>
                    <td class="whitespace-nowrap">{{ tanggal_id($t['tanggal']) }}</td>
                    <td class="text-ink-2">{{ $t['keterangan'] }}</td>
                    <td class="text-right tabular-nums">{{ rupiah($t['nominal']) }}</td>
                </tr>
            @endforeach
            <x-slot:kaki>
                <tr><td colspan="2">Peredaran bruto {{ $namaBulan }}</td><td class="text-right tabular-nums">{{ rupiah($brutoBulanIni) }}</td></tr>
            </x-slot:kaki>
        </x-table>
    @endif
</x-card>

<x-card judul="Perhitungan PPh Final" class="mt-6">
    <div class="grid gap-6 lg:grid-cols-3">
        <div>
            <p class="mb-1 text-sm font-medium text-ink-2">1. Akumulasi</p>
            <div class="divide-y divide-line-soft">
                <x-row label="Akumulasi s.d. bulan lalu">{{ rupiah($akumulasiSebelum) }}</x-row>
                <x-row label="Bruto {{ $namaBulan }}">{{ rupiah($brutoBulanIni) }}</x-row>
                <x-row label="Akumulasi" tebal>{{ rupiah($akumulasi) }}</x-row>
            </div>
        </div>
        <div>
            <p class="mb-1 text-sm font-medium text-ink-2">2. Omzet Kena Pajak</p>
            <div class="divide-y divide-line-soft">
                <x-row label="Batas tidak kena pajak">{{ rupiah($batasBebas) }}</x-row>
                <x-row label="Sisa batas sebelum bulan ini">{{ rupiah($sisaBebas) }}</x-row>
                <x-row label="Omzet kena pajak" tebal>{{ rupiah($omzetKenaPajak) }}</x-row>
            </div>
        </div>
        <div>
            <p class="mb-1 text-sm font-medium text-ink-2">3. PPh Final</p>
            <div class="divide-y divide-line-soft">
                <x-row label="Omzet kena pajak">{{ rupiah($omzetKenaPajak) }}</x-row>
                <x-row label="Tarif">{{ persen($tarif) }}</x-row>
                <x-row label="PPh Final" tebal>{{ rupiah($pphFinal) }}</x-row>
            </div>
        </div>
    </div>

    <div class="mt-6 rounded-field bg-primary-soft px-6 py-5">
        <p class="text-sm font-medium text-primary">PPh Final terutang {{ $namaBulan }} {{ $tahun }}</p>
        <p class="mt-1 text-[32px] leading-tight font-semibold text-primary-ink">{{ rupiah($pphFinal) }}</p>
        <p class="mt-1 text-sm text-primary-ink/80 italic">Terbilang: {{ ucfirst(terbilang($pphFinal)) }}</p>
    </div>
</x-card>
