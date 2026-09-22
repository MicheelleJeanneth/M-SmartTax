@extends('pdf._kerangka')
@section('periode', 'Posisi 31 Desember ' . $tahun)

@section('isi')
    <h2>A. Rincian Utang</h2>
    <table class="data">
        <thead><tr><th>Kode</th><th>Nama Kreditur</th><th>Jenis</th><th>Tahun</th><th class="r">Saldo</th></tr></thead>
        <tbody>
            @foreach($utang as $u)
                <tr><td>{{ $u['kode'] }}</td><td>{{ $u['kreditur'] }}</td><td>{{ $u['jenis'] }}</td><td>{{ $u['tahun'] }}</td><td class="r">{{ rupiah($u['saldo']) }}</td></tr>
            @endforeach
        </tbody>
        <tfoot><tr><td colspan="4">Total utang</td><td class="r">{{ rupiah(collect($utang)->sum('saldo')) }}</td></tr></tfoot>
    </table>

    <h2>B. Rekapitulasi per Jenis</h2>
    <table class="data">
        <tbody>
            @foreach(collect($utang)->groupBy('jenis') as $jenis => $baris)
                <tr><td>{{ $jenis }}</td><td class="r">{{ rupiah($baris->sum('saldo')) }}</td></tr>
            @endforeach
        </tbody>
        <tfoot><tr><td>Total</td><td class="r">{{ rupiah(collect($utang)->sum('saldo')) }}</td></tr></tfoot>
    </table>
@endsection
