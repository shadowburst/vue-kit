<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

final class CurrentTeamController extends Controller
{
    // Stub — real implementation ships in #22
    public function update(): RedirectResponse
    {
        return redirect()->back();
    }
}
