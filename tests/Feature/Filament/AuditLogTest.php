<?php

declare(strict_types=1);

use App\Enums\Role\Role;
use App\Filament\Resources\ActivityResource;
use App\Models\Team;
use App\Models\User;
use Spatie\Activitylog\Models\Activity as ActivityModel;
use Spatie\Permission\PermissionRegistrar;

it('model update by admin causer stores activity with log_name=admin', function (): void {
    $admin = User::factory()->createOne();
    $team  = Team::factory()->createOne(['owner_id' => $admin->id]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $admin->assignRole(Role::Admin->value);

    $this->actingAs($admin);
    $admin->update(['name' => 'Operator Updated']);

    $activity = ActivityModel::where('subject_type', User::class)
        ->where('subject_id', $admin->id)
        ->where('description', 'updated')
        ->latest()
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->log_name)->toBe('admin');
});

it('model update by non-admin causer does not store admin-log entry', function (): void {
    $user = User::factory()->createOne();

    $this->actingAs($user);
    $user->update(['name' => 'Regular Updated']);

    $activity = ActivityModel::where('subject_type', User::class)
        ->where('subject_id', $user->id)
        ->where('description', 'updated')
        ->latest()
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->log_name)->not->toBe('admin');
});

it('ActivityResource query only returns admin-log entries', function (): void {
    activity('admin')->log('admin.test');
    activity('default')->log('default.test');

    $ids = ActivityResource::getEloquentQuery()->pluck('log_name')->unique()->all();

    expect($ids)->toBe(['admin']);
});

it('direct activity helper creates admin-log entry with causer and subject', function (): void {
    $admin  = User::factory()->createOne();
    $target = User::factory()->createOne();

    activity('admin')
        ->causedBy($admin)
        ->performedOn($target)
        ->log('test.action');

    $activity = ActivityModel::where('log_name', 'admin')
        ->where('description', 'test.action')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBe($admin->id)
        ->and($activity->subject_id)->toBe($target->id);
});

it('Team model update by admin causer stores activity with log_name=admin', function (): void {
    $admin = User::factory()->createOne();
    $team  = Team::factory()->createOne(['owner_id' => $admin->id]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $admin->assignRole(Role::Admin->value);

    $this->actingAs($admin);
    $team->update(['name' => 'Renamed Team']);

    $activity = ActivityModel::where('subject_type', Team::class)
        ->where('subject_id', $team->id)
        ->where('description', 'updated')
        ->latest()
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->log_name)->toBe('admin');
});
