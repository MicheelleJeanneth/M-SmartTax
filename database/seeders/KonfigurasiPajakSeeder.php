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

        KonfigurasiPajak::updateOrCreate(
            ['tahun_berlaku' => MockData::TAHUN],
            [
                'tanggal_berlaku' => MockData::TAHUN.'-04-22',
                'tarif_final' => $pajak['tarif_final'],
                'ambang_bebas' => $pajak['ambang_bebas'],
                'ambang_maksimal' => $pajak['ambang_maksimal'],
                'dasar_hukum' => $pajak['dasar_hukum'],
            ],
        );
    }
}
