@extends('pdf._kerangka')
@section('periode', 'Posisi 31 Desember ' . $tahun)

@section('isi')
    <h2>A. Rincian Utang</h2>
    <table class="data padat">
        <thead>
            <tr>
                <th style="width: 24px;">No</th>
                <th>Kode</th>
                <th>Deskripsi</th>
                <th>NIK Kreditur</th>
                <th>Nama Kreditur</th>
                <th>Negara Kreditur</th>
                <th>Tahun Peminjaman</th>
                <th class="r">Saldo</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($utang as $u)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $u['kode'] }}</td>
                    <td>{{ $u['deskripsi'] }}</td>
                    <td>{{ $u['nik_kreditur'] ?: '-' }}</td>
                    <td>{{ $u['kreditur'] }}</td>
                    <td>{{ $u['negara'] }}</td>
                    <td>{{ $u['tahun'] }}</td>
                    <td class="r">{{ rupiah($u['saldo']) }}</td>
                    <td>{{ $u['keterangan'] ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="9" style="color: #7A7975;">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="7">Total utang</td>
                <td class="r">{{ rupiah(collect($utang)->sum('saldo')) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <h2>B. Rekapitulasi per Kode Utang</h2>
    <table class="data">
        <tbody>
            @foreach(collect($utang)->groupBy('kode') as $kode => $baris)
                <tr><td>{{ $kodeUtang[$kode] ?? $kode }}</td><td class="r">{{ rupiah($baris->sum('saldo')) }}</td></tr>
            @endforeach
        </tbody>
        <tfoot><tr><td>Total</td><td class="r">{{ rupiah(collect($utang)->sum('saldo')) }}</td></tr></tfoot>
    </table>
@endsection
