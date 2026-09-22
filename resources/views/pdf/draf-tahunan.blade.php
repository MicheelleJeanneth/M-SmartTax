@extends('pdf._kerangka')
@section('periode', 'Tahun Pajak ' . $tahun)

@section('isi')
    <h2>A. Rekap Peredaran Bruto 12 Bulan</h2>
    <table class="data">
        <thead><tr><th>Bulan</th><th class="r">Peredaran Bruto</th><th class="r">Omzet Kena Pajak</th><th class="r">PPh Final</th></tr></thead>
        <tbody>
            @foreach($rekap as $r)
                <tr><td>{{ $r['nama'] }}</td><td class="r">{{ rupiah($r['bruto']) }}</td><td class="r">{{ rupiah($r['omzet_kena_pajak']) }}</td><td class="r">{{ rupiah($r['pph_final']) }}</td></tr>
            @endforeach
        </tbody>
        <tfoot><tr><td>Total</td><td class="r">{{ rupiah($bruto) }}</td><td class="r">{{ rupiah(array_sum(array_column($rekap, 'omzet_kena_pajak'))) }}</td><td class="r">{{ rupiah($pph) }}</td></tr></tfoot>
    </table>

    <h2>B. Perhitungan</h2>
    <table class="data">
        <tbody>
            <tr><td>Total peredaran bruto</td><td class="r">{{ rupiah($bruto) }}</td></tr>
            <tr><td>Total PPh Final yang telah dihitung</td><td class="r">{{ rupiah($pph) }}</td></tr>
            <tr><td>Penghasilan neto (bruto dikurangi PPh Final)</td><td class="r">{{ rupiah($bruto - $pph) }}</td></tr>
        </tbody>
    </table>

    <h2>C. Harta</h2>
    <table class="data">
        <thead><tr><th>Kategori</th><th class="r">Jumlah</th><th class="r">Nilai</th></tr></thead>
        <tbody>
            @foreach($harta as $h)
                <tr><td>{{ $h['nama'] }}</td><td class="r">{{ $h['jumlah'] }}</td><td class="r">{{ rupiah($h['nilai']) }}</td></tr>
            @endforeach
        </tbody>
        <tfoot><tr><td colspan="2">Total harta</td><td class="r">{{ rupiah($totalHarta) }}</td></tr></tfoot>
    </table>

    <h2>D. Utang</h2>
    <table class="data">
        <thead><tr><th>Kode</th><th>Kreditur</th><th>Tahun</th><th class="r">Saldo</th></tr></thead>
        <tbody>
            @foreach($utang as $u)
                <tr><td>{{ $u['kode'] }}</td><td>{{ $u['kreditur'] }}</td><td>{{ $u['tahun'] }}</td><td class="r">{{ rupiah($u['saldo']) }}</td></tr>
            @endforeach
        </tbody>
        <tfoot><tr><td colspan="3">Total utang</td><td class="r">{{ rupiah($totalUtang) }}</td></tr></tfoot>
    </table>

    <h2>E. Kekayaan Bersih</h2>
    <table class="data">
        <tbody>
            <tr><td>Total harta</td><td class="r">{{ rupiah($totalHarta) }}</td></tr>
            <tr><td>Total utang</td><td class="r">({{ rupiah($totalUtang) }})</td></tr>
        </tbody>
        <tfoot><tr><td>Kekayaan bersih</td><td class="r">{{ rupiah($kekayaanBersih) }}</td></tr></tfoot>
    </table>
@endsection
