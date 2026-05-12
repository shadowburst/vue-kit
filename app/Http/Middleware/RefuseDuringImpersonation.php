<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RefuseDuringImpersonation
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->isImpersonated()) {
            abort(403, __('admin.impersonation.refuse_mutation'));
        }

        return $next($request);
    }
}
