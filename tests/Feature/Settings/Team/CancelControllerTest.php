<?php

declare(strict_types=1);

use App\Actions\Team\CreateTeam;
use App\Enums\Role\Role;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity as ActivityModel;
use Spatie\Permission\PermissionRegistrar;
use Stripe\StripeClient;
use Stripe\Subscription as StripeSubscription;
use Stripe\SubscriptionItem as StripeSubscriptionItem;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

beforeEach(function (): void {
    seed(RolePermissionSeeder::class);
});

/**
 * Binds a fake StripeClient that handles subscription update and item retrieval for cancel tests.
 */
function bindFakeCancelStripeClient(): void
{
    $fakeStripeSubscription = StripeSubscription::constructFrom(['id' => 'sub_test', 'status' => 'active']);
    $futureTimestamp = time() + (30 * 24 * 60 * 60);
    $fakeStripeItem = StripeSubscriptionItem::constructFrom([
        'id' => 'si_test',
        'current_period_end' => $futureTimestamp,
    ]);

    $fakeSubscriptions = new class($fakeStripeSubscription)
    {
        public function __construct(
            private StripeSubscription $subscription,
        ) {}

        /** @param array<string, mixed> $params */
        public function update(string $id, array $params): StripeSubscription
        {
            return $this->subscription;
        }
    };

    $fakeSubscriptionItems = new class($fakeStripeItem)
    {
        public function __construct(
            private StripeSubscriptionItem $item,
        ) {}

        /** @param array<string, mixed> $params */
        public function retrieve(string $id, array $params = []): StripeSubscriptionItem
        {
            return $this->item;
        }
    };

    app()->bind(StripeClient::class, fn () => (object) [
        'subscriptions' => $fakeSubscriptions,
        'subscriptionItems' => $fakeSubscriptionItems,
    ]);
}

test('cancel sets ends_at and redirects to billing for an Owner', function (): void {
    $user = User::factory()->createOne();
    $team = (new CreateTeam)->execute('Test Team', $user);
    $user->update(['current_team_id' => $team->id]);

    $subscription = $team->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test',
        'stripe_status' => 'active',
        'stripe_price' => 'price_pro_monthly_test',
    ]);

    /** @mago-expect analysis:mixed-method-access */
    $subscription
        ->items()
        ->create([
            'stripe_id' => 'si_test',
            'stripe_product' => 'prod_test',
            'stripe_price' => 'price_pro_monthly_test',
            'quantity' => 1,
        ]);

    bindFakeCancelStripeClient();

    $response = actingAs($user)->post(route('teams.billing.cancel.store'));

    $response->assertRedirect(route('teams.billing.show'));
    $endsAt = DB::table('subscriptions')->where('type', 'default')->value('ends_at');
    expect($endsAt)->not->toBeNull();

    $activity = ActivityModel::where('description', 'subscription.cancel.period_end')
        ->where('subject_type', Subscription::class)
        ->where('subject_id', $subscription->id)
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->log_name)->not->toBe('admin')
        ->and($activity->causer_id)->toBe($user->id);
});

test('cancel returns 403 for an Owner when over Free cap', function (): void {
    $owner = User::factory()->createOne();
    $team  = (new CreateTeam)->execute('Test Team', $owner);
    $owner->update(['current_team_id' => $team->id]);

    $member = User::factory()->createOne();
    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $member->assignRole(Role::Member->value);

    config(['billing.tiers.free.member_cap' => 0]);

    $response = actingAs($owner)->post(route('teams.billing.cancel.store'));

    $response->assertForbidden();
});

test('cancel returns 403 for an Admin', function (): void {
    $owner = User::factory()->createOne();
    $team = (new CreateTeam)->execute('Test Team', $owner);

    $admin = User::factory()->createOne(['current_team_id' => $team->id]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $admin->assignRole(Role::Manager->value);

    $response = actingAs($admin)->post(route('teams.billing.cancel.store'));

    $response->assertForbidden();
});

test('cancel returns 403 for a Member', function (): void {
    $owner = User::factory()->createOne();
    $team = (new CreateTeam)->execute('Test Team', $owner);

    $member = User::factory()->createOne(['current_team_id' => $team->id]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $member->assignRole(Role::Member->value);

    $response = actingAs($member)->post(route('teams.billing.cancel.store'));

    $response->assertForbidden();
});
