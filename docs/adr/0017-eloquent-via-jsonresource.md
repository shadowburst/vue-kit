# ADR 0017: Eloquent Models Cross the Inertia/JS Boundary as JsonResource

- **Status:** Accepted
- **Date:** May 2026

## Context

ADR-0010 Decision 1 mandates that all types crossing the Laravel/JS boundary come from Wayfinder-generated types. Decision 3 of that ADR currently says sub-components consuming backend-shaped props reference `App.Models.*` directly from the generated namespace.

Two problems emerge as the codebase grows:

1. **Uncontrolled model shape.** Eloquent models serialise their full attribute set by default. Controllers that pass a model instance to `Inertia::render(...)` risk leaking sensitive columns (`password`, `remember_token`, billing fields) to the frontend or varying the shape across endpoints without a contract.
2. **Wayfinder's `App.Models.*` namespace conflicts with domain intent.** Wayfinder generates `App.Models.User` from the Eloquent model class — but `App.Models.*` types include columns the frontend should never see, and the namespace implies the frontend can reason about every column. A resource-shaped type documents the contract explicitly.

The companion concern — that `App.Data.*` (Spatie Data) already works well for non-Eloquent typed values like `AuthAbilitiesData` and `UserSettingsData` — argues for a two-axis rule rather than replacing Data entirely with resources.

This ADR narrows ADR-0010 Decision 3. It does not supersede ADR-0010.

---

## Decision 1 — Resource Type: Flat `JsonResource`, Not `JsonApiResource`

### Choice Made

All Eloquent models passed to `Inertia::render(...)` (or returned from a controller for the future API) are wrapped in a flat `JsonResource`. The same resource class is reused by the future JSON API layer. No JSON:API specification envelope is introduced.

### Alternatives Considered

**`JsonApiResource` (JSON:API specification envelope).**
*Rejected:* JSON:API's `{ data: { type, id, attributes, relationships } }` envelope is designed for hypermedia clients negotiating resource relationships via `links`. Inertia pages and the project's own API clients receive a known controller-defined shape — they do not negotiate. The envelope adds nesting that Wayfinder must traverse to reach the actual field types, and it couples the frontend to a spec the project has no dependency on.

**Hand-rolled `toArray()` on the model itself (`$casts` + `$hidden`).**
*Rejected:* buries the serialisation contract inside the model. A model modified for one endpoint silently changes every other endpoint. Resources make the per-endpoint contract explicit and auditable.

### Reasoning

`JsonResource` is the standard Laravel mechanism for exactly this: an explicit, versioned serialisation layer between the model and the consumer. A flat shape is the simplest that works, matches what Wayfinder can introspect for TypeScript generation, and leaves open the same JSON:API migration path that `JsonResource` always does.

### Consequences

- Every controller that currently passes a raw Eloquent model to `Inertia::render(...)` or returns one from an API method must be updated to wrap it in the corresponding resource.
- The future API layer reuses the same resource classes rather than introducing API-specific serialisation.
- No JSON:API client or `vnd.api+json` content-type negotiation is added.

---

## Decision 2 — Scope Split: Eloquent → `JsonResource`; Non-Eloquent Typed Shapes → `App\Data\*`

### Choice Made

Two distinct namespaces serve two distinct categories of backend-shaped data:

- **Eloquent model shapes** → `App\Http\Resources\{Noun}\{Noun}Resource` (new convention, see Decision 8).
- **Non-Eloquent typed shapes** → `App\Data\*` (Spatie Data, existing convention).

The existing `App\Data\Auth\AuthAbilitiesData` and `App\Data\User\UserSettingsData` stay in `App\Data\*`. They are not Eloquent models and the resource rule does not apply to them.

### Alternatives Considered

**Replace all `App\Data\*` shapes with `JsonResource`.**
*Rejected:* `AuthAbilitiesData` and `UserSettingsData` have no backing Eloquent model. A resource wrapping an ad-hoc plain object is an anti-pattern — resources exist to serialise models. `App\Data\*` objects are also typed PHP value objects, not row-shaped hydration targets, so the Spatie Data validation/casting pipeline applies and resource bypasses it.

**Replace all `JsonResource` with `App\Data\*` (Spatie Data everywhere).**
*Rejected at current scale:* Spatie Data's full pipeline (validation, casting, lazy loading, `whenLoaded`) is not needed for every serialisation. The two-axis rule preserves the existing `App\Data\*` investment and introduces resources only where Eloquent models are involved.

**No rule — ad-hoc per controller.**
*Rejected:* the status quo produces inconsistent frontend shapes, silent data leaks, and Wayfinder types that vary by endpoint.

### Reasoning

The rule mirrors the shape of the data being serialised. Eloquent models have rows, relationships, and lazy-load behaviour — resources address all three. Non-Eloquent typed values are pure DTOs — Spatie Data addresses those. One rule, two implementations, no overlap.

### Consequences

- Sub-components that currently type their props as `App.Models.User` or `App.Models.Team` will migrate to `App.Http.Resources.*`-derived types once Wayfinder generates them (pending the Wayfinder `generate.models` flag flip in issue #67).
- `AuthAbilitiesData` and `UserSettingsData` are explicitly preserved in `App\Data\*`. The split is documented so reviewers do not migrate them into resources.

---

## Decision 3 — Loading Mechanic: Controller Eager-Loads; Resource Gates with `whenLoaded()`

### Choice Made

Controllers are responsible for eager-loading all relationships a resource will serialise. Resources declare optional relationship fields using `$this->whenLoaded('relation')` — a field is present in the JSON output only when the controller explicitly loaded the relationship. TypeScript fields derived from optional relationships are typed as optional (`?`).

A second resource class is created only when the serialisation contract genuinely diverges across contexts (e.g. a list resource omitting fields the detail resource includes). If a single resource can serve both contexts by using `whenLoaded()` for heavier relationships, no split occurs.

### Alternatives Considered

**Resource always lazy-loads relationships it needs.**
*Rejected:* hides the N+1 query risk inside the resource class. Controllers that pass a model collection through a resource would trigger a query per model for each relationship. `Model::preventLazyLoading()` (Decision 5) makes this a hard error in dev/test.

**Always split into list/detail resource pairs.**
*Rejected:* premature. Many resources have a single usage context. A mandatory split doubles the class count before divergence is real, adding maintenance overhead and a naming convention tax (`UserListResource` vs `UserDetailResource`).

**Eager-load everything in the resource via the `with` property.**
*Rejected:* transfers the load decision from the controller to the resource. The controller owns the query; the resource owns the serialisation. Mixing them makes it impossible to reuse the same resource across endpoints with different depth requirements.

### Reasoning

The controller is the only point in the request lifecycle that knows what the current page or API response needs. Centralising the load decision there, and making the resource defensive via `whenLoaded()`, keeps N+1 risks visible at the call site and optional TS fields reflect reality — a field is genuinely optional because it may or may not be loaded.

### Consequences

- Every resource method referencing a relationship uses `$this->whenLoaded('relation')`, not `$this->resource->relation`.
- TypeScript types generated by Wayfinder mark those fields as optional, matching the runtime behaviour.
- Resources are not split until divergence is observed, keeping the class count low.

---

## Decision 4 — Wayfinder Hygiene: `App.Models.*` Killed via `generate.models` Flag

### Choice Made

The Wayfinder configuration flag `generate.models` is set to `false`, preventing Wayfinder from emitting the `App.Models.*` TypeScript namespace. Frontend code no longer references `App.Models.*` for backend-shaped data; it references `App.Http.Resources.*` instead.

This decision is scoped to the Wayfinder config change only. The migration of existing component prop types is a separate implementation task (issue #67).

### Alternatives Considered

**Keep `App.Models.*` generated alongside `App.Http.Resources.*`.**
*Rejected:* two competing type sources for the same data. `App.Models.User` and a `UserResource`-derived type describe overlapping shapes; Wayfinder consumers must choose, and over time both drifts and explicit casts accumulate.

**Deprecate `App.Models.*` via ESLint `no-restricted-imports` without removing generation.**
*Rejected:* generating types that are immediately forbidden is waste. The flag is the correct removal point.

### Reasoning

Removing the generation at source is cleaner than layering a lint rule on top of a generated artefact. Once resources are the canonical contract, model-level types are redundant.

### Consequences

- After the flag is flipped (issue #67), any component still referencing `App.Models.*` will produce a TypeScript error, driving the migration automatically.
- The `wayfinder:generate` command output shrinks by the model namespace.

---

## Decision 5 — Lazy-Load Guard: `Model::preventLazyLoading()` in Non-Production

### Choice Made

`Model::preventLazyLoading(! app()->isProduction())` is called in `AppServiceProvider::boot()`. In development and test environments, any lazy-loaded relationship throws an exception immediately, making "controller decides what loads" mechanically enforceable rather than aspirational.

### Alternatives Considered

**Trust code review to catch lazy loading.**
*Rejected:* lazy loading is invisible at the call site. A reviewer reading `$resource->user->name` cannot tell from the controller whether `user` was eager-loaded or not without running the code. The guard makes the omission immediate and local.

**Apply the guard in production too.**
*Rejected:* production guard is a hard failure for any deployed code with a missed eager-load. Non-production is sufficient — the guard catches problems before they ship; production should not blow up for users because a new relationship was added without updating the controller.

**Use Telescope or Debugbar to detect N+1 queries post-hoc.**
*Rejected:* post-hoc detection requires the developer to remember to check; the guard is pre-hoc and automatic.

### Reasoning

The guard aligns the runtime behaviour of dev/test with the contractual intention of Decision 3. It converts "you should eager-load" from a guideline into an enforced invariant in the environments where enforcement has no production consequence.

### Consequences

- Any controller that passes a model with an un-loaded relationship to a resource will throw `LazyLoadingViolationException` in tests, causing test failures that surface the missed `->with('relation')`.
- The guard is added to `AppServiceProvider::boot()` behind the `! app()->isProduction()` condition.

---

## Decision 6 — Shared Data: `HandleInertiaRequests::share()` Wraps `auth.user` and `currentTeam` via Resources

### Choice Made

`HandleInertiaRequests::share()` wraps the `auth.user` and `currentTeam` values with their respective `JsonResource` classes before sharing them with Inertia. This produces a single canonical shape for these objects across every frontend page and component, regardless of which controller triggered the request.

### Alternatives Considered

**Share the raw Eloquent model directly (current state).**
*Rejected:* the serialised shape is determined by the model's `$hidden` and `$casts` — both of which are general-purpose attributes, not a documented frontend contract. The shape drifts silently when model attributes change.

**Share a Spatie Data DTO (`UserData`, `TeamData`) wrapping the model.**
*Rejected:* the Data pipeline is well-suited to non-Eloquent typed shapes (per Decision 2) but adds the full Spatie Data validation/casting overhead for what is simply a serialisation of a model already in memory. A `JsonResource` is the lighter, correct mechanism here.

**Share different shapes from different controllers (no canonical shape).**
*Rejected:* sub-components rendered across pages rely on `auth.user` having a stable shape. Multiple shapes force conditional prop handling in components that should not know which controller rendered their page.

### Reasoning

Shared data is the highest-impact application of the resource rule: every page and every component that reads `auth.user` or `currentTeam` receives the same type. Centralising serialisation in `HandleInertiaRequests` rather than in each controller is the correct scope for globally shared data.

### Consequences

- `HandleInertiaRequests::share()` is updated to return `new UserResource($user)` and `new TeamResource($team)` rather than raw models or ad-hoc arrays.
- Wayfinder generates `Inertia.SharedData.auth.user` from the resource's `toArray()` return, not from the model's full attribute set. TypeScript consumers see the resource-shaped type.
- This change is implemented in issues #65 and #66 and is explicitly out of scope of this ADR slice.

---

## Decision 7 — Pagination Convention: `LengthAwarePaginator` for Unbounded Lists; `Collection` for Bounded Sets

### Choice Made

Controllers returning list data use one of two shapes:

- **Unbounded lists** (size determined by user interaction or record count) → `LengthAwarePaginator`, which resources wrap automatically when passed to `ResourceCollection::make()`. The frontend receives `{ data: [...], links: {...}, meta: {...} }`.
- **Bounded fixed-size sets** (e.g. a team's last 5 invoices, a member cap display) → plain `Collection`, which resources wrap as a flat array.

Cursor-based pagination is not adopted at this stage.

### Alternatives Considered

**Always paginate, even small fixed lists.**
*Rejected:* wraps a 5-element bounded set in pagination metadata that the frontend ignores. Adds parsing overhead for no navigational benefit.

**Always return flat arrays, paginate client-side if needed.**
*Rejected:* client-side pagination requires shipping the full dataset to the browser. For large member lists or invoice histories, this is a performance regression.

**Cursor-based pagination.**
*Deferred:* cursor pagination is superior for feed-style infinite-scroll lists (no page-count metadata, stable offsets). It requires `after`/`before` cursor parameters rather than `page` numbers and is a larger API design commitment. Not needed by current list pages. Revisit when an infinite-scroll list is introduced.

### Reasoning

The two-branch rule gives controllers a single decision point ("is this list bounded in size?") that maps directly to the two Laravel pagination mechanisms. The distinction is preserved in the type shape Wayfinder generates: a paginator produces `Inertia.Pages.*.users.data` (array) with sibling `links`/`meta` keys; a collection produces `Inertia.Pages.*.users` directly as an array.

### Consequences

- Controllers serving index pages (member list, invoice list) use `UserResource::collection($team->members()->paginate())`.
- Controllers serving bounded data (tier comparison, over-cap display) use `UserResource::collection($team->members)`.
- The TypeScript shape differs between the two patterns; page components that mix them must handle the distinction explicitly.

---

## Decision 8 — Layout: `app/Http/Resources/{Noun}/{Noun}Resource.php`

### Choice Made

Resource classes live at `app/Http/Resources/{Noun}/{Noun}Resource.php`, following the per-noun subgrouping convention established by ADR-0009. The `Http/Resources/` type folder is new and subgrouped from day one. A Pest arch test scans the folder to enforce the structural rule once the folder exists.

### Alternatives Considered

**Flat `app/Http/Resources/{Noun}Resource.php` (no noun subfolder).**
*Rejected:* ADR-0009 Decision 3 allows a flat layout only for type folders where subgrouping would repeat the noun (models, policies, observers). `Resources/UserResource.php` does not repeat the noun unnecessarily and the folder will grow as models are added, making the flat layout harder to navigate.

**`app/Resources/` at the app root (outside `Http/`).**
*Rejected:* resources are HTTP-layer artefacts — they format output for HTTP responses. Placing them outside `Http/` implies they are domain layer classes, which is incorrect.

**A separate `app/Http/Resources/Api/` vs `app/Http/Resources/Inertia/` split.**
*Rejected:* Decision 1 establishes that the same resource class is reused by both Inertia and the future API. An artificial namespace split re-introduces the duplication the single-class rule eliminates.

### Reasoning

`app/Http/Resources/` is the Laravel convention. ADR-0009's per-noun subgrouping applies: `app/Http/Resources/User/UserResource.php`, `app/Http/Resources/Team/TeamResource.php`. The arch test enforcement (once the folder lands) is consistent with how ADR-0009 handles every other subgrouped type folder.

### Consequences

- The first resource class created establishes the folder. The Pest arch test in `tests/Arch/` is extended to assert no class lives at the root of `app/Http/Resources/`.
- The layout is consistent with every other subgrouped type folder in the codebase.

---

## Decision 9 — Content Policy: Carbon and Backed Enums Direct; Computed Fields on Resource; Policy Results in `AuthAbilitiesData`

### Choice Made

Resources return Carbon instances and backed enums directly without manual transformation:

- **Carbon / `DateTimeInterface`:** Wayfinder's `TypeScriptConverter` maps `DateTimeInterface` to `string` in generated types. Laravel serialises Carbon to an ISO 8601 string in JSON automatically.
- **Backed enums:** PHP 8.1+ JSON-serialises backed enums to their raw value (`string` or `int`) automatically. Wayfinder maps the enum type to the corresponding TypeScript literal union.

No `$model->append(...)` calls are used. Computed or derived fields (fields that are not raw model columns) belong on the resource's `toArray()` method, not on the model as `$appends`. Permission and policy results (e.g. `can.update`, `can.delete`) stay in `AuthAbilitiesData` — they are not inlined into resources.

### Alternatives Considered

**Manually cast Carbon to `->toISOString()` in `toArray()`.**
*Rejected:* redundant — Laravel's JSON serialisation pipeline already calls `jsonSerialize()` on Carbon, which produces the ISO 8601 string. Manual casting adds noise and a maintenance point if Carbon's serialisation format is ever configured differently.

**Manually cast backed enums to `->value` in `toArray()`.**
*Rejected:* same reasoning. PHP 8.1 guarantees `json_encode(BackedEnum::Case)` produces the scalar value. No cast is needed.

**Use `$model->append('computed_field')** and keep derived fields on the model.**
*Rejected:* `$appends` attach to every serialisation of the model, including contexts where the computed field is not needed. This inflates every model response and pushes business logic into the model layer.

**Inline `can.update` / `can.delete` into the resource.**
*Rejected:* policy results are already consolidated in `AuthAbilitiesData` (per ADR-0008's `auth.abilities` shape). Inlining them into individual resources creates a second, inconsistent source for the same policy results and fragments the frontend's ability check surface.

### Reasoning

Leveraging PHP and Wayfinder's built-in serialisation keeps resources thin — they express the serialisation contract, not the serialisation mechanics. Keeping policy results outside resources preserves the clean separation between "what does this model look like?" and "what is this user allowed to do with it?" established by ADR-0008.

### Consequences

- Resources do not manually call `->toISOString()` on date fields or `->value` on backed enum fields.
- Wayfinder generates `string` for Carbon-typed resource fields and the appropriate literal union for backed-enum fields, matching the runtime JSON output.
- All `can.*` boolean fields remain in `auth.abilities.*` (via `AuthAbilitiesData`), not inside resource responses.
- Computed or derived fields added to the frontend shape are placed in the resource's `toArray()` method.

---

## Decision 10 — Testing: Inertia Prop Shape Assertions; No Standalone Resource Unit Tests

### Choice Made

The correctness of a resource's serialised shape is verified by feature tests that assert Inertia prop shape:

```php
$this->get(route('team.members.index'))
    ->assertInertia(fn ($page) => $page
        ->has('members.data', 3)
        ->where('members.data.0.name', 'Alice')
        ->missing('members.data.0.password')
    );
```

No standalone unit tests are written for `JsonResource` classes in isolation.

### Alternatives Considered

**Standalone unit tests for each resource class.**
*Rejected:* a unit test of `(new UserResource($user))->toArray(request())` tests the serialisation in isolation from the controller that chooses what to eager-load and what to pass. The relevant correctness property — "the controller sends the right shape to the page" — requires the full Inertia response to verify. Unit tests of resources alone would pass even if the controller forgot to load relationships or passed the wrong model.

**Snapshot tests of the full JSON output.**
*Rejected:* snapshot tests break on any additive change (new field added to a resource) and require manual snapshot updates. `assertInertia` assertions test specific fields and relationships, tolerating additive changes that don't affect the properties under test.

### Reasoning

`assertInertia` tests the thing that matters: what the frontend actually receives. Resources are implementation details of how the controller builds that shape. Testing the output rather than the implementation keeps tests resilient to refactors (e.g. inlining a resource into a controller or extracting a sub-resource) that do not change the frontend contract.

### Consequences

- Resource classes have no standalone test files. Coverage comes from the feature tests for the controllers that use them.
- Feature tests covering Inertia pages assert at least the fields that are security-sensitive (e.g. `->missing('members.data.0.password')`) and the fields the frontend depends on.

---

## Decision 11 — ADR Resolution: Narrows ADR-0010 Decision 3; Does Not Supersede ADR-0010

### Choice Made

This ADR narrows ADR-0010 Decision 3. The original text of Decision 3 states that sub-components reference `App.Models.*` for backend-shaped data. This ADR replaces that guidance for Eloquent model shapes: sub-components now reference `App.Http.Resources.*`-derived types (via Wayfinder) for Eloquent shapes, and `App.Data.*` for non-Eloquent typed shapes. ADR-0010 Decision 3 receives a one-line cross-reference pointing here.

ADR-0010 is not marked superseded. Decisions 1, 2, 4, 5, and 6 of ADR-0010 are unaffected.

### Alternatives Considered

**Mark ADR-0010 superseded, rewrite Decision 3 in full here.**
*Rejected:* ADR-0010 contains five other decisions that remain valid. Superseding it would remove useful context and force readers to diff two ADRs to understand what actually changed.

**Extend ADR-0010 Decision 3 in place without a new ADR.**
*Rejected:* the scope of this decision (a new type folder, a new loading mechanic, a new arch test, a new testing convention) warrants its own document with full alternatives and consequences. An inline edit to ADR-0010 would compress detail that future readers need.

### Reasoning

ADR-0010 and ADR-0017 form a coherent pair: ADR-0010 establishes that all backend-shaped types come from Wayfinder; ADR-0017 narrows *how* Eloquent models are shaped before Wayfinder reads them. A cross-reference from Decision 3 of ADR-0010 is the minimal link that guides readers from the general rule to the specific mechanic.

### Consequences

- ADR-0010 Decision 3 gains a cross-reference: *"For Eloquent model shapes, see ADR-0017."*
- Sub-components that currently use `App.Models.*` for Eloquent-shaped props migrate to the `App.Http.Resources.*` namespace once issue #67 flips the Wayfinder flag and the resource classes exist.
- `App.Data.*` (Spatie Data) remains the correct namespace for non-Eloquent typed shapes; no component migration is required for those.
