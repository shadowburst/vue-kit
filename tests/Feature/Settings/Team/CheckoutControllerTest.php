<?php

declare(strict_types=1);

use App\Actions\Team\CreateTeam;
use App\Enums\Role\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\PermissionRegistrar;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Customer as StripeCustomer;
use Stripe\StripeClient;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

beforeEach(function (): void {
    seed(RolePermissionSeeder::class);

    config([
        'billing.tiers.pro.monthly' => 'price_pro_monthly_test',
        'billing.tiers.pro.yearly'  => 'price_pro_yearly_test',
    ]);
});

/**
 * Binds a fake StripeClient that captures the checkout session create params.
 *
 * @param array<string, mixed> $capturedParams Reference to capture the params passed to sessions->create()
 */
function bindFakeStripeClient(array &$capturedParams): void
{
    $fakeCustomer  = StripeCustomer::constructFrom(['id' => 'cus_fake_test']);
    $fakeCustomers = new class($fakeCustomer) {
        public function __construct(
            private StripeCustomer $customer,
        ) {}

        public function create(array $options, array $requestOptions = []): StripeCustomer
        {
            return $this->customer;
        }
    };

    $fakeSession  = StripeSession::constructFrom(['url' => 'https://checkout.stripe.com/pay/test-session']);
    $fakeSessions = new class($fakeSession, $capturedParams) {
        /** @param array<string, mixed> $capturedParams */
        public function __construct(
            private StripeSession $session,
            private array &$capturedParams,
        ) {}

        /** @param array<string, mixed> $params */
        public function create(array $params): StripeSession
        {
            $this->capturedParams = $params;

            return $this->session;
        }
    };

    app()->bind(StripeClient::class, fn () => (object) [
        'customers' => $fakeCustomers,
        'checkout'  => (object) ['sessions' => $fakeSessions],
    ]);
}

test('checkout redirects to Stripe for a Monthly interval', function (): void {
    $user = User::factory()->createOne();
    $team = (new CreateTeam)->execute('Test Team', $user);
    $user->update(['current_team_id' => $team->id]);

    /** @var array<string, mixed> $capturedParams */
    $capturedParams = [];
    bindFakeStripeClient($capturedParams);

    $response = actingAs($user)->post(route('teams.checkout.store'), ['interval' => 'monthly']);

    $response->assertRedirect('https://checkout.stripe.com/pay/test-session');
    expect(data_get($capturedParams, 'line_items.0.price'))->toBe('price_pro_monthly_test');
    expect($capturedParams['mode'])->toBe('subscription');
    expect($capturedParams['allow_promotion_codes'])->toBeTrue();
});

test('checkout redirects to Stripe for a Yearly interval', function (): void {
    $user = User::factory()->createOne();
    $team = (new CreateTeam)->execute('Test Team', $user);
    $user->update(['current_team_id' => $team->id]);

    /** @var array<string, mixed> $capturedParams */
    $capturedParams = [];
    bindFakeStripeClient($capturedParams);

    $response = actingAs($user)->post(route('teams.checkout.store'), ['interval' => 'yearly']);

    $response->assertRedirect('https://checkout.stripe.com/pay/test-session');
    expect(data_get($capturedParams, 'line_items.0.price'))->toBe('price_pro_yearly_test');
});

test('checkout returns 403 for Admin without subscription.resume', function (): void {
    $owner = User::factory()->createOne();
    $team  = (new CreateTeam)->execute('Test Team', $owner);

    $admin = User::factory()->createOne(['current_team_id' => $team->id]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $admin->assignRole(Role::Manager->value);

    $response = actingAs($admin)->post(route('teams.checkout.store'), ['interval' => 'monthly']);

    $response->assertForbidden();
});

test('checkout rejects an unknown interval with a validation error', function (): void {
    $user = User::factory()->createOne();
    $team = (new CreateTeam)->execute('Test Team', $user);
    $user->update(['current_team_id' => $team->id]);

    $response = actingAs($user)->post(route('teams.checkout.store'), ['interval' => 'quarterly']);

    $response->assertSessionHasErrors('interval');
});
