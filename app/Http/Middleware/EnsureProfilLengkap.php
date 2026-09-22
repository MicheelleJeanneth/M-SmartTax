<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfilLengkap
{
    /**
     * Arahkan pengguna yang profilnya belum lengkap ke halaman Lengkapi Profil.
     *
     * Mockup: selalu diteruskan. Setelah tabel profil ada, ganti dengan:
     * `if (! $request->user()?->profil?->profil_lengkap) { return redirect()->route('profil.lengkapi'); }`
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }
}
