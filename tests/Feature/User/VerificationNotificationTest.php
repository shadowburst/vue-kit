<?php

declare(strict_types=1);

use App\Http\Middleware\Team\SetCurrentTeam;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\withoutMiddleware;

beforeEach(function () {
    skipUnlessFortifyHas(Features::emailVerification());
    withoutMiddleware(SetCurrentTeam::class);
});

test('sends verification notification', function () {
    Notification::fake();

    $user = User::factory()->unverified()->createOne();

    actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect('/');

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('does not send verification notification if email is verified', function () {
    Notification::fake();

    $user = User::factory()->createOne();

    actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect(route('dashboard', absolute: false));

    Notification::assertNothingSent();
});
