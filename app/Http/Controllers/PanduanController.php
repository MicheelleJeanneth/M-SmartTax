<?php

namespace App\Http\Controllers;

use App\Support\Panduan;
use Illuminate\View\View;

class PanduanController extends Controller
{
    public function __invoke(string $bagian = 'memulai'): View
    {
        $daftar = Panduan::daftar();

        abort_unless(isset($daftar[$bagian]), 404);

        return view('panduan', [
            'daftar' => $daftar,
            'bagian' => $bagian,
            'isi' => $daftar[$bagian],
        ]);
    }
}
