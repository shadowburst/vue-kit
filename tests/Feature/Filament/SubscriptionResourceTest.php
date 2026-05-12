<?php

declare(strict_types=1);

use App\Actions\Membership\AssignMembership;
use App\Enums\Role\Role;
use App\Filament\Resources\SubscriptionResource\Pages\ListSubscriptions;
use App\Filament\Resources\SubscriptionResource\Pages\ViewSubscription;
use App\Filament\Resources\SubscriptionResource\RelationManagers\InvoicesRelationManager;
use App\Models\Subscription;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity as ActivityModel;
use Spatie\Permission\PermissionRegistrar;
use Stripe\StripeClient;
use Stripe\Subscription as StripeSubscription;
use Stripe\SubscriptionItem as StripeSubscriptionItem;

function makeSubscriptionOperator(): User
{
    $admin = User::factory()->createOne();
    $team  = Team::factory()->createOne(['owner_id' => $admin->id]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $admin->assignRole(Role::Admin->value);

    return $admin;
}

function bindFakeStripeClientForCancel(): void
{
    $fakeStripeSubscription = StripeSubscription::constructFrom(['id' => 'sub_test', 'status' => 'active']);
    $futureTimestamp        = time() + (30 * 24 * 60 * 60);
    $fakeStripeItem         = StripeSubscriptionItem::constructFrom([
        'id'                 => 'si_test',
        'current_period_end' => $futureTimestamp,
    ]);

    $fakeSubscriptions = new class($fakeStripeSubscription) {
        public function __construct(private StripeSubscription $subscription) {}

        /** @param array<string, mixed> $params */
        public function update(string $id, array $params): StripeSubscription
        {
            return $this->subscription;
        }
    };

    $fakeSubscriptionItems = new class($fakeStripeItem) {
        public function __construct(private StripeSubscriptionItem $item) {}

        /** @param array<string, mixed> $params */
        public function retrieve(string $id, array $params = []): StripeSubscriptionItem
        {
            return $this->item;
        }
    };

    app()->bind(StripeClient::class, fn () => (object) [
        'subscriptions'     => $fakeSubscriptions,
        'subscriptionItems' => $fakeSubscriptionItems,
    ]);
}

function bindFakeStripeClientForUpdate(): void
{
    $fakeStripeSubscription = StripeSubscription::constructFrom(['id' => 'sub_test', 'status' => 'active']);

    $fakeSubscriptions = new class($fakeStripeSubscription) {
        public function __construct(private StripeSubscription $subscription) {}

        /** @param array<string, mixed> $params */
        public function update(string $id, array $params): StripeSubscription
        {
            return $this->subscription;
        }
    };

    app()->bind(StripeClient::class, fn () => (object) [
        'subscriptions' => $fakeSubscriptions,
    ]);
}

function createActiveSubscription(Team $team, string $stripeId = 'sub_test'): Subscription
{
    /** @var Subscription $subscription */
    $subscription = $team->subscriptions()->create([
        'type'          => 'default',
        'stripe_id'     => $stripeId,
        'stripe_status' => 'active',
        'stripe_price'  => 'price_pro_monthly_test',
    ]);

    $subscription->items()->create([
        'stripe_id'      => 'si_test',
        'stripe_product' => 'prod_test',
        'stripe_price'   => 'price_pro_monthly_test',
        'quantity'       => 1,
    ]);

    return $subscription;
}

it('admin can list subscriptions', function (): void {
    $admin  = makeSubscriptionOperator();
    $team   = Team::factory()->createOne();
    createActiveSubscription($team, 'sub_list_test');

    $subscriptions = Subscription::all();

    $this->actingAs($admin);

    Livewire::test(ListSubscriptions::class)
        ->assertCanSeeTableRecords($subscriptions);
});

it('non-admin cannot access subscription list', function (): void {
    $user = User::factory()->createOne();

    $this->actingAs($user)->get('/admin/subscriptions')->assertForbidden();
});

it('cancel at period end sets ends_at and logs activity', function (): void {
    $admin        = makeSubscriptionOperator();
    $team         = Team::factory()->createOne();
    $subscription = createActiveSubscription($team);

    bindFakeStripeClientForCancel();

    $this->actingAs($admin);

    Livewire::test(ViewSubscription::class, ['record' => $subscription->getRouteKey()])
        ->callAction('cancelAtPeriodEnd')
        ->assertNotified();

    expect($subscription->fresh()->ends_at)->not->toBeNull();

    $activity = ActivityModel::where('log_name', 'admin')
        ->where('description', 'subscription.cancel.period_end')
        ->where('subject_id', $subscription->id)
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBe($admin->id)
        ->and($activity->properties['team_id'])->toBe($team->id);
});

it('cancel is refused when team has non-owner members', function (): void {
    $admin  = makeSubscriptionOperator();
    $owner  = User::factory()->createOne();
    $team   = Team::factory()->createOne(['owner_id' => $owner->id]);
    $member = User::factory()->createOne();

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $owner->assignRole(Role::Manager->value);
    app(AssignMembership::class)->execute($member, $team, Role::Member);

    $subscription = createActiveSubscription($team, 'sub_overcap_test');

    $this->actingAs($admin);

    Livewire::test(ViewSubscription::class, ['record' => $subscription->getRouteKey()])
        ->callAction('cancelAtPeriodEnd');

    expect($subscription->fresh()->ends_at)->toBeNull();

    $cancelLog = ActivityModel::where('log_name', 'admin')
        ->where('description', 'subscription.cancel.period_end')
        ->where('subject_id', $subscription->id)
        ->first();

    expect($cancelLog)->toBeNull();
});

it('resume clears ends_at and logs activity when on grace period', function (): void {
    $admin        = makeSubscriptionOperator();
    $team         = Team::factory()->createOne();
    $subscription = $team->subscriptions()->create([
        'type'          => 'default',
        'stripe_id'     => 'sub_resume_test',
        'stripe_status' => 'active',
        'stripe_price'  => 'price_pro_monthly_test',
        'ends_at'       => Carbon::now()->addDays(10),
    ]);

    bindFakeStripeClientForUpdate();

    $this->actingAs($admin);

    Livewire::test(ViewSubscription::class, ['record' => $subscription->getRouteKey()])
        ->callAction('resume')
        ->assertNotified();

    expect($subscription->fresh()->ends_at)->toBeNull();

    $activity = ActivityModel::where('log_name', 'admin')
        ->where('description', 'subscription.resume')
        ->where('subject_id', $subscription->id)
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBe($admin->id);
});

it('extend trial updates trial_ends_at and logs old and new dates', function (): void {
    $admin       = makeSubscriptionOperator();
    $team        = Team::factory()->createOne();
    $originalEnd = Carbon::now()->addDays(5)->startOfDay();

    $subscription = $team->subscriptions()->create([
        'type'           => 'default',
        'stripe_id'      => 'sub_trial_test',
        'stripe_status'  => 'trialing',
        'stripe_price'   => 'price_pro_monthly_test',
        'trial_ends_at'  => $originalEnd,
    ]);

    bindFakeStripeClientForUpdate();

    $newDate = Carbon::now()->addDays(30)->toDateString();

    $this->actingAs($admin);

    Livewire::test(ViewSubscription::class, ['record' => $subscription->getRouteKey()])
        ->callAction('extendTrial', data: ['trial_ends_at' => $newDate])
        ->assertNotified();

    expect($subscription->fresh()->trial_ends_at->toDateString())->toBe($newDate);

    $activity = ActivityModel::where('log_name', 'admin')
        ->where('description', 'subscription.trial.extend')
        ->where('subject_id', $subscription->id)
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBe($admin->id)
        ->and($activity->properties['old_date'])->toBe($originalEnd->toDateString())
        ->and($activity->properties['new_date'])->toBe($newDate);
});

it('invoices relation manager renders without error when team has no stripe id', function (): void {
    $admin        = makeSubscriptionOperator();
    $team         = Team::factory()->createOne(['stripe_id' => null]);
    $subscription = createActiveSubscription($team, 'sub_inv_test');

    $this->actingAs($admin);

    Livewire::test(InvoicesRelationManager::class, [
        'ownerRecord' => $subscription,
        'pageClass'   => ViewSubscription::class,
    ])
        ->assertSuccessful();
});

it('open in stripe header action is visible when team has a stripe id', function (): void {
    $admin        = makeSubscriptionOperator();
    $team         = Team::factory()->createOne(['stripe_id' => 'cus_test123']);
    $subscription = createActiveSubscription($team, 'sub_stripe_test');

    $this->actingAs($admin);

    Livewire::test(ViewSubscription::class, ['record' => $subscription->getRouteKey()])
        ->assertActionExists('openInStripe');
});
