<?php

declare(strict_types=1);

namespace App\Http\Controllers\Impersonation;

use App\Filament\Resources\UserResource;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ImpersonateController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        /** @var User $target */
        $target = auth()->user();

        if (! $target->isImpersonated()) {
            return redirect()->intended('/');
        }

        /** @var User $operator */
        $operator = app(\Lab404\Impersonate\Services\ImpersonateManager::class)->getImpersonator();

        activity('admin')
            ->causedBy($operator)
            ->performedOn($target)
            ->withProperties([
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent(),
            ])
            ->log('impersonation.stop');

        $target->leaveImpersonation();

        return redirect(UserResource::getUrl('view', ['record' => $target->getRouteKey()]));
    }
}
