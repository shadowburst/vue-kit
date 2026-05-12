<?php

declare(strict_types=1);

namespace App\Http\Middleware\Impersonation;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

final class RefuseDuringImpersonation
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user !== null && $user->isImpersonated() === true) {
            $message = __('admin.impersonation.refuse_mutation');

            abort(403, is_string($message) ? $message : 'Impersonated sessions cannot perform this action.');
        }

        $response = $next($request);

        if (! $response instanceof Response) {
            throw new RuntimeException('Middleware stack returned an invalid response.');
        }

        return $response;
    }
}
