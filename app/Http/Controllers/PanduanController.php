<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PanduanController extends Controller
{
    public function __invoke(): View
    {
        return view('panduan');
    }
}
