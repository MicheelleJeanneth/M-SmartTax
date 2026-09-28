{{-- Panel kanan login & registrasi. Lingkaran corak dipotong oleh overflow-hidden. --}}
<div class="relative flex h-full min-h-screen items-center overflow-hidden bg-primary-ink px-6 py-16 lg:px-24">
    <div class="pointer-events-none absolute -top-24 -right-24 h-[420px] w-[420px] rounded-full bg-white/[0.08]"></div>
    <div class="pointer-events-none absolute -bottom-28 -left-24 h-[320px] w-[320px] rounded-full bg-white/[0.07]"></div>
    <div class="pointer-events-none absolute -right-16 -bottom-32 h-[280px] w-[280px] rounded-full bg-white/[0.05]"></div>

    <div class="relative w-full max-w-[540px] text-white">
        <h2 class="text-[40px] leading-[1.15] font-medium">Susun draf pajak sebelum melapor</h2>
        <p class="mt-5 text-base leading-relaxed text-dark-2">
            Catat penghasilan, harta, dan utang usaha Anda. M-SmartTax menghitung PPh Final 0,5% sesuai PP Nomor 20 Tahun 2026.
        </p>

        <ul class="mt-10 space-y-6">
            @foreach([
                ['calculator', 'Perhitungan otomatis', 'Akumulasi omzet dan ambang bebas pajak dihitung sendiri'],
                ['file-text', 'Dokumen siap cetak', 'Unduh draf bulanan, tahunan, daftar harta dan utang'],
                ['trending-up', 'Analisis keuangan', 'Pantau kekayaan bersih dan kelayakan pembelian aset'],
            ] as [$ikon, $judul, $isi])
                <li class="flex gap-4">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-field bg-white/10 text-dark-3">
                        <x-icon :name="$ikon" :size="22" />
                    </span>
                    <div>
                        <p class="text-[17px] font-medium">{{ $judul }}</p>
                        <p class="mt-0.5 text-sm leading-relaxed text-dark-2">{{ $isi }}</p>
                    </div>
                </li>
            @endforeach
        </ul>

        <p class="mt-10 border-t border-white/15 pt-6 text-sm leading-relaxed text-dark-2">
            Draf disusun secara mandiri. Penyetoran dan pelaporan tetap dilakukan melalui saluran resmi Direktorat Jenderal Pajak.
        </p>
    </div>
</div>
