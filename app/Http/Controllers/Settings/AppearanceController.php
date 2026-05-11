<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Data\Appearance\AppearanceEditProps;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

final class AppearanceController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('settings/Appearance', new AppearanceEditProps());
    }
}
