<?php

declare(strict_types=1);

namespace Kami\Cocktail\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Kami\Cocktail\Models\User;
use Symfony\Component\HttpFoundation\Response;

final class EnsureUserIsNotAnonymized
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Defense in depth: tokens should already be revoked on anonymization
        if ($user instanceof User && $user->isAnonymized()) {
            abort(401, 'Account has been deleted.');
        }

        return $next($request);
    }
}
