<?php

namespace App\Services;

/**
 * Perkiraan kelayakan pembelian aset secara kredit. Seluruh perhitungan
 * dilakukan di sisi server dan tidak satu pun hasilnya disimpan.
 */
class SimulasiAset
{
    /** Rasio angsuran di bawah batas ini tergolong layak. */
    /**
     * Isian awal formulir rencana pembelian.
     *
     * Sengaja kosong, kecuali cara pembayaran. Setiap kolom di sini masuk ke
     * rumus dan keluarannya berupa vonis kelayakan, sehingga angka bawaan yang
     * terlihat sah berbahaya: pengguna dapat menekan Hitung sambil mengira
     * angka itu miliknya, lalu menerima penilaian atas pembelian yang tidak
     * pernah ia rencanakan. Contoh angkanya dipindahkan ke placeholder, yang
     * tidak ikut terkirim. Jangan diisi ulang.
     *
     * Cara pembayaran tetap Kredit karena pilihan itulah yang menentukan kolom
     * mana yang tampil. Kalau bawaannya Tunai, tiga kolom kredit tersembunyi
     * sejak awal dan pengguna tidak tahu kolom itu ada; dengan Kredit, seluruh
     * formulir terlihat dan menguranginya menjadi tindakan sadar.
     *
     * @var array<string, mixed>
     */
    public const RENCANA_BAWAAN = [
        'jenis_aset' => '',
        'nama_aset' => '',
        'cara_pembayaran' => 'Kredit',
        'harga_aset' => null,
        'uang_muka' => null,
        'jangka_waktu' => null,
        'suku_bunga' => null,
    ];

    public const BATAS_LAYAK = 30;

    /** Sampai batas ini perlu dipertimbangkan; di atasnya belum disarankan. */
    public const BATAS_PERTIMBANGAN = 40;

    public function __construct(private AnalisisKeuangan $analisis) {}

    /** Meneruskan pilihan data peraga ke perhitungan di baliknya. */
    public function pakaiPeraga(?string $nama): ?string
    {
        return $this->analisis->pakaiPeraga($nama);
    }

    /**
     * Ketiga tingkat kelayakan beserta ambangnya, urut dari yang teringan.
     *
     * Panduan Pengguna membaca daftar yang sama, sehingga label dan angka
     * ambang di sana tidak pernah berbeda dengan yang dipakai perhitungan.
     *
     * @return array<int, array{kesimpulan: string, nada: string, batas: string}>
     */
    public static function tingkatKelayakan(): array
    {
        return [
            ['kesimpulan' => 'Layak', 'nada' => 'ok', 'batas' => 'di bawah '.self::BATAS_LAYAK.'%'],
            ['kesimpulan' => 'Perlu Dipertimbangkan', 'nada' => 'warn', 'batas' => self::BATAS_LAYAK.'% sampai '.self::BATAS_PERTIMBANGAN.'%'],
            ['kesimpulan' => 'Belum Disarankan', 'nada' => 'danger', 'batas' => 'di atas '.self::BATAS_PERTIMBANGAN.'%'],
        ];
    }

    /**
     * Kondisi keuangan tahun berjalan, terisi otomatis dari data yang tercatat.
     *
     * @return array{penghasilan_bulanan: int, penghasilan_tahun: int, cicilan_berjalan: int, total_harta: int, total_utang: int, kekayaan_bersih: int}
     */
    public function kondisi(int $tahun): array
    {
        $bruto = $this->analisis->peredaranBruto($tahun);

        return [
            'penghasilan_bulanan' => (int) round($bruto / 12),
            'penghasilan_tahun' => $bruto,
            'cicilan_berjalan' => $this->analisis->cicilanBerjalan($tahun),
            'total_harta' => $this->analisis->totalHarta($tahun),
            'total_utang' => $this->analisis->totalUtang($tahun),
            'kekayaan_bersih' => $this->analisis->kekayaanBersih($tahun),
        ];
    }

    /**
     * Angsuran bulanan dengan metode anuitas, dibulatkan ke rupiah terdekat.
     *
     * Rumus anuitas membagi dengan nol ketika bunganya nol, karena itu
     * kondisi tersebut diperiksa lebih dahulu dan pokoknya dibagi rata.
     */
    public function hitungCicilan(int $pokok, int $bulan, float $sukuBunga): int
    {
        if ($pokok <= 0 || $bulan <= 0) {
            return 0;
        }

        $bungaBulanan = $sukuBunga / 100 / 12;

        if ($bungaBulanan <= 0) {
            return (int) round($pokok / $bulan);
        }

        return (int) round($pokok * $bungaBulanan / (1 - (1 + $bungaBulanan) ** -$bulan));
    }

    /**
     * Rasio angsuran dan kesimpulan kelayakannya.
     *
     * Kesimpulan hanya menimbang rasio angsuran, mengikuti Debt Service Ratio
     * yang membandingkan kewajiban per bulan dengan penghasilan per bulan.
     * Rasio total utang mengukur posisi utang secara keseluruhan, sehingga
     * ikut ditampilkan sebagai keterangan tetapi tidak pernah memvonis.
     *
     * @param  array{penghasilan_bulanan: int, penghasilan_tahun: int, cicilan_berjalan: int, total_utang: int}  $kondisi
     * @return array{cicilan: int, total_kewajiban: int, dapat_dihitung: bool, rasio: float|null, kesimpulan: string|null, nada: string|null, penjelasan: string|null, utang_setelah: int, rasio_utang: float|null}
     */
    public function hitungKelayakan(array $kondisi, int $cicilan, int $pokok): array
    {
        $totalKewajiban = $cicilan + $kondisi['cicilan_berjalan'];
        $utangSetelah = $kondisi['total_utang'] + $pokok;

        // Penghasilan nol membuat pembagi rasio nol. Ini bukan galat: hasilnya
        // tetap ditampilkan sampai total kewajiban bulanan saja.
        if ($kondisi['penghasilan_bulanan'] <= 0) {
            return [
                'cicilan' => $cicilan,
                'total_kewajiban' => $totalKewajiban,
                'dapat_dihitung' => false,
                'rasio' => null,
                'kesimpulan' => null,
                'nada' => null,
                'penjelasan' => null,
                'utang_setelah' => $utangSetelah,
                'rasio_utang' => null,
            ];
        }

        $rasio = $totalKewajiban / $kondisi['penghasilan_bulanan'] * 100;
        $rasioUtang = $utangSetelah / $kondisi['penghasilan_tahun'] * 100;

        ['kesimpulan' => $kesimpulan, 'nada' => $nada] = self::tingkatKelayakan()[match (true) {
            $rasio < self::BATAS_LAYAK => 0,
            $rasio <= self::BATAS_PERTIMBANGAN => 1,
            default => 2,
        }];

        return [
            'cicilan' => $cicilan,
            'total_kewajiban' => $totalKewajiban,
            'dapat_dihitung' => true,
            'rasio' => $rasio,
            'kesimpulan' => $kesimpulan,
            'nada' => $nada,
            'penjelasan' => $this->penjelasan($nada, $rasio),
            'utang_setelah' => $utangSetelah,
            'rasio_utang' => $rasioUtang,
        ];
    }

    /**
     * Perkiraan status konsistensi harta bila aset dibeli pada tahun berjalan.
     *
     * Perhitungannya dipinjam dari AnalisisKeuangan, dipanggil dua kali dengan
     * masukan berbeda, supaya kedua angka benar-benar sebanding dengan yang
     * dipakai draf tahunan.
     *
     * Dua sebab perbandingan tidak dapat ditampilkan dibedakan: draf tahunan
     * pembanding belum ada, atau drafnya ada tetapi brutonya nol. Keduanya
     * memerlukan keterangan yang berbeda agar tidak menyesatkan.
     *
     * @return array{ada_pembanding: bool, dapat_dihitung: bool, sekarang: array<string, mixed>, perkiraan: array<string, mixed>}
     */
    public function dampakKonsistensi(int $tahun, int $hargaAset, int $pokok): array
    {
        $sekarang = $this->analisis->rasioKonsistensi($tahun);
        $perkiraan = $this->analisis->rasioKonsistensi($tahun, $hargaAset, $pokok);

        return [
            'ada_pembanding' => $sekarang['ada_pembanding'],
            'dapat_dihitung' => $sekarang['ada_pembanding'] && $sekarang['rasio'] !== null,
            'sekarang' => $sekarang,
            'perkiraan' => $perkiraan,
        ];
    }

    /**
     * Kalimat penjelas kesimpulan. Hanya menyebut rasio angsuran, karena
     * hanya itu yang menentukan vonisnya. Pembaginya disebut terang-terangan:
     * rata-rata penghasilan bulanan, bukan penghasilan satu bulan tertentu.
     */
    private function penjelasan(string $nada, float $rasio): string
    {
        return match ($nada) {
            'ok' => 'Seluruh kewajiban bulanan sebesar '.persen($rasio).' dari rata-rata penghasilan bulanan masih di bawah batas wajar '.self::BATAS_LAYAK.'%.',
            'warn' => 'Seluruh kewajiban bulanan sebesar '.persen($rasio).' dari rata-rata penghasilan bulanan sudah melewati batas wajar '.self::BATAS_LAYAK.'%, meski belum menyentuh '.self::BATAS_PERTIMBANGAN.'%.',
            default => 'Seluruh kewajiban bulanan sebesar '.persen($rasio).' dari rata-rata penghasilan bulanan melebihi batas '.self::BATAS_PERTIMBANGAN.'% dan membebani arus kas.',
        };
    }
}
