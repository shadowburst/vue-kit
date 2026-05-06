<?php

declare(strict_types=1);

use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Team\CurrentTeamController;
use App\Http\Controllers\Team\TeamController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('teams/create', [TeamController::class, 'create'])->name('teams.create');
    Route::put('current-team', [CurrentTeamController::class, 'update'])->name('current-team.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
});

require __DIR__.'/settings.php';
