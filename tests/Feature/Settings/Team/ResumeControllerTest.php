<?php

declare(strict_types=1);

use App\Actions\Team\CreateTeam;
use App\Enums\Role\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Stripe\StripeClient;
use Stripe\Subscription as StripeSubscription;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

beforeEach(function (): void {
    seed(RolePermissionSeeder::class);
});

/**
 * Binds a fake StripeClient that handles subscription update for resume tests.
 */
function bindFakeResumeStripeClient(): void
{
    $fakeStripeSubscription = StripeSubscription::constructFrom(['id' => 'sub_test', 'status' => 'active']);

    $fakeSubscriptions = new class($fakeStripeSubscription) {
        public function __construct(
            private StripeSubscription $subscription,
        ) {}

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

test('resume clears ends_at and redirects to billing for an Owner on grace period', function (): void {
    $user = User::factory()->createOne();
    $team = (new CreateTeam)->execute('Test Team', $user);
    $user->update(['current_team_id' => $team->id]);

    $team->subscriptions()->create([
        'type'          => 'default',
        'stripe_id'     => 'sub_test',
        'stripe_status' => 'active',
        'stripe_price'  => 'price_pro_monthly_test',
        'ends_at'       => CarbonImmutable::now()->addDays(10),
    ]);

    bindFakeResumeStripeClient();

    $response = actingAs($user)->post(route('teams.billing.resume.store'));

    $response->assertRedirect(route('teams.billing.show'));
    $endsAt = DB::table('subscriptions')->where('type', 'default')->value('ends_at');
    expect($endsAt)->toBeNull();
});

test('resume is a no-op when subscription is not on grace period', function (): void {
    $user = User::factory()->createOne();
    $team = (new CreateTeam)->execute('Test Team', $user);
    $user->update(['current_team_id' => $team->id]);

    $team->subscriptions()->create([
        'type'          => 'default',
        'stripe_id'     => 'sub_test',
        'stripe_status' => 'active',
        'stripe_price'  => 'price_pro_monthly_test',
    ]);

    $response = actingAs($user)->post(route('teams.billing.resume.store'));

    $response->assertRedirect(route('teams.billing.show'));
    $endsAt = DB::table('subscriptions')->where('type', 'default')->value('ends_at');
    expect($endsAt)->toBeNull();
});

test('resume returns 403 for a Member', function (): void {
    $owner = User::factory()->createOne();
    $team  = (new CreateTeam)->execute('Test Team', $owner);

    $member = User::factory()->createOne(['current_team_id' => $team->id]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $member->assignRole(Role::Member->value);

    $response = actingAs($member)->post(route('teams.billing.resume.store'));

    $response->assertForbidden();
});
