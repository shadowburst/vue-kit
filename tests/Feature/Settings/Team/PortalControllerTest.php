<?php

declare(strict_types=1);

use App\Actions\Team\CreateTeam;
use App\Enums\Role\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\PermissionRegistrar;
use Stripe\BillingPortal\Session as StripeBillingPortalSession;
use Stripe\StripeClient;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

beforeEach(function (): void {
    seed(RolePermissionSeeder::class);
});

/**
 * Binds a fake StripeClient that returns a portal session URL.
 */
function bindFakeBillingPortalClient(): void
{
    $fakeSession = StripeBillingPortalSession::constructFrom(['url' => 'https://billing.stripe.com/session/test']);

    $fakeSessions = new class($fakeSession) {
        public function __construct(
            private StripeBillingPortalSession $session,
        ) {}

        /** @param array<string, mixed> $params */
        public function create(array $params): StripeBillingPortalSession
        {
            return $this->session;
        }
    };

    app()->bind(StripeClient::class, fn () => (object) [
        'billingPortal' => (object) ['sessions' => $fakeSessions],
    ]);
}

test('portal redirects to Stripe Billing Portal for an Owner with an active subscription', function (): void {
    $user = User::factory()->createOne();
    $team = (new CreateTeam)->execute('Test Team', $user);
    $user->update(['current_team_id' => $team->id]);

    $team->forceFill(['stripe_id' => 'cus_test_portal'])->save();
    $team->subscriptions()->create([
        'type'          => 'default',
        'stripe_id'     => 'sub_test',
        'stripe_status' => 'active',
        'stripe_price'  => 'price_pro_monthly',
    ]);

    bindFakeBillingPortalClient();

    $response = actingAs($user)->get(route('teams.billing.portal.show'));

    $response->assertRedirect('https://billing.stripe.com/session/test');
});

test('portal returns 403 for an Admin', function (): void {
    $owner = User::factory()->createOne();
    $team  = (new CreateTeam)->execute('Test Team', $owner);

    $admin = User::factory()->createOne(['current_team_id' => $team->id]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $admin->assignRole(Role::Manager->value);

    $response = actingAs($admin)->get(route('teams.billing.portal.show'));

    $response->assertForbidden();
});

test('portal returns 403 for a Member', function (): void {
    $owner = User::factory()->createOne();
    $team  = (new CreateTeam)->execute('Test Team', $owner);

    $member = User::factory()->createOne(['current_team_id' => $team->id]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $member->assignRole(Role::Member->value);

    $response = actingAs($member)->get(route('teams.billing.portal.show'));

    $response->assertForbidden();
});
