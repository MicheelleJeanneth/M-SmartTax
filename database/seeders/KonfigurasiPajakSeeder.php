<?php

namespace Database\Seeders;

use App\Models\KonfigurasiPajak;
use App\Support\MockData;
use Illuminate\Database\Seeder;

/**
 * Tarif dan ambang yang berlaku. Diambil dari sumber yang sama dengan yang
 * dipakai tampilan, supaya angkanya tidak pernah berbeda.
 */
class KonfigurasiPajakSeeder extends Seeder
{
    public function run(): void
    {
        $pajak = MockData::konfigurasiPajak();

        // Aturan pendahulu, supaya tahun pajak sebelum 2026 juga punya dasar
        // perhitungan. Tarif dan ambangnya sama, hanya dasar hukumnya berbeda.
        $aturan = [
            [
                'tahun_berlaku' => 2022,
                'tanggal_berlaku' => '2022-12-20',
                'dasar_hukum' => 'PP Nomor 55 Tahun 2022',
            ],
            [
                'tahun_berlaku' => MockData::TAHUN,
                'tanggal_berlaku' => MockData::TAHUN.'-04-22',
                'dasar_hukum' => $pajak['dasar_hukum'],
            ],
        ];

        foreach ($aturan as $baris) {
            KonfigurasiPajak::updateOrCreate(
                ['tahun_berlaku' => $baris['tahun_berlaku']],
                [
                    'tanggal_berlaku' => $baris['tanggal_berlaku'],
                    'tarif_final' => $pajak['tarif_final'],
                    'ambang_bebas' => $pajak['ambang_bebas'],
                    'ambang_maksimal' => $pajak['ambang_maksimal'],
                    'dasar_hukum' => $baris['dasar_hukum'],
                ],
            );
        }
    }
}
