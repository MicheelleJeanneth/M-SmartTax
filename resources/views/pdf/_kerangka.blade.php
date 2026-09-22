{{--
    Kerangka bersama keempat laporan PDF (panduan bagian 9).
    DomPDF: tanpa Tailwind, tanpa flex/grid. Tata letak memakai <table>.
    Dipakai juga untuk pratinjau di halaman web (iframe) agar isi dijamin sama.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $judul }}</title>
    <style>
        @page { size: A4 portrait; margin: 20mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'DejaVu Sans', sans-serif; font-size: 9.5pt; color: #2C2C2A; line-height: 1.45; }
        /* DomPDF memperlakukan dirinya sebagai media "screen", jadi margin pratinjau web diatur lewat $pratinjau. */
        @if($pratinjau ?? false) body { padding: 76px; background: #fff; } @endif
        table { width: 100%; border-collapse: collapse; }
        .kop td { vertical-align: middle; padding: 0 0 10px; }
        .kop { border-bottom: 1.5px solid #2C2C2A; margin-bottom: 14px; }
        .logo { width: 32px; height: 32px; }
        .kop .merek { font-size: 13pt; font-weight: bold; color: #083845; padding-left: 8px; }
        .cetak { text-align: right; color: #5F5E5A; font-size: 8.5pt; }
        h1 { font-size: 13pt; margin: 0 0 12px; text-align: center; }
        .identitas td { padding: 2px 0; }
        .identitas td.l { width: 90px; color: #5F5E5A; }
        h2 { font-size: 10pt; margin: 18px 0 6px; color: #0E5F73; }
        .data th { text-align: left; font-weight: bold; color: #5F5E5A; border-bottom: 1px solid #D3D1C7; padding: 5px 6px; font-size: 8.5pt; }
        .data td { padding: 5px 6px; border-bottom: 1px solid #E5E3DC; font-size: 8.5pt; }
        .data .r { white-space: nowrap; }
        .data tfoot td { font-weight: bold; border-top: 1px solid #D3D1C7; border-bottom: 0; }
        .r, .data .r { text-align: right; }
        .hasil { background: #CBF2FF; padding: 10px 12px; margin-top: 10px; }
        .hasil .angka { font-size: 14pt; font-weight: bold; color: #083845; }
        .pernyataan { border: 1px solid #D3D1C7; padding: 10px 12px; margin-top: 22px; font-size: 8.5pt; color: #3D3D3A; }
        .ttd { margin-top: 26px; }
        .ttd td { width: 50%; }
    </style>
</head>
<body>
    <table class="kop">
        <tr>
            {{-- DomPDF tidak bisa memuat URL lokal, jadi logo disematkan sebagai base64. --}}
            <td style="width: 34px;"><img class="logo" src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/logo-ikon.png'))) }}" alt=""></td>
            <td class="merek">M-SmartTax</td>
            <td class="cetak">Dicetak {{ tanggal_id($tanggalCetak) }}</td>
        </tr>
    </table>

    <h1>{{ $judul }}</h1>

    <table class="identitas">
        <tr><td class="l">Nama</td><td>: {{ $profil['nama'] }}</td></tr>
        <tr><td class="l">NIK / NPWP</td><td>: {{ $profil['nik'] }} / {{ $profil['npwp'] }}</td></tr>
        <tr><td class="l">Alamat</td><td>: {{ $profil['alamat'] }}, {{ $profil['kelurahan'] }}, {{ $profil['kecamatan'] }}, {{ $profil['kota'] }} {{ $profil['kode_pos'] }}</td></tr>
        <tr><td class="l">Periode</td><td>: @yield('periode')</td></tr>
    </table>

    @yield('isi')

    <div class="pernyataan">
        Dokumen ini adalah draf yang disusun oleh M-SmartTax berdasarkan data yang dimasukkan oleh wajib pajak.
        Dokumen ini bukan bukti setor maupun bukti lapor. Penyetoran dan pelaporan dilakukan melalui saluran resmi Direktorat Jenderal Pajak.
    </div>

    <table class="ttd">
        <tr>
            <td></td>
            <td style="text-align: center;">
                {{ $profil['kota'] }}, {{ tanggal_id($tanggalCetak) }}<br><br><br><br>
                <strong>{{ $profil['nama'] }}</strong>
            </td>
        </tr>
    </table>
</body>
</html>
