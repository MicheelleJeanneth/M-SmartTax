@extends('pdf._kerangka')
@section('periode', 'Posisi 31 Desember ' . $tahun)

@section('isi')
    @php
        $huruf = range('A', 'Z');
        $total = 0;

        // Satu pembaca untuk seluruh kolom, memakai penamaan yang sama dengan
        // tabel di layar. 'khas:N' menunjuk kolom khas kategori yang sedang
        // dicetak, sisanya kolom induk harta.
        $isi = function (\App\Models\Harta $baris, array $kolom): string {
            $nilai = str_starts_with($kolom['isi'], 'khas:')
                ? ($baris['khas'][(int) substr($kolom['isi'], 5)] ?? null)
                : $baris[$kolom['isi']];

            if (blank($nilai)) {
                return '-';
            }

            return ($kolom['kanan'] ?? false) ? rupiah($nilai) : (string) $nilai;
        };
    @endphp

    @foreach($kategori as $k)
        @php $subtotal = collect($k['baris'])->sum('nilai'); $total += $subtotal; @endphp
        <h2>{{ $huruf[$loop->index] }}. {{ $k['nama'] }}</h2>
        <table @class(['data', 'padat' => $padat ?? false])>
            <thead>
                <tr>
                    <th style="width: 24px;">No</th>
                    @foreach($k['laporan'] as $kolom)
                        <th @class(['r' => $kolom['kanan'] ?? false])>{{ $kolom['judul'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($k['baris'] as $baris)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        @foreach($k['laporan'] as $kolom)
                            <td @class(['r' => $kolom['kanan'] ?? false])>{{ $isi($baris, $kolom) }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($k['laporan']) + 1 }}" style="color: #7A7975;">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                {{-- Subtotal berdiri di kolom harga perolehan, yaitu kolom
                     bertanda 'nilai' pada susunan kategori ini. --}}
                @php $kolomNilai = collect($k['laporan'])->search(fn (array $x): bool => $x['isi'] === 'nilai'); @endphp
                <tr>
                    <td colspan="{{ $kolomNilai + 1 }}">Subtotal</td>
                    <td class="r">{{ rupiah($subtotal) }}</td>
                    @if(count($k['laporan']) - $kolomNilai - 1 > 0)
                        <td colspan="{{ count($k['laporan']) - $kolomNilai - 1 }}"></td>
                    @endif
                </tr>
            </tfoot>
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
