<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Urutannya mengikuti ketergantungan: acuan dahulu, lalu data wajib pajak.
     */
    public function run(): void
    {
        $this->call([
            KonfigurasiPajakSeeder::class,
            KodeSeeder::class,
            DataContohSeeder::class,
        ]);
    }
}
