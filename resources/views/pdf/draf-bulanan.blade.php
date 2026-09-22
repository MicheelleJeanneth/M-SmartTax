@extends('pdf._kerangka')
@section('periode', $namaBulan . ' ' . $tahun)

@section('isi')
    <h2>A. Rincian Transaksi</h2>
    <table class="data">
        <thead><tr><th style="width: 28px;">No</th><th>Tanggal</th><th>Keterangan</th><th class="r">Nominal</th></tr></thead>
        <tbody>
            @forelse($transaksi as $t)
                <tr><td>{{ $loop->iteration }}</td><td>{{ tanggal_id($t['tanggal']) }}</td><td>{{ $t['keterangan'] }}</td><td class="r">{{ rupiah($t['nominal']) }}</td></tr>
            @empty
                <tr><td colspan="4" style="text-align: center; color: #7A7975;">Tidak ada penghasilan pada bulan ini (draf nihil).</td></tr>
            @endforelse
        </tbody>
        <tfoot><tr><td colspan="3">Peredaran bruto</td><td class="r">{{ rupiah($brutoBulanIni) }}</td></tr></tfoot>
    </table>

    <h2>B. Perhitungan PPh Final</h2>
    <table class="data">
        <tbody>
            <tr><td>Akumulasi peredaran bruto s.d. bulan lalu</td><td class="r">{{ rupiah($akumulasiSebelum) }}</td></tr>
            <tr><td>Peredaran bruto {{ $namaBulan }}</td><td class="r">{{ rupiah($brutoBulanIni) }}</td></tr>
            <tr><td>Akumulasi peredaran bruto</td><td class="r">{{ rupiah($akumulasi) }}</td></tr>
            <tr><td>Batas peredaran bruto tidak kena pajak</td><td class="r">{{ rupiah($batasBebas) }}</td></tr>
            <tr><td>Omzet kena pajak</td><td class="r">{{ rupiah($omzetKenaPajak) }}</td></tr>
            <tr><td>Tarif PPh Final</td><td class="r">{{ persen($tarif) }}</td></tr>
        </tbody>
    </table>
    <div class="hasil">
        PPh Final terutang<br>
        <span class="angka">{{ rupiah($pphFinal) }}</span><br>
        <em>Terbilang: {{ ucfirst(terbilang($pphFinal)) }}</em>
    </div>
@endsection
