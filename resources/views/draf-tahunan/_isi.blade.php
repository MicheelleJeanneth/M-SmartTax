{{-- Isi draf tahunan. Dipakai halaman Susun dan Rincian dengan susunan yang sama. --}}
@php
    $omzetKenaPajak = array_sum(array_column($rekap, 'omzet_kena_pajak'));
    $tarif = \App\Support\MockData::konfigurasiPajak($tahun)['tarif_final'];
@endphp

<x-card judul="Ringkasan Perpajakan">
    <div class="text-[15px]">
        <x-row label="Total peredaran bruto">{{ rupiah($bruto) }}</x-row>
        <x-row label="Total omzet kena pajak">{{ rupiah($omzetKenaPajak) }}</x-row>
        <x-row label="Tarif PPh Final">{{ persen($tarif) }}</x-row>
    </div>

    <div class="mt-3 flex items-center justify-between gap-6 rounded-field bg-primary-soft px-5 py-4">
        <p class="text-[15px] text-primary-ink">Total PPh Final Terutang {{ $tahun }}</p>
        <p class="text-[28px] leading-tight font-medium tracking-tight tabular-nums text-primary-ink">{{ rupiah($pph) }}</p>
    </div>

    <p class="mt-3 text-sm text-ink-3">Terbilang: {{ ucfirst(terbilang($pph)) }}</p>
</x-card>

<x-card judul="Lampiran Harta per 31 Desember {{ $tahun }}" class="mt-4" padat>
    <x-table :kepala="['Kategori', ['teks' => 'Jumlah', 'kanan' => true], ['teks' => 'Nilai', 'kanan' => true]]">
        @foreach($harta as $h)
            <tr>
                <td>{{ $h['nama'] }}</td>
                <td class="text-right tabular-nums">{{ $h['jumlah'] }}</td>
                <td class="text-right tabular-nums">{{ rupiah($h['nilai']) }}</td>
            </tr>
        @endforeach
        <x-slot:kaki>
            <tr>
                <td>Total Harta</td>
                <td class="text-right tabular-nums">{{ collect($harta)->sum('jumlah') }}</td>
                <td class="text-right tabular-nums">{{ rupiah($totalHarta) }}</td>
            </tr>
        </x-slot:kaki>
    </x-table>
</x-card>

<x-card judul="Lampiran Utang per 31 Desember {{ $tahun }}" class="mt-4" padat>
    <x-table :kepala="['Kode', 'Deskripsi', 'Kreditur', ['teks' => 'Saldo', 'kanan' => true]]">
        @foreach($utang as $u)
            <tr>
                <td class="tabular-nums text-ink-2">{{ $u['kode'] }}</td>
                <td>{{ $u['deskripsi'] }}</td>
                <td class="text-ink-2">{{ $u['kreditur'] }}</td>
                <td class="text-right tabular-nums">{{ rupiah($u['saldo']) }}</td>
            </tr>
        @endforeach
        <x-slot:kaki>
            <tr>
                <td colspan="3">Total Utang</td>
                <td class="text-right tabular-nums">{{ rupiah($totalUtang) }}</td>
            </tr>
        </x-slot:kaki>
    </x-table>
</x-card>

<div class="mt-4 grid gap-4 lg:grid-cols-2">
    <x-card judul="Pertumbuhan Kekayaan">
        <div class="text-[15px]">
            <x-row label="Akhir {{ $tahun - 1 }}">{{ rupiah($kekayaanTahunLalu) }}</x-row>
            <x-row label="Akhir {{ $tahun }}">{{ rupiah($kekayaanBersih) }}</x-row>
        </div>
        <div class="mt-3 flex items-center gap-4 border-t border-line-soft pt-3">
            <span class="inline-flex min-w-28 justify-center rounded-field bg-ok-bg px-4 py-2 text-sm font-medium text-ok-ink">
                + {{ persen($pertumbuhanPersen) }}
            </span>
            <span class="text-[15px] tabular-nums text-ok-ink">{{ rupiah($pertumbuhan) }}</span>
        </div>
    </x-card>

    <x-card judul="Konsistensi Harta">
        <div class="text-[15px]">
            <x-row label="Penghasilan neto (bruto − PPh)">{{ rupiah($bruto - $pph) }}</x-row>
            <x-row label="Kenaikan kekayaan bersih">{{ rupiah($pertumbuhan) }}</x-row>
        </div>
        <div class="mt-3 flex items-center gap-4 border-t border-line-soft pt-3">
            <x-badge :status="$statusKonsistensi" />
            <span class="text-[15px] text-ink-3">Rasio {{ persen($rasioKonsistensi) }}</span>
        </div>
    </x-card>
</div>
