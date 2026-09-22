@extends('layouts.app')
@section('judul', 'Panduan Pengguna')
@section('keterangan', 'Langkah demi langkah memakai M-SmartTax.')

@php
    $bagian = [
        'memulai' => ['Memulai', [
            'Daftar dengan email dan kata sandi, lalu masuk.',
            'Lengkapi profil: NIK, nama, dan alamat akan tercetak di setiap laporan.',
            'Setelah profil lengkap, Anda diarahkan ke Dashboard.',
        ]],
        'penghasilan' => ['Mencatat Penghasilan', [
            'Buka Data Penghasilan, lalu pilih Tambah Penghasilan.',
            'Isi tanggal dan nominal bruto. Keterangan boleh dikosongkan.',
            'Penghasilan yang sudah masuk draf bulanan ditandai gembok dan tidak bisa diubah.',
        ]],
        'harta' => ['Mencatat Harta', [
            'Pilih kategori harta di bagian atas halaman Data Harta.',
            'Isi harga perolehan sesuai bukti pembelian. Nilai saat ini bersifat perkiraan.',
            'Luas tanah dan bangunan ditulis dalam satu kolom, misalnya 120/90.',
        ]],
        'utang' => ['Mencatat Utang', [
            'Catat setiap utang yang masih berjalan beserta saldo akhir tahunnya.',
            'Utang terkunci setelah draf tahunan disusun.',
        ]],
        'draf-bulanan' => ['Menyusun Draf Bulanan', [
            'Draf disusun berurutan mulai Januari.',
            'Bulan tanpa penghasilan tetap disusun sebagai draf nihil.',
            'Draf hanya dapat dibatalkan mulai dari bulan terakhir.',
        ]],
        'draf-tahunan' => ['Menyusun Draf Tahunan', [
            'Draf tahunan dapat disusun setelah dua belas draf bulanan lengkap.',
            'Periksa kartu Konsistensi Harta sebelum menyimpan.',
        ]],
        'laporan' => ['Mencetak Laporan', [
            'Pilih jenis laporan dan periode, lalu tekan Unduh PDF.',
            'Laporan hanya tersedia untuk draf yang sudah tersusun.',
        ]],
        'simulasi' => ['Simulasi Aset', [
            'Masukkan harga aset, uang muka, jangka waktu, dan suku bunga.',
            'Kesimpulan Layak bila total cicilan di bawah 30% penghasilan bulanan.',
        ]],
        'faq' => ['Pertanyaan Umum', [
            'Apakah M-SmartTax menyetor pajak? Tidak. Penyetoran dan pelaporan dilakukan melalui saluran resmi Direktorat Jenderal Pajak.',
            'Bisakah saya mengubah draf yang sudah tersimpan? Bisa, setelah draf dibatalkan.',
        ]],
    ];
@endphp

@section('isi')
    <div class="flex items-start gap-8">
        <nav class="sticky top-10 w-[260px] shrink-0 rounded-card border border-line bg-white p-4" aria-label="Daftar isi panduan">
            <p class="mb-2 px-2 text-[11px] font-medium tracking-wider text-ink-3">DAFTAR ISI</p>
            <ol class="space-y-0.5 text-sm">
                @foreach($bagian as $id => [$judul])
                    <li><a href="#{{ $id }}" class="block rounded-field px-2 py-1.5 text-ink-2 hover:bg-page hover:text-primary">{{ $loop->iteration }}. {{ $judul }}</a></li>
                @endforeach
            </ol>
        </nav>

        <div class="min-w-0 flex-1 space-y-6">
            @foreach($bagian as $id => [$judul, $isi])
                <x-card :judul="$loop->iteration . '. ' . $judul" id="{{ $id }}" class="scroll-mt-10">
                    <ul class="list-disc space-y-2 pl-5 text-[15px] leading-relaxed text-ink">
                        @foreach($isi as $baris)<li>{{ $baris }}</li>@endforeach
                    </ul>
                </x-card>
            @endforeach
        </div>
    </div>
@endsection
