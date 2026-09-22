{{-- Panel kanan login & registrasi. Dua lingkaran corak, overflow-hidden. --}}
<div class="relative flex h-full min-h-screen items-center justify-center overflow-hidden bg-primary-ink px-12 py-16">
    {{-- Atas kanan 360, #0E5F73 50% --}}
    <div class="pointer-events-none absolute -top-20 -right-[136px] h-[360px] w-[360px] rounded-full bg-primary/50"></div>
    {{-- Bawah kiri 300, #1793B4 25% --}}
    <div class="pointer-events-none absolute -bottom-20 -left-[60px] h-[300px] w-[300px] rounded-full bg-accent/25"></div>

    <div class="relative w-full max-w-[480px] text-white">
        <h2 class="text-[32px] leading-tight font-medium">Kelola pajak UMKM Anda dengan lebih tertata.</h2>
        <p class="mt-4 text-base leading-relaxed text-dark-2">
            Catat penghasilan, harta, dan utang di satu tempat. M-SmartTax menyusun draf PPh Final dan SPT Tahunan untuk Anda.
        </p>

        <ul class="mt-10 space-y-6">
            @foreach([
                ['receipt', 'Draf PPh Final otomatis', 'Hitung PPh Final 0,5% setiap bulan dari data penghasilan yang Anda catat.'],
                ['landmark', 'Catatan harta dan utang', 'Enam kategori harta dan daftar utang siap dilampirkan pada SPT Tahunan.'],
                ['calculator', 'Simulasi pembelian aset', 'Lihat apakah cicilan baru masih sehat untuk kondisi keuangan Anda.'],
            ] as [$ikon, $judul, $isi])
                <li class="flex gap-4">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-field bg-white/10 text-dark-3">
                        <x-icon :name="$ikon" :size="20" />
                    </span>
                    <div>
                        <p class="font-medium">{{ $judul }}</p>
                        <p class="mt-0.5 text-sm leading-relaxed text-dark-2">{{ $isi }}</p>
                    </div>
                </li>
            @endforeach
        </ul>

        <p class="mt-12 border-t border-white/15 pt-6 text-sm leading-relaxed text-dark-2">
            M-SmartTax membantu menyusun draf. Penyetoran dan pelaporan tetap dilakukan melalui saluran resmi Direktorat Jenderal Pajak.
        </p>
    </div>
</div>
