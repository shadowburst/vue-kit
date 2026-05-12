---
status: accepted
---

# Filament admin coverage enforced by Pest schema-coverage test

Two Pest tests enforce that every model and every column reachable from `App\Models` is surfaced in the Operator panel — or is explicitly excluded. (1) An architecture test (ADR-0004) asserts every Eloquent class in `App\Models` has a corresponding `App\Filament\Resources\*Resource`. (2) A feature test introspects each Resource's form and table and asserts every column from `Schema::getColumnListing()` on the underlying model is either represented or named in an explicit allow-list. The allow-list is a single array in the test file with a comment per entry explaining why the column is hidden. The test failure message points the next contributor at exactly what's missing and where to fix it.

## Considered options

- **Convention-only** — a CLAUDE.md note that says "when you add a column, update the Resource." Rejected. The premise of the project is that agents and humans share the codebase; CLAUDE.md is read but not failing. Drift is silent and accumulates between releases.
- **Generator-first workflow** — every Resource is created with `php artisan make:filament-resource X --generate`, which scaffolds from the schema. New columns mean re-running the generator and merging. Rejected as the primary mechanism. The generator is a one-shot scaffold; re-running clobbers customizations like custom form components, conditional visibility, and relation-manager wiring. Useful as a starting point, useless as ongoing enforcement.
- **Schema-coverage test only** — skip the resource-existence arch test, rely on the column test. Rejected because a missing Resource means there's nothing to introspect — a new model with no Resource passes the column test trivially (zero columns × zero coverage = vacuously satisfied). Both tests are needed.
- **Both tests with a maintained allow-list** (chosen). The arch test catches new models; the column test catches new fields; the allow-list is the explicit escape hatch with mandatory justification.

## Why

The original ask — "any future changes to the data structure should be reflected in the admin to keep them in sync" — is a hope unless something fails. The whole point of this ADR is converting silent drift into a loud test failure. Pest is the right vehicle because (a) the project already encodes architectural conventions as Pest arch tests per ADR-0004, (b) Pest runs in CI, and (c) the failure message can name the exact column and Resource at fault.

The allow-list is load-bearing. Without it, the test would either fail on every project (passwords, two-factor secrets, payment-method fingerprints — fields that *should not* appear in any admin UI) or it would degrade to a hand-written list of "fields to check," which is the same drift problem in a different file. With it, every excluded column is a deliberate, single-line decision with a comment.

The cost of the test is bounded. It runs once per Resource per test invocation; introspection of `Schema::getColumnListing()` and walking a Filament form/table is cheap. The test does not run Filament's renderer — it inspects schema metadata. No Livewire boot, no view rendering.

## Consequences

- A new test file `tests/Architecture/FilamentResourceCoverageTest.php` adds the arch expectation. A custom Pest expectation (`->toHaveCorrespondingResourceIn`) is added to `tests/Pest.php` and named consistently with existing custom expectations.
- A new test file `tests/Feature/Filament/SchemaCoverageTest.php` defines the column test. Structure: a dataset of Resource classes, each row asserts coverage. Failure messages are formatted as: *"Column `X` exists on `Y` but is not surfaced in `YResource` (form or table) and is not in the allow-list. Add it to the Resource, or to the allow-list with a comment justifying the exclusion."*
- The allow-list lives in the test file, not in config or in the Resource. Default entries: `id`, `created_at`, `updated_at`, `deleted_at`, `password`, `remember_token`, `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`, `pm_type`, `pm_last_four`, `stripe_id`, `trial_ends_at`. Each entry has a `// ` comment giving the reason (e.g., `// secret — never displayed`).
- New models added to `App\Models` cause the arch test to fail until a corresponding Resource exists. New columns cause the column test to fail until either the Resource is updated or the allow-list is extended.
- Computed/virtual columns (Eloquent attributes like `Team::features` accessor) are not in `Schema::getColumnListing()` and are out of scope. The test covers persisted columns only.
- Pivot tables and Spatie's permission tables are not under `App\Models` and are out of scope. Membership data is surfaced via the Team Resource's relation manager (per ADR-0001's "implicit membership" stance), not as its own Resource.
- Models that are deliberately admin-only and have no panel exposure (none today; possibly internal queue/cache tables in the future) must still satisfy the arch test or be added to a separate exclusion list with an ADR-level justification.
- An entry in the project's CLAUDE.md under "Application Structure & Architecture" points contributors at this test as the source of truth for the convention.
