<?php

declare(strict_types=1);

use App\Enums\Role\Role;
use App\Filament\Resources\ActivityResource;
use App\Filament\Resources\SubscriptionResource;
use App\Filament\Resources\TeamResource;
use App\Filament\Resources\UserResource;
use App\Models\Subscription;
use App\Models\Team;
use App\Models\User;
use Filament\Forms\Components\Field;
use Filament\Infolists\Components\Entry;
use Filament\Resources\Resource;
use Filament\Schemas\Component;
use Filament\Tables\Columns\Column;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema as DatabaseSchema;
use Livewire\Livewire;
use PHPUnit\Framework\ExpectationFailedException;
use Spatie\Activitylog\Models\Activity as ActivityModel;
use Spatie\Permission\PermissionRegistrar;

const HIDDEN_COLUMNS = [
    'default' => [
        'id' => 'primary key - Filament shows this implicitly',
        'created_at' => 'timestamp - Filament commonly surfaces this in read-only admin views',
        'updated_at' => 'timestamp - non-user-facing audit metadata',
        'deleted_at' => 'soft-delete marker',
        'password' => 'secret - never displayed; reset via dedicated action',
        'remember_token' => 'session secret',
        'two_factor_secret' => '2FA secret',
        'two_factor_recovery_codes' => '2FA secret',
        'two_factor_confirmed_at' => '2FA state - surfaced via the verified badge, not as a raw column',
        'pm_type' => 'Cashier payment-method internal',
        'pm_last_four' => 'Cashier payment-method internal',
    ],
    UserResource::class => [
        'current_team_id' => 'session-scoped team context, not an operator-managed profile field',
        'settings' => 'JSON document - the resource surfaces specific nested settings fields instead of raw JSON',
    ],
    TeamResource::class => [
        'owner_id' => 'surfaced through owner relationship fields instead of the raw foreign key',
        'stripe_id' => 'Cashier customer reference - exposed through dedicated operator billing flows',
        'trial_ends_at' => 'Cashier trial metadata lives on the Team billable model but is surfaced on Subscription Resource instead',
    ],
    SubscriptionResource::class => [
        'team_id' => 'surfaced through the owning team relationship instead of the raw foreign key',
        'type' => 'Cashier subscription type internal',
        'quantity' => 'Cashier quantity is not used by the current billing model',
        'stripe_id' => 'Cashier subscription reference - operators use the Open in Stripe flow instead of editing the raw ID',
    ],
    ActivityResource::class => [
        'log_name' => 'activity stream grouping metadata, not operator-editable data',
        'subject_type' => 'activity subject is surfaced as a formatted subject summary instead of the raw morph type',
        'subject_id' => 'activity subject is surfaced as a formatted subject summary instead of the raw morph ID',
        'event' => 'activity event name duplicates the human-readable description in this panel',
        'causer_type' => 'activity operator is surfaced through the causer relationship instead of the raw morph type',
        'causer_id' => 'activity operator is surfaced through the causer relationship instead of the raw morph ID',
        'attribute_changes' => 'activity diff payload is stored for audit detail, not list/table display',
        'properties' => 'activity metadata is surfaced in the infolist, not duplicated in the list view',
    ],
];

dataset('filamentResources', function (): array {
    return discoverPhpClasses(dirname(__DIR__, 3).'/app/Filament/Resources', 'App\\Filament\\Resources', '*Resource.php');
});

beforeEach(function (): void {
    $this->actingAs(makeOperator());
});

it('surfaces every persisted column for each resource or justifies the omission', function (string $resourceClass): void {
    /** @var class-string<resource> $resourceClass */
    $modelClass = $resourceClass::getModel();
    $columns = DatabaseSchema::getColumnListing((new $modelClass)->getTable());
    $surfacedColumns = collect([
        collectFormColumns($resourceClass),
        collectTableColumns($resourceClass),
        collectInfolistColumns($resourceClass),
    ])->flatten()->unique()->values()->all();

    assertResourceColumnsAreCovered(
        modelClass: $modelClass,
        resourceClass: $resourceClass,
        persistedColumns: $columns,
        surfacedColumns: $surfacedColumns,
        hiddenColumns: hiddenColumnsForResource($resourceClass),
    );
})->with('filamentResources');

it('emits the documented failure message for an uncovered column', function (): void {
    expect(fn (): null => assertResourceColumnsAreCovered(
        modelClass: User::class,
        resourceClass: 'App\\Filament\\Resources\\ExampleUserResource',
        persistedColumns: ['missing_column'],
        surfacedColumns: [],
        hiddenColumns: [],
    ))->toThrow(
        ExpectationFailedException::class,
        'Column `missing_column` exists on `App\\Models\\User` but is not surfaced in `App\\Filament\\Resources\\ExampleUserResource` (form, table, or infolist) and is not in the allow-list. Add it to the Resource, or to the allow-list with a comment justifying the exclusion.',
    );
});

function makeOperator(): User
{
    $admin = User::factory()->createOne();
    $team = Team::factory()->createOne(['owner_id' => $admin->id]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $admin->assignRole(Role::Admin->value);

    return $admin;
}

/**
 * @param  class-string<resource>  $resourceClass
 * @return array<int, string>
 */
function collectFormColumns(string $resourceClass): array
{
    $page = makeSchemaPage($resourceClass, ['edit', 'view']);
    $form = $page?->getSchema('form');

    if ($form === null) {
        return [];
    }

    return collect($form->getFlatFields())
        ->map(fn (Field $field): string => $field->getStatePath())
        ->filter()
        ->flatMap(fn (string $field): array => normalizeSurfacedColumn($field))
        ->unique()
        ->values()
        ->all();
}

/**
 * @param  class-string<resource>  $resourceClass
 * @return array<int, string>
 */
function collectTableColumns(string $resourceClass): array
{
    $indexPageClass = $resourceClass::getPages()['index']->getPage();
    $component = Livewire::test($indexPageClass)->instance();

    if (! $component instanceof HasTable) {
        return [];
    }

    return collect($component->getTable()->getColumns())
        ->map(fn (Column $column): string => $column->getName())
        ->flatMap(fn (string $column): array => normalizeSurfacedColumn($column))
        ->unique()
        ->values()
        ->all();
}

/**
 * @param  class-string<resource>  $resourceClass
 * @return array<int, string>
 */
function collectInfolistColumns(string $resourceClass): array
{
    $page = makeSchemaPage($resourceClass, ['view']);
    $infolist = $page?->getSchema('infolist');

    if ($infolist === null) {
        return [];
    }

    return collect($infolist->getFlatComponents(withActions: false))
        ->whereInstanceOf(Component::class)
        ->map(function (Component $component): ?string {
            if (! $component instanceof Entry) {
                return null;
            }

            return $component->getName();
        })
        ->filter()
        ->flatMap(fn (string $column): array => normalizeSurfacedColumn($column))
        ->unique()
        ->values()
        ->all();
}

/**
 * @param  class-string<resource>  $resourceClass
 * @param  list<string>  $pageNames
 */
function makeSchemaPage(string $resourceClass, array $pageNames): ?object
{
    $pages = $resourceClass::getPages();

    foreach ($pageNames as $pageName) {
        if (! array_key_exists($pageName, $pages)) {
            continue;
        }

        $pageClass = $pages[$pageName]->getPage();
        $record = makeRecordForResource($resourceClass);

        return Livewire::test($pageClass, ['record' => $record->getRouteKey()])->instance();
    }

    return null;
}

/**
 * @param  class-string<resource>  $resourceClass
 */
function makeRecordForResource(string $resourceClass): object
{
    return match ($resourceClass) {
        UserResource::class => User::factory()->createOne(),
        TeamResource::class => Team::factory()->createOne(),
        SubscriptionResource::class => makeSubscriptionRecord(),
        ActivityResource::class => makeActivityRecord(),
        default => throw new RuntimeException("No record factory is defined for resource [{$resourceClass}]."),
    };
}

function makeSubscriptionRecord(): Subscription
{
    $owner = User::factory()->createOne();
    $team = Team::factory()->createOne(['owner_id' => $owner->id]);

    return Subscription::query()->create([
        'team_id' => $team->id,
        'type' => 'default',
        'stripe_id' => (string) str()->uuid(),
        'stripe_status' => 'active',
        'stripe_price' => 'price_pro_monthly',
        'quantity' => 1,
        'trial_ends_at' => now()->addWeek(),
        'ends_at' => null,
    ]);
}

function makeActivityRecord(): ActivityModel
{
    $operator = makeOperator();
    $target = User::factory()->createOne();

    return activity('admin')
        ->causedBy($operator)
        ->performedOn($target)
        ->withProperties(['example' => true])
        ->event('updated')
        ->log('user.updated');
}

/**
 * @param  class-string<resource>  $resourceClass
 * @return list<string>
 */
function hiddenColumnsForResource(string $resourceClass): array
{
    return array_keys([
        ...HIDDEN_COLUMNS['default'],
        ...(HIDDEN_COLUMNS[$resourceClass] ?? []),
    ]);
}

/**
 * @param  list<string>  $persistedColumns
 * @param  list<string>  $surfacedColumns
 * @param  list<string>  $hiddenColumns
 */
function assertResourceColumnsAreCovered(
    string $modelClass,
    string $resourceClass,
    array $persistedColumns,
    array $surfacedColumns,
    array $hiddenColumns,
): void {
    $missingColumns = array_values(array_diff($persistedColumns, $surfacedColumns, $hiddenColumns));

    expect($missingColumns)->toBeEmpty(collect($missingColumns)
        ->map(fn (string $column): string => "Column `{$column}` exists on `{$modelClass}` but is not surfaced in `{$resourceClass}` (form, table, or infolist) and is not in the allow-list. Add it to the Resource, or to the allow-list with a comment justifying the exclusion.")
        ->implode("\n"));
}

/**
 * @return list<string>
 */
function normalizeSurfacedColumn(string $field): array
{
    if ($field === 'verified') {
        return ['verified', 'email_verified_at'];
    }

    $segments = explode('.', $field);
    $normalized = [Arr::first($segments), $field];

    if (count($segments) > 1) {
        $normalized[] = Arr::first($segments).'_id';
    }

    return array_values(array_filter(array_unique($normalized), fn (?string $value): bool => filled($value)));
}
