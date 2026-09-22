<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DrafBulananController;
use App\Http\Controllers\DrafTahunanController;
use App\Http\Controllers\HartaController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\PanduanController;
use App\Http\Controllers\PenghasilanController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\SimulasiController;
use App\Http\Controllers\UtangController;
use Illuminate\Support\Facades\Route;

/*
 | Mockup: belum ada middleware auth. Setelah autentikasi dibuat,
 | bungkus grup tamu dengan 'guest' dan grup aplikasi dengan 'auth'.
 */

Route::redirect('/', '/login');

// Tamu
Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store']);
Route::get('/register', [RegisterController::class, 'create'])->name('register');
Route::post('/register', [RegisterController::class, 'store']);
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

// Lengkapi profil (layout center, tanpa middleware profil.lengkap)
Route::get('/profil/lengkapi', [ProfilController::class, 'lengkapi'])->name('profil.lengkapi');
Route::post('/profil/lengkapi', [ProfilController::class, 'simpanLengkapi']);

// Aplikasi
Route::middleware('profil.lengkap')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('penghasilan', PenghasilanController::class)->except('show')
        ->parameters(['penghasilan' => 'id']);

    $kategori = 'kas|piutang|investasi|bergerak|tidak-bergerak|lainnya';
    Route::prefix('harta')->name('harta.')->controller(HartaController::class)->group(function () use ($kategori) {
        Route::get('/{kategori?}', 'index')->name('index')->where('kategori', $kategori);
        Route::get('/{kategori}/tambah', 'create')->name('create')->where('kategori', $kategori);
        Route::post('/{kategori}', 'store')->name('store')->where('kategori', $kategori);
        Route::get('/{kategori}/{id}', 'show')->name('show')->where('kategori', $kategori)->whereNumber('id');
        Route::get('/{kategori}/{id}/ubah', 'edit')->name('edit')->where('kategori', $kategori)->whereNumber('id');
        Route::put('/{kategori}/{id}', 'update')->name('update')->where('kategori', $kategori)->whereNumber('id');
        Route::delete('/{kategori}/{id}', 'destroy')->name('destroy')->where('kategori', $kategori)->whereNumber('id');
    });

    Route::resource('utang', UtangController::class)->parameters(['utang' => 'id']);

    Route::prefix('draf-bulanan')->name('draf-bulanan.')->controller(DrafBulananController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/susun', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{bulan}', 'show')->name('show')->whereNumber('bulan');
        Route::delete('/{bulan}', 'destroy')->name('destroy')->whereNumber('bulan');
    });

    Route::prefix('draf-tahunan')->name('draf-tahunan.')->controller(DrafTahunanController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/susun', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{tahun}', 'show')->name('show')->whereNumber('tahun');
        Route::delete('/{tahun}', 'destroy')->name('destroy')->whereNumber('tahun');
    });

    Route::get('/simulasi', [SimulasiController::class, 'index'])->name('simulasi.index');

    Route::prefix('laporan')->name('laporan.')->controller(LaporanController::class)->group(function () {
        foreach (['draf-bulanan', 'draf-tahunan', 'harta', 'utang'] as $jenis) {
            Route::get("/$jenis", 'show')->name($jenis)->defaults('jenis', $jenis);
        }
        $jenis = 'draf-bulanan|draf-tahunan|harta|utang';
        Route::get('/{jenis}/pratinjau', 'pratinjau')->name('pratinjau')->where('jenis', $jenis);
        Route::get('/{jenis}/unduh', 'unduh')->name('unduh')->where('jenis', $jenis);
    });

    Route::get('/profil', [ProfilController::class, 'show'])->name('profil.show');
    Route::get('/profil/ubah', [ProfilController::class, 'edit'])->name('profil.edit');
    Route::put('/profil', [ProfilController::class, 'update'])->name('profil.update');

    Route::get('/panduan', PanduanController::class)->name('panduan');
});
