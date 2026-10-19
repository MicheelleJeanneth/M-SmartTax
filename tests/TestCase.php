<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Seluruh tes berjalan di atas basis data SQLite dalam memori yang dimigrasikan
 * dan disemai sekali di awal, lalu tiap tes dibungkus transaksi sendiri.
 * Dengan begitu data contohnya sama persis dengan yang dilihat pengguna.
 */
abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected $seed = true;
}
