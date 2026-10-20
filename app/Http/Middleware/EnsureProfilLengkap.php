<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfilLengkap
{
    /**
     * Pengguna yang profilnya belum lengkap hanya boleh membuka Lengkapi Profil dan Keluar.
     * Halaman lain selalu dialihkan kembali, termasuk bila diketik langsung di URL.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->profil_lengkap === false) {
            return redirect()->route('profil.lengkapi');
        }

        return $next($request);
    }
}
