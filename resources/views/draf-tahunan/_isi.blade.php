{{-- Isi lengkap draf tahunan. $ringkas = true untuk halaman Lihat. --}}
@php $ringkas = $ringkas ?? false; @endphp

<div class="grid grid-cols-3 gap-4">
    <x-stat label="Peredaran Bruto {{ $tahun }}" :nilai="rupiah($bruto)" />
    <x-stat label="Total PPh Final" :nilai="rupiah($pph)" />
    <x-stat label="Kekayaan Bersih" :nilai="rupiah($kekayaanBersih)" />
</div>

@unless($ringkas)
    <x-card judul="A. Rekap Dua Belas Bulan" class="mt-6">
        <x-table :kepala="['Bulan', ['teks' => 'Peredaran Bruto', 'kanan' => true], ['teks' => 'Akumulasi', 'kanan' => true], ['teks' => 'Omzet Kena Pajak', 'kanan' => true], ['teks' => 'PPh Final', 'kanan' => true]]">
            @foreach($rekap as $r)
                <tr>
                    <td>{{ $r['nama'] }}</td>
                    <td class="text-right tabular-nums">{{ rupiah($r['bruto']) }}</td>
                    <td class="text-right tabular-nums">{{ rupiah($r['akumulasi']) }}</td>
                    <td class="text-right tabular-nums">{{ rupiah($r['omzet_kena_pajak']) }}</td>
                    <td class="text-right tabular-nums">{{ rupiah($r['pph_final']) }}</td>
                </tr>
            @endforeach
            <x-slot:kaki>
                <tr><td>Total</td><td class="text-right tabular-nums">{{ rupiah($bruto) }}</td><td></td>
                    <td class="text-right tabular-nums">{{ rupiah(array_sum(array_column($rekap, 'omzet_kena_pajak'))) }}</td>
                    <td class="text-right tabular-nums">{{ rupiah($pph) }}</td></tr>
            </x-slot:kaki>
        </x-table>
    </x-card>
@endunless

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <x-card judul="{{ $ringkas ? 'Harta' : 'B. Lampiran Harta' }}">
        <div class="divide-y divide-line-soft">
            @foreach($harta as $h)
                <x-row :label="$h['nama'] . ' (' . $h['jumlah'] . ')'">{{ rupiah($h['nilai']) }}</x-row>
            @endforeach
            <x-row label="Total Harta" tebal>{{ rupiah($totalHarta) }}</x-row>
        </div>
    </x-card>
    <x-card judul="{{ $ringkas ? 'Utang' : 'C. Lampiran Utang' }}">
        <div class="divide-y divide-line-soft">
            @foreach($utang as $u)
                <x-row :label="$u['kreditur'] . ' · ' . $u['jenis']">{{ rupiah($u['saldo']) }}</x-row>
            @endforeach
            <x-row label="Total Utang" tebal>{{ rupiah($totalUtang) }}</x-row>
        </div>
    </x-card>
</div>

@unless($ringkas)
    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <x-card judul="Pertumbuhan Kekayaan">
            <div class="divide-y divide-line-soft">
                <x-row label="Kekayaan bersih {{ $tahun - 1 }}">{{ rupiah($kekayaanTahunLalu) }}</x-row>
                <x-row label="Kekayaan bersih {{ $tahun }}">{{ rupiah($kekayaanBersih) }}</x-row>
                <x-row label="Pertumbuhan" tebal>+ {{ rupiah($pertumbuhan) }} ({{ persen($pertumbuhanPersen) }})</x-row>
            </div>
        </x-card>
        <x-card judul="Konsistensi Harta">
            <x-slot:aksi><x-badge :status="$statusKonsistensi" /></x-slot:aksi>
            <div class="divide-y divide-line-soft">
                <x-row label="Penghasilan neto (bruto − PPh)">{{ rupiah($bruto - $pph) }}</x-row>
                <x-row label="Kenaikan kekayaan bersih">{{ rupiah($pertumbuhan) }}</x-row>
                <x-row label="Rasio kenaikan terhadap penghasilan" tebal>{{ persen($rasioKonsistensi) }}</x-row>
            </div>
            <p class="mt-3 text-[13px] text-ink-3">Normal di bawah 60% · Perlu Ditinjau 60–100% · Perlu Diperiksa di atas 100%</p>
        </x-card>
    </div>
@endunless
