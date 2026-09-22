<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Mockup: belum ada autentikasi, langsung diarahkan ke dashboard.
     */
    public function store(Request $request): RedirectResponse
    {
        return redirect()->route('dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        return redirect()->route('login');
    }
}
