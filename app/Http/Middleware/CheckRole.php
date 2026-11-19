<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            throw new AccessDeniedHttpException('Pengguna belum terautentikasi.');
        }

        // Normalize roles in case of comma-separated string or variable arguments
        $normalizedRoles = [];
        foreach ($roles as $r) {
            foreach (explode(',', $r) as $part) {
                $trimmed = trim($part);
                if ($trimmed !== '') {
                    $normalizedRoles[] = $trimmed;
                }
            }
        }

        // Check if user has any of the required roles
        $hasRequiredRole = false;
        foreach ($normalizedRoles as $role) {
            if ($user->hasRole($role)) {
                $hasRequiredRole = true;
                break;
            }
        }

        if (! $hasRequiredRole) {
            throw new AccessDeniedHttpException(
                sprintf('Akses dilarang. Fitur ini hanya dapat diakses oleh peran: %s.', implode(', ', $normalizedRoles))
            );
        }

        return $next($request);
    }
}
