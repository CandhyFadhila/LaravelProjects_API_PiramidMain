<?php

namespace App\Http\Middleware;

use App\Http\Resources\Templates\WithoutDataResource;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerifiedRole
{
    public function handle(Request $request, Closure $next, $type = null)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_UNAUTHORIZED,
                    'NO_ACTIVE_SESSION',
                    'Akses Ditolak',
                    'Anda tidak memiliki sesi login yang aktif.'
                ),
                Response::HTTP_UNAUTHORIZED
            );
        }

        $roles = match ($type) {
            'marketplace' => ['Super Admin', 'Marketplace'], // tambahkan role lainnya
            'admin' => ['Super Admin'], // tambahkan role selain user
            default => [],
        };

        if (!empty($roles) && !$user->hasRole($roles)) {
            $request->user()->currentAccessToken()->delete();
            return response()->json(
                new WithoutDataResource(
                    Response::HTTP_FORBIDDEN,
                    'FORBIDDEN_ROLE',
                    'Akses Ditolak',
                    "Anda saat ini sedang memasuki sesi yang tidak diizinkan, sesi login anda telah dihapus."
                ),
                Response::HTTP_FORBIDDEN
            );
        }

        return $next($request);
    }
}
