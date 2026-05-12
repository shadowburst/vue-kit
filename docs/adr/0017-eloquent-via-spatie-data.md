# ADR 0017: Backend Shapes Cross the Inertia/JS Boundary via Spatie Data

- **Status:** Accepted
- **Date:** May 2026

## Context

ADR-0010 established that all backend-shaped types come from Wayfinder. Decision 3 of that ADR left room for Eloquent shapes specifically via `App.Models.*`. Three concrete Wayfinder failure modes block that approach:

1. **`Inertia.Pages.*` inlines resource shapes.** When a controller returns `Inertia::render('teams/Index', ['user' => UserResource::make($user)])`, Wayfinder reads the array literal and emits the resource's `toArray()` shape *inline* on the page type — not as a reference to a named `UserResource` type. Frontend pages have no way to import a typed `User` shape derived from the resource; they get a fresh anonymous shape per page.
2. **Resources living only in shared data are missed.** `HandleInertiaRequests::share()` wraps `auth.user` in `UserResource`, but Wayfinder doesn't pick the resource up as a named type — only the inline shape ends up on `Inertia.SharedData`. Sub-components that need to type a `user` prop can't reference the resource shape from outside.
3. **`TypeScriptType` attribute on `JsonResource` is fragile.** The workaround puts `#[TypeScriptType([...])]` on each resource class with manual type strings. Backslash-namespace paths (`\App\Enums\Subscription\SubscriptionTier`) end up as `undefined` in the generated TS because TypeScript can't parse them as namespace paths.

The companion package — `spatie/laravel-typescript-transformer` v3 — is already installed. Its `LaravelDataTypeScriptTransformerExtension` reads property declarations on Spatie Data classes directly and produces *named* TS types (`App.Data.User.UserResource`) that any consumer can reference. The missing piece is switching the serialisation layer from `JsonResource` to Spatie Data classes so the transformer can do the job Wayfinder doesn't.

This document replaces an earlier version of ADR-0017 that mandated `JsonResource`. The previous text addressed the same problem with a different mechanic.

---

## Decision 1 — Spatie Data Replaces JsonResource as the Serialisation Layer

### Choice Made

All backend-shaped types crossing the Inertia/JS boundary are expressed as Spatie Data classes (`Spatie\LaravelData\Data` or `Spatie\LaravelData\Resource`). Spatie's TypeScript Transformer emits named TS types for each class, consumed by frontend code as `App.Data.{Noun}.{ClassName}`.

`Illuminate\Http\Resources\Json\JsonResource` is no longer used.

### Alternatives Considered

**Keep `JsonResource` and patch type-resolution bugs with `TypeScriptType` attributes.**
*Rejected:* the patch surface is unbounded. Each manual type string is a maintenance point that drifts with the model. Namespace paths with backslashes produce `undefined`; enum references don't resolve; lazy relationships have no clean way to express optionality. The Spatie Data class's typed property declarations express all three correctly as a side effect of being typed PHP.

**Wayfinder-only with everything inlined.**
*Rejected:* the failure modes in the context above are unfixable without reading PHP types directly. Wayfinder reads array literals and method return shapes; it cannot resolve a resource's `toArray()` return to a named type because the return *is* the anonymous shape.

### Reasoning

The serialisation contract becomes a typed PHP class. Backend authors write `public int $id; public string $name;` instead of `'id' => $this->id, 'name' => $this->name`. The transformer reads the constructor; the frontend gets `App.Data.User.UserResource` as a named, referenceable type. Drift between backend and frontend fails the TypeScript build because every consumer points at the same named type — adding or removing a field breaks every consumer that reads it.

### Consequences

- `app/Http/Resources/` is deleted.
- `composer.json` continues to require `spatie/laravel-data` and `spatie/laravel-typescript-transformer` v3.
- `TypeScriptTransformerServiceProvider` writes to `resources/js/spatie/types.d.ts` via `GlobalNamespaceWriter`.

---

## Decision 2 — Four-Stub Taxonomy: `data`, `data-request`, `data-resource`, `data-props`

### Choice Made

Four artisan stubs encode four uses of Spatie Data:

| Stub | Base class | Purpose |
|---|---|---|
| `data.stub` | `Spatie\LaravelData\Data` | Free-standing typed DTOs, including sub-models updated through a parent's form |
| `data-request.stub` | `Spatie\LaravelData\Data` | One per route that takes input beyond route bindings |
| `data-resource.stub` | `Spatie\LaravelData\Resource` | One per Eloquent model — the model's serialisation contract |
| `data-props.stub` | `Spatie\LaravelData\Resource` | One per Inertia page — the page's prop shape |

`Resource` (output-only) is used wherever no input validation pipeline runs. `Data` (input + output) is used wherever a class is hydrated from a request or composed into a parent's form. A single class is not round-tripped across input and output — input shapes (`Data`) and output shapes (`Resource`) are distinct files.

### Alternatives Considered

**Single `data` stub, all classes extend `Data`.**
*Rejected:* output-only classes don't need the validation pipeline. Forcing every Resource through `Data` runs `rules()` and `attributes()` resolution on every serialisation.

**Combine `data-resource` and `data-props` into one stub.**
*Rejected:* the conventions diverge. Resources are per-model with `Lazy::whenLoaded` for relationships and dynamic `defaultIncludes()`; props are per-page with `Lazy::inertiaDeferred` for deferred fields.

**Combine `data` and `data-request`.**
*Rejected:* the request-class case carries intent ("this class hydrates from an HTTP request"). Stub identity records the intent.

### Reasoning

Four stubs match four mental categories. The base class is determined by the stub; the naming convention is enforced by review.

### Consequences

- Four stub files live in `stubs/data.stub`, `stubs/data-request.stub`, `stubs/data-resource.stub`, `stubs/data-props.stub`.
- A reviewer can tell a Spatie Data class's purpose from its base class and file-name suffix (Decision 3).

---

## Decision 3 — Location and Naming: `app/Data/{Noun}/`, Suffixed Class Names

### Choice Made

All Spatie Data classes live under `app/Data/{Noun}/` per ADR-0009's noun-grouping convention. The TS namespace is `App.Data.{Noun}.{ClassName}` for every type.

Class name suffixes:

- `{Noun}Resource` — `UserResource`, `TeamResource` (one per Eloquent model)
- `{Noun}{Verb}Request` — `TeamStoreRequest`, `TeamUpdateRequest`, `TeamIndexRequest`
- `{Noun}{PageName}Props` — `TeamIndexProps`, `TeamShowProps`, `ProfileEditProps`
- `{Noun}{Purpose}Data` — `AuthAbilitiesData`, `UserSettingsData`

Pages without a dominant domain noun (e.g. `dashboard/Index.vue`) use the page concept as the noun: `app/Data/Dashboard/DashboardProps.php`. Shared data is `app/Data/Shared/SharedData.php` (a Resource — see Decision 8).

### Alternatives Considered

**Keep `app/Http/Resources/` and `app/Http/Requests/`, add `app/Data/`.**
*Rejected:* the `Http/` prefix is misleading once these are Spatie Data classes — they're typed value objects that participate in HTTP boundaries, not HTTP-layer artefacts.

**Folder-by-shape under `app/`** (e.g. `app/Resources/`, `app/Requests/`, `app/Props/`).
*Rejected:* breaks ADR-0009's per-noun grouping. Reviewers reading "what shapes does the User have on the frontend?" should open one folder, not four.

**`{Verb}{Noun}Request`** (`StoreTeamRequest`, Laravel's default artisan generator).
*Rejected:* `app/Data/Team/` would scatter alphabetically into `StoreTeamRequest`, `TeamIndexProps`, `TeamResource`, `UpdateTeamRequest`. Folder browseability wins with `{Noun}{Verb}` ordering.

### Reasoning

Every backend-shaped type is `App.Data.{Noun}.{Class}`. Frontend code has one mental model for where shapes come from. The folder-per-noun matches how reviewers think about features.

### Consequences

- `app/Http/Resources/` and `app/Http/Requests/` are deleted.
- The Pest arch test in `tests/Arch/` updates to scan `app/Data/{Noun}/*` for the noun-subgrouping rule.
- TS imports in components reference `App.Data.{Noun}.{Class}` consistently.

---

## Decision 4 — Resource Shape: DB Columns Required, Computed Fields Lazy, Relationships via `Lazy::whenLoaded`

### Choice Made

A `{Noun}Resource` class is created once per Eloquent model, with all fields and relationships defined:

- **Database columns** are declared as plain typed properties (`public int $id`, `public string $name`). Required in PHP and required in the generated TS.
- **Computed / appended fields** are declared as `Lazy|T` (`public Lazy|bool $is_owner`). Included at runtime when listed by `defaultIncludes()` (Decision 5).
- **Relationships** are declared as `Lazy|TResource` using `Lazy::whenLoaded(...)`. The controller decides whether to eager-load; the resource gates inclusion accordingly.
- **`withCount` / `loadCount` attributes** are declared as `Lazy|int` and named to match the Eloquent attribute (`members_count`, not `member_count`). The controller decides loading with `loadCount('members')`; the resource reads `$team->members_count` (or a wrapping method) and declares the field under the same name.

The TS type for a `Lazy` field is optional (`?: T`). Consumers handle optionality with optional chaining.

A second resource class is created only when divergence cannot be expressed with `Lazy` + dynamic `defaultIncludes()`. In practice this is rare; the default expectation is one class per model.

### Alternatives Considered

**Split into list/detail resource pairs.**
*Rejected:* divergence is rare. `Lazy` fields handle the spectrum of "always send" to "send only when controller asks."

**Declare computed fields as plain required properties.**
*Rejected:* the runtime decision "include this when the model is in state X" needs to read instance state, which a plain property declaration cannot express. Lazy + dynamic `defaultIncludes()` matches the runtime contract.

**Eager-load relationships inside the resource.**
*Rejected:* hides N+1 risk inside the resource. Controller owns the query; resource owns the serialisation.

### Reasoning

The class is the union of every field a model can produce; `Lazy` expresses "depends on what the controller loaded or the model state allows." The TS type marks lazy fields optional because they are optional at runtime — that's accurate, not a defect.

### Consequences

- `Model::preventLazyLoading(! app()->isProduction())` stays in `AppServiceProvider::boot()` to fail any missed eager-load in dev/test (Decision 11).
- Sub-resource references (`public Lazy|TeamResource $currentTeam`) generate correctly because the LaravelData transformer extension auto-discovers them.

---

## Decision 5 — `defaultIncludes()` is Dynamic, Based on Model State

### Choice Made

The Resource class's `defaultIncludes()` method returns a list of `Lazy` field names to auto-include, decided at construction time based on the wrapped model's state:

```php
public function defaultIncludes(): array
{
    $includes = [];

    if ($this->resource->relationLoaded('currentTeam')) {
        $includes[] = 'is_owner';
    }

    return $includes;
}
```

This mirrors Laravel's `$appends` model property, but instance-scoped rather than class-scoped: the include list depends on which relationships are loaded or which conditions hold at runtime.

### Alternatives Considered

**Static `defaultIncludes()` returning a fixed list.**
*Rejected:* "is_owner is always included" is false — it's included when `currentTeam` is loaded. Fixed lists either over-include (compute unnecessarily) or under-include (force every caller to opt in).

**Eager fields (non-Lazy) computed in a factory.**
*Rejected:* the field is genuinely conditional. Declaring it as required forces every caller to provide a value even when it's not relevant; the TS type would lie about runtime presence.

### Reasoning

The frontend's optional-field handling reflects the backend's runtime decision. The two stay in lock-step because they describe the same thing — "this field may or may not be present, depending."

### Consequences

- Each Resource class typically has a small `defaultIncludes()` method that reads model state.
- TS consumers using a Resource type handle optional fields with `user.is_owner ?? false` style checks.

---

## Decision 6 — Request Data: One Class per Route with Input Beyond Bindings; Fortify Wrapped

### Choice Made

A `{Noun}{Verb}Request` Data class exists for every route that takes user-supplied input beyond route bindings — i.e. body, query parameters, or uploaded files. Routes whose only input is route bindings or CSRF do not get a request class.

Controller signatures type-hint the request class:

```php
public function store(TeamStoreRequest $request): RedirectResponse
{
    $team = Team::create($request->only('name', 'slug'));
    // ...
}
```

Laravel's container resolves the Data class from the current request, runs `rules()` validation, and hydrates the typed properties.

Fortify's vendor controllers cannot accept project Data classes directly. Project-owned controller wrappers under `App\Http\Controllers\Auth\*` accept project Data classes, validate, then delegate to Fortify's underlying action classes. The exact wrap mechanism (`Fortify::ignoreRoutes()` vs `using()` callbacks) is an implementation detail confirmed at integration time; the constraint is that the generated TS namespace `App.Data.{Auth}.{Verb}Request` is project-defined, not vendor-emitted.

### Alternatives Considered

**Request class on every route, including empty ones.**
*Rejected:* `LogoutRequest` with no fields adds pure ceremony. Wayfinder's route helpers already type the call site; an empty class adds no TS information.

**FormRequest survives alongside Data classes.**
*Rejected:* two patterns for the same job — the drift this ADR replaces.

### Reasoning

The request class earns its place when it carries typed input. The frontend's `useForm` initial values type against `App.Data.{Noun}.{Verb}Request`. Backend validation and frontend type generation stay in one place.

### Consequences

- `app/Http/Requests/*` is deleted. All FormRequests become `App\Data\{Noun}\{Verb}Request` classes.
- `App\Concerns\ProfileValidationRules` (if still present) is deleted per ADR-0010 D4.
- Auth routes use project-owned controller wrappers around Fortify.

---

## Decision 7 — Page Props: One Class per Inertia Page; `Lazy::inertiaDeferred` for Deferred Fields

### Choice Made

Each Inertia page has a corresponding `{Noun}{PageName}Props` Resource class. The controller constructs an instance with the explicit constructor and passes it to `Inertia::render`:

```php
public function index(TeamIndexRequest $filters): Response
{
    return Inertia::render('teams/Index', new TeamIndexProps(
        teams: TeamResource::collect($this->paginate($filters)),
        filters: $filters,
    ));
}
```

Page components type their props via the generated namespace:

```ts
defineProps<App.Data.Team.TeamIndexProps>();
```

Heavy fields the page can render without initially are declared as `Lazy::inertiaDeferred(fn () => ...)`. Inertia ships the page shell first; the deferred field is fetched on a second request. The TS type marks deferred fields optional. Sub-resource laziness (e.g. `Lazy::whenLoaded` on a nested resource's relationships) transits through the props class without re-declaration.

### Alternatives Considered

**Static factory `TeamIndexProps::from(...)`.**
*Rejected:* the explicit constructor *is* the props contract. A static factory layer duplicates the constructor signature with no value added.

**`Inertia::render` with an array literal as before; props class is a separate wrapper.**
*Rejected:* the page-shaped type comes from the Spatie Data class; if the controller doesn't instantiate the class, there's no class to read.

**`Lazy::create` for deferred props.**
*Rejected:* `Lazy::create` is for controller-decided inclusion. The page has one controller method — the controller-decided dimension is already collapsed. Inertia's deferred-prop mechanism is what page-level laziness actually means.

### Reasoning

The page's props are a contract between one controller method and one Vue component. A class makes the contract auditable and named.

### Consequences

- Every existing Inertia page gains a corresponding Props class under `app/Data/{Noun}/`.
- Pages stop using `defineProps<Inertia.Pages.X>()` (no longer generated by Wayfinder per Decision 9) and use `defineProps<App.Data.{Noun}.{Class}>()` instead.

---

## Decision 8 — Shared Data: `App\Data\Shared\SharedData` Resource

### Choice Made

`HandleInertiaRequests::share()` returns an instance of `App\Data\Shared\SharedData`, a Resource class. The class declares all shared fields as typed properties — `auth.user` as `?UserResource`, `auth.abilities` as `AuthAbilitiesData`, `currentTeam` as `?TeamResource`, etc.

The frontend's `usePage().props` is typed by augmenting `@inertiajs/core`'s `PageProps` interface:

```ts
// resources/js/types/inertia.d.ts
declare module '@inertiajs/core' {
    interface PageProps extends App.Data.Shared.SharedData {}
}
```

This is the one project-owned `.d.ts` file required to replace Wayfinder's previous `inertia-config.d.ts`.

### Alternatives Considered

**Distinct fifth stub for shared data.**
*Rejected:* structurally identical to a Props class. Shared data is the props of "every page."

**Free-form array from `share()` with typed values inside.**
*Rejected:* loses the named outer type. Shared data is the most-consumed shape in the app; it deserves a named type more than any per-page shape.

### Reasoning

One mechanism for page-level shapes — Props classes — covers shared data as a degenerate case (the props that apply to every page). The augmentation file is one line of glue.

### Consequences

- `HandleInertiaRequests::share()` returns `new SharedData(...)`.
- `resources/js/types/inertia.d.ts` is the one project-owned `.d.ts` for Inertia type augmentation.
- Frontend code reads `usePage().props.auth.user` with full type safety; pages declare only their own props via `defineProps`.

---

## Decision 9 — Wayfinder Scope: Routes, Enums, Environment Variables Only

### Choice Made

`config/wayfinder.php` flags settle to:

| Flag | Value |
|---|---|
| `generate.route.actions` | `true` |
| `generate.route.form_variant` | `true` |
| `generate.models` | `false` |
| `generate.inertia.shared_data` | `false` |
| `generate.inertia.component` | `false` |
| `generate.enums` | `true` |
| `generate.environment_variables` | `true` |

Wayfinder retains the type generation it does well: route helpers, enum runtime + type modules (`export const Permission = { ... } as const`), and env vars. It stops generating shapes Spatie does better.

Spatie's TypeScript Transformer keeps its own enum generation enabled (auto-discovered from `app_path()`). The two outputs declare `App.Enums.*` namespaces with identical contents because they read from the same PHP source enums; TS namespace merging joins them cleanly. CI typecheck catches any divergence.

### Alternatives Considered

**Drop Wayfinder entirely; Spatie generates everything.**
*Rejected:* Spatie v3 has no runtime `const ... as const` enum mode. Frontend code comparing values (`if (role === RoleName.Owner)`) needs Wayfinder's runtime modules.

**Drop Spatie's enum generation; Wayfinder is the only source.**
*Rejected:* Spatie's transformer needs `App.Enums.*` to exist in its own namespace to resolve enum references inside generated Data/Resource types. Without Spatie's enum gen, fields like `public SubscriptionTier $tier` generate as `undefined`.

### Reasoning

The two generators handle complementary surfaces. Wayfinder's strength is route generation and runtime enum value emission; Spatie's strength is reading typed PHP classes. The duplication of `App.Enums.*` type-side is harmless because both read from the same source.

### Consequences

- `resources/js/wayfinder/Inertia/` and `resources/js/wayfinder/inertia-config.d.ts` are no longer generated.
- The project-owned `resources/js/types/inertia.d.ts` (Decision 8) replaces them for shared-data typing.
- Frontend code continues to import enum *values* from `@/wayfinder/App/Enums/*` (ADR-0010 D5 unchanged).

---

## Decision 10 — Pagination via `PaginatedDataCollection` / `DataCollection`

### Choice Made

Unbounded lists use `PaginatedDataCollection` (wrapping `LengthAwarePaginator`). Bounded fixed-size sets use `DataCollection` (flat). The generated TS shape uses `Illuminate.LengthAwarePaginator<...>` for paginated and `TResource[]` for flat.

Spatie Data collection parameterisation uses `#[DataCollectionOf(TeamResource::class)]` on the property:

```php
final class TeamIndexProps extends Resource
{
    public function __construct(
        #[DataCollectionOf(TeamResource::class)]
        public PaginatedDataCollection $teams,
    ) {}
}
```

The transformer extension reads the attribute to parameterise the generated TS type.

Cursor-based pagination is not adopted at this stage.

### Alternatives Considered

**Always paginate, even small fixed sets.**
*Rejected:* wraps a 5-element list in pagination metadata the frontend ignores.

**Always flat, paginate client-side.**
*Rejected:* ships full datasets for large lists. Performance regression.

**Cursor pagination from day one.**
*Deferred:* useful for infinite-scroll feeds; not needed yet.

### Reasoning

One decision point ("is this list bounded?") maps to two mechanisms. The TS shape carries the distinction explicitly so the page component handles `data.teams` vs `data.teams.data` based on type, not convention.

### Consequences

- Index controllers use `PaginatedDataCollection` with `DataCollectionOf`.
- Bounded sets use `DataCollection`.
- Cursor pagination remains revisitable without changing this ADR.

---

## Decision 11 — Same Resource Serves Inertia and Future JSON API; `preventLazyLoading` Stays; Tests via `assertInertia`

### Choice Made

A `{Noun}Resource` class is reused by the future JSON API layer. API controllers return Resource / Data instances directly:

```php
public function show(Team $team): TeamResource
{
    return TeamResource::from($team->load('owner'));
}
```

Laravel serialises via `Responsable`. No API-specific resource folder.

`Model::preventLazyLoading(! app()->isProduction())` stays in `AppServiceProvider::boot()`. Test environments fail on any missed eager-load.

Resource and Props classes have no standalone unit tests. Correctness is verified by feature tests asserting Inertia prop shape:

```php
$this->get(route('teams.index'))
    ->assertInertia(fn ($page) => $page
        ->has('teams.data', 3)
        ->where('teams.data.0.name', 'Alpha')
        ->missing('teams.data.0.owner.password')
    );
```

### Alternatives Considered

**Separate API resource folder for JSON-API-specific shapes.**
*Rejected:* the same controller-defined shape works for both. Splitting reintroduces duplication this ADR was written to remove.

**Standalone unit tests for each Resource.**
*Rejected:* tests serialisation in isolation from the controller's eager-load decision. The relevant property — "the controller sends the right shape" — needs a feature test.

### Reasoning

The serialisation contract is one class. The test is "what the frontend actually receives." The runtime guard makes missed eager-loads fail fast in dev/test.

### Consequences

- Feature tests assert security-sensitive missing fields and depended-on present fields.
- `preventLazyLoading` stays.
- Future API endpoints return Spatie Resource / Data instances directly.

---

## Decision 12 — ADR Resolution: Replaces Earlier ADR-0017 Text; Narrows ADR-0010 Decision 3

### Choice Made

This document replaces the earlier ADR-0017 text in place. The earlier text mandated `JsonResource` as the serialisation layer; the current text mandates Spatie Data. The ADR number, `Status: Accepted`, and acceptance posture stay. Git history preserves the previous text.

The file slug changes from `0017-eloquent-via-jsonresource.md` to `0017-eloquent-via-spatie-data.md` to match the new mechanism.

ADR-0010 Decision 3 retains its cross-reference *"For Eloquent model shapes, see ADR-0017"* — the pointer is unchanged; the destination's substance has shifted from `App.Http.Resources.*` to `App.Data.*`.

### Alternatives Considered

**Mark the previous ADR-0017 superseded; write a new ADR-0018.**
*Rejected:* vue-kit is pre-production. The project convention is to edit in place rather than carry forward "transitional" artefacts. An ADR-0018 plus a `Superseded by` header on ADR-0017 would force readers across two files; in-place rewrite gives readers one current document.

### Reasoning

The pre-prod convention favours one current document over an audit trail of superseded ones. Git history is the audit trail when one is needed.

### Consequences

- `docs/adr/0017-eloquent-via-jsonresource.md` is renamed to `docs/adr/0017-eloquent-via-spatie-data.md`.
- ADR-0010 Decision 3's cross-reference link is updated to the new slug; its prose is updated to reflect `App.Data.*` rather than `App.Http.Resources.*`.
