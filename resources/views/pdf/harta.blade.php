@extends('pdf._kerangka')
@section('periode', 'Posisi 31 Desember ' . $tahun)

@section('isi')
    @php $huruf = range('A', 'Z'); $total = 0; @endphp
    @foreach($kategori as $k)
        @php $subtotal = collect($k['baris'])->sum('nilai'); $total += $subtotal; @endphp
        <h2>{{ $huruf[$loop->index] }}. {{ $k['nama'] }}</h2>
        <table class="data">
            <thead>
                <tr>
                    <th>Kode</th><th>Nama Harta</th><th>Tahun</th>
                    @foreach($k['kolom'] as $kolom)<th>{{ $kolom }}</th>@endforeach
                    <th class="r">{{ $k['label_nilai'] }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($k['baris'] as $b)
                    <tr>
                        <td>{{ $b['kode'] }}</td><td>{{ $b['nama'] }}</td><td>{{ $b['tahun'] }}</td>
                        @foreach($b['khas'] as $nilai)<td>{{ $nilai }}</td>@endforeach
                        <td class="r">{{ rupiah($b['nilai']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="{{ 4 + count($k['kolom']) }}" style="color: #7A7975;">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
            <tfoot><tr><td colspan="{{ 3 + count($k['kolom']) }}">Subtotal</td><td class="r">{{ rupiah($subtotal) }}</td></tr></tfoot>
        </table>
    @endforeach

    <h2>G. Rekapitulasi</h2>
    <table class="data">
        <tbody>
            @foreach($kategori as $k)
                <tr><td>{{ $huruf[$loop->index] }}. {{ $k['nama'] }}</td><td class="r">{{ rupiah(collect($k['baris'])->sum('nilai')) }}</td></tr>
            @endforeach
        </tbody>
        <tfoot><tr><td>Total harta</td><td class="r">{{ rupiah($total) }}</td></tr></tfoot>
    </table>
@endsection
