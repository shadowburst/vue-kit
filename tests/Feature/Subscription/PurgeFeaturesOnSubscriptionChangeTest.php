<?php

declare(strict_types=1);

use App\Actions\Team\CreateTeam;
use App\Enums\Role\Role;
use App\Models\Team;
use App\Models\User;
use Laravel\Cashier\Events\WebhookHandled;
use Laravel\Pennant\Feature;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

/**
 * @return array{type: string, data: array{object: array{customer: string}}}
 */
function subscriptionPayload(string $type, string $stripeId): array
{
    return [
        'type' => $type,
        'data' => ['object' => ['customer' => $stripeId]],
    ];
}

dataset('subscription_events', [
    'customer.subscription.created',
    'customer.subscription.updated',
    'customer.subscription.deleted',
]);

it('purges team Pennant feature cache on subscription event', function (string $eventType): void {
    $team = Team::factory()->createOne(['stripe_id' => 'cus_test123']);

    Feature::define('pro', fn (Team $t) => true);
    Feature::for($team)->active('pro'); // evaluate and store in DB

    assertDatabaseHas('features', ['name' => 'pro']);

    event(new WebhookHandled(subscriptionPayload($eventType, 'cus_test123')));

    assertDatabaseMissing('features', ['name' => 'pro']);
})->with('subscription_events');

it('purges only the affected team and not other teams', function (): void {
    $team  = Team::factory()->createOne(['stripe_id' => 'cus_target']);
    $other = Team::factory()->createOne(['stripe_id' => 'cus_other']);

    Feature::define('pro', fn (Team $t) => true);
    Feature::for($team)->active('pro');
    Feature::for($other)->active('pro');

    event(new WebhookHandled(subscriptionPayload('customer.subscription.updated', 'cus_target')));

    assertDatabaseMissing('features', [
        'name'  => 'pro',
        'scope' => (new Team)->getMorphClass().'|'.$team->id,
    ]);

    assertDatabaseHas('features', [
        'name'  => 'pro',
        'scope' => (new Team)->getMorphClass().'|'.$other->id,
    ]);
});

it('is a no-op for unrelated webhook events', function (): void {
    $team = Team::factory()->createOne(['stripe_id' => 'cus_noop']);

    Feature::define('pro', fn (Team $t) => true);
    Feature::for($team)->active('pro');

    event(new WebhookHandled(subscriptionPayload('customer.updated', 'cus_noop')));

    assertDatabaseHas('features', ['name' => 'pro']);
});

it('does not delete memberships on subscription.deleted (voluntary or involuntary)', function (): void {
    $owner           = User::factory()->createOne();
    $team            = (new CreateTeam)->execute('Acme Corp', $owner);
    $team->stripe_id = 'cus_no_prune';
    $team->save();

    $member = User::factory()->createOne();
    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $member->assignRole(Role::Member->value);

    assertDatabaseHas('model_has_roles', ['model_id' => $member->id, 'team_id' => $team->id]);

    event(new WebhookHandled(subscriptionPayload('customer.subscription.deleted', 'cus_no_prune')));

    assertDatabaseHas('model_has_roles', ['model_id' => $member->id, 'team_id' => $team->id]);
    assertDatabaseHas('model_has_roles', ['model_id' => $owner->id, 'team_id' => $team->id]);
});

it('purges Pennant cache on subscription.deleted', function (): void {
    $owner           = User::factory()->createOne();
    $team            = (new CreateTeam)->execute('Acme Corp', $owner);
    $team->stripe_id = 'cus_pennant_deleted';
    $team->save();

    Feature::define('pro', fn (Team $t) => true);
    Feature::for($team)->active('pro');
    assertDatabaseHas('features', ['name' => 'pro']);

    event(new WebhookHandled(subscriptionPayload('customer.subscription.deleted', 'cus_pennant_deleted')));

    assertDatabaseMissing('features', ['name' => 'pro']);
});
