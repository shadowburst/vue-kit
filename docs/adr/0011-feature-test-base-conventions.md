# ADR 0011: Feature Test Base Conventions

- **Status:** Accepted
- **Date:** May 2026

## Context

The Pest Feature suite (`tests/Feature/**`) wires its test base in
`tests/Pest.php` via `pest()->extend(TestCase::class)->use(...)->beforeEach(...)`.
Three concerns interact in that wiring:

1. **Database lifecycle** — every Feature test needs a clean schema, but
   the existing setup migrates and transacts on every test regardless of
   whether the test ever queries the database.
2. **Role/permission seeding** — authorization (per ADR 0002) is
   permission-based, and permissions live in DB rows seeded by
   `RolePermissionSeeder`. ~16 Feature tests today repeat
   `seed(RolePermissionSeeder::class)` in their own `beforeEach`.
3. **Cache hygiene** — Spatie Permission caches resolved roles and
   permissions in `cache.permission`; this kit also keeps a static
   per-process model cache on the `Role` and `Permission` enums (see
   `App\Enums\Role\Role::modelCache()`). Both caches survive across
   tests in the same PHP process. Database refresh between tests reissues
   row IDs, so any cache that holds an ID becomes stale.

The previous wiring flushed only the enum-level static caches; Spatie's
own cache was flushed in only two specific tests. This was a latent bug:
any test that mutated roles or relied on freshly-issued IDs could read
stale `PermissionRegistrar` state. It hadn't bitten yet only because the
suite is small and seeded the same fixture every time.

This ADR records the three decisions that close those gaps and give
Feature tests a single, predictable base.

---

## Decision 1 — `LazilyRefreshDatabase` Over `RefreshDatabase`

### Choice Made

Feature tests use `Illuminate\Foundation\Testing\LazilyRefreshDatabase`
as their database trait, applied globally in `tests/Pest.php`:

```php
pest()
    ->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->beforeEach(...)
    ->in('Feature');
```

### Alternatives Considered

**Keep `RefreshDatabase`.**
*Rejected:* `RefreshDatabase` opens a transaction on every test even
when the test never queries the database. `LazilyRefreshDatabase` defers
the refresh + transaction until the first DB query, so pure render /
HTTP-shape tests skip the cost entirely.

**Use `DatabaseMigrations` (full `migrate:fresh` per test).**
*Rejected:* an order of magnitude slower, no correctness benefit over
the transaction-rollback approach used by both `RefreshDatabase` and
`LazilyRefreshDatabase` on the SQLite/MySQL drivers this kit supports.

**Apply the trait to the abstract `tests/TestCase.php` instead of
`tests/Pest.php`.**
*Rejected:* `TestCase` is also the parent of `tests/Unit/**` and
`tests/Arch/**`. Arch tests must never touch the DB, and putting DB
machinery in their parent class is misleading even if the lazy variant
makes it harmless. Keeping the trait in `tests/Pest.php` scoped to
`->in('Feature')` matches the file's existing `use(...)` boundary.

### Reasoning

`LazilyRefreshDatabase` is a drop-in replacement for `RefreshDatabase`
with strictly the same behaviour for tests that touch the DB and a free
pass for tests that don't. The wins are modest today (most current
Feature tests do hit the DB) but the convention pays off as the suite
grows: future Inertia render-shape tests, broadcasting tests, and
exception-page tests routinely don't query, and they shouldn't pay for
a transaction they never use.

### Consequences

- `tests/Pest.php` imports `LazilyRefreshDatabase` instead of
  `RefreshDatabase`.
- The `$seeder` property (Decision 2) is read by the lazy refresh path
  the same way it is by the eager one — no separate wiring needed.

---

## Decision 2 — Auto-Seed via `$seeder = RolePermissionSeeder::class`

### Choice Made

The Feature `beforeEach` sets `$this->seeder = RolePermissionSeeder::class`.
Laravel's `LazilyRefreshDatabase` runs that seeder automatically as part
of the lazy refresh, so every Feature test that touches the DB ends up
with the canonical role/permission rows in place before its first query.

The per-test `seed(RolePermissionSeeder::class)` calls scattered across
~16 Feature files are deleted. The only remaining explicit `seed()`
calls live in `tests/Feature/Permission/RolePermissionSeederTest.php`,
where the seeder itself is the system under test.

### Alternatives Considered

**Call `seed(RolePermissionSeeder::class)` inside the global
`beforeEach`.**
*Rejected:* this seeds eagerly, defeating half of `LazilyRefreshDatabase`'s
purpose. A test that doesn't touch the DB would still pay for the seeder
run.

**Set `protected bool $seed = true`** to run `DatabaseSeeder`.
*Rejected:* `DatabaseSeeder` also creates a `Test User` row
(`test@example.com`). Auto-seeding that user before every Feature test
would pollute fixtures and break assertions that count or list users.
`$seeder` targets exactly one seeder; `$seed` targets `DatabaseSeeder`,
which is wrong shape here.

**Keep per-test `seed()` calls.**
*Rejected:* the repetition is a footgun for new tests (it is easy to
forget the call and get a confusing "permission not found" error).
Centralising seeding eliminates the failure mode.

**Use a base trait per test family** (e.g. `WithRoles`,
`WithPermissions`).
*Rejected:* the kit has no Feature test today that does *not* need the
canonical role/permission set seeded — every test either authenticates
a user (which needs roles to attach) or asserts on permission-gated
behaviour. Splitting traits would create surface area for future drift
without solving a current problem.

### Reasoning

Centralising the seeder in the test base, gated by lazy refresh, is the
shortest path that satisfies all three goals at once: tests that need
the DB get seeded automatically, tests that don't never pay the cost,
and the pattern is one line in one file rather than 16 copies. The only
test-that-tests-the-seeder case (`RolePermissionSeederTest`) keeps its
explicit `seed()` calls because *those calls are the assertion subject*,
not boilerplate — the auto-seed simply means the seeder runs once
before the test body's first explicit invocation, which the seeder's
own idempotency guarantees is safe.

### Consequences

- `tests/Pest.php`'s `beforeEach` sets `$this->seeder = RolePermissionSeeder::class`.
- ~16 Feature test files lose their `seed(RolePermissionSeeder::class)`
  preamble.
- `tests/Feature/Permission/RolePermissionSeederTest.php` keeps its
  explicit `seed()` calls; its idempotency test now exercises the third
  run rather than the second, which is still a valid idempotency check.
- Any future seeder that needs to run before every Feature test would
  need to be merged into `RolePermissionSeeder` or replace it; the
  `$seeder` property holds exactly one class.
- **Seeding semantics — once per session, not per test.** The `$seeder`
  property is read by `migrate:fresh`, which the `LazilyRefreshDatabase`
  trait runs exactly once per PHP process (when
  `RefreshDatabaseState::$migrated` flips to true on the first
  DB-touching test). Seeded rows live *outside* any per-test
  transaction, so they persist across tests for free. This is the
  correct Laravel pattern but is easy to misread: setting
  `$this->seeder = null` inside a test body has *no effect* on rows
  already seeded.
- **Escape hatch for tests that need unseeded state.** A small number
  of tests assert behaviour when roles or permissions are absent
  (`RoleDoesNotExist`, `PermissionDoesNotExist`, `CreateTeam` rollback
  on missing roles, the cache-keyed-by-team test). These tests call
  `SpatieRole::query()->delete()` (or the permissions equivalent) at
  the start of the test. The delete runs inside the test transaction,
  so the rollback restores the seeded rows for the next test — no
  cross-test pollution.

---

## Decision 3 — Flush Both Spatie's Cache and the Enum Static Cache, Spatie First

### Choice Made

The Feature `beforeEach` flushes three caches in this order:

```php
->beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Role::flushModelCache();
    Permission::flushModelCache();
    $this->seeder = RolePermissionSeeder::class;
})
```

### Alternatives Considered

**Flush only Spatie's cache** (drop the enum `flushModelCache` calls).
*Rejected:* the static `$cache` array on `Role` and `Permission` enums
holds resolved `SpatieRole` / `SpatiePermission` model instances keyed
by name (and team id). It is independent from Spatie's cache and lives
for the entire PHP process. Without flushing it, a second test in the
same process would resolve `Role::Owner->model()` to a model row from
the previous test's transaction — by that point rolled back and
reissued with a different id.

**Flush only the enum cache** (the previous behaviour).
*Rejected:* this is the latent bug this ADR closes. `PermissionRegistrar`
holds permissions in `cache.permission` (driver `array` per
`phpunit.xml`), which is also per-process. After the lazy refresh
reissues role and permission ids, an unflushed `PermissionRegistrar`
returns stale rows and `$user->can(...)` checks silently misbehave.

**Run the enum flushes before Spatie's flush.**
*Rejected:* if any code path inside `beforeEach` (or downstream of it,
before the test body) resolves a role through the enum (`Role::Owner->model()`),
the enum's `??=` repopulates its cache by calling `SpatieRole::findByName`,
which itself reads from `PermissionRegistrar`'s cache. Flushing
Spatie first ensures any subsequent enum repopulation reads fresh data.

**Use a Spatie testing trait** (e.g. `WithoutPermissionsCache`).
*Rejected:* no such trait exists in the version of `spatie/laravel-permission`
this kit installs. The documented testing pattern is exactly the
`forgetCachedPermissions()` call used here.

**Move Spatie's flush to `setUp` on `TestCase` itself.**
*Rejected:* same rationale as the Decision 1 alternative — it pulls a
Feature-suite concern into the parent of Unit and Arch tests. The
`tests/Pest.php` boundary is the right one.

### Reasoning

Two independent caches both survive across tests in the same process,
both can hold stale ids after a database refresh, and both must be
cleared before any test code runs. Spatie's
`forgetCachedPermissions()` is the documented pattern; the enum static
cache is project-specific and its `flushModelCache` API exists exactly
for this case (see the dedicated tests in `PermissionModelTest` and
`RoleModelTest` that assert the flush works). Ordering Spatie first is
defensive against any code path that resolves an enum's `model()`
inside the same `beforeEach`.

### Consequences

- Tests that mutate role or permission rows (assigning, revoking,
  reseeding) are now safe to run in any order without per-test
  cache-clearing boilerplate.
- The two existing tests that call `flushModelCache()` and
  `forgetCachedPermissions()` inside their bodies
  (`tests/Feature/Permission/PermissionModelTest.php`,
  `tests/Feature/Role/RoleModelTest.php`) keep those calls — the calls
  are the system under test, not setup.
- A future migration off Spatie Permission would remove the first line;
  a future removal of the enum static cache (if it proves redundant
  against Spatie's own cache) would remove the next two. Neither is in
  scope for this ADR.
