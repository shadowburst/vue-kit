<?php

declare(strict_types=1);

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

final class TeamController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('teams/Create');
    }
}
