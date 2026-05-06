# ADR 0012: `app/Services/` for Request-Scoped Resolver Classes

- **Status:** Accepted
- **Date:** 2026-05-06

## Context

Per-request resolved state — currently just "the active **Team** for this
request" — has been stored as a string-keyed container binding
(`app('currentTeam')`) populated by `SetCurrentTeam` middleware. Callers
fetch it the same way:

```php
$team = app('currentTeam');                              // controllers
$team = app()->bound('currentTeam') ? app('currentTeam') // Inertia share
    : null;
```

Three problems:

1. **Untyped.** The binding returns `mixed`. IDEs can't trace usages and
   type checkers can't catch wrong assumptions.
2. **Service-locator smell.** Every consumer reaches into the container
   with a string key rather than declaring a typed dependency.
3. **No conceptual home.** "The active team for this request" is a
   first-class concept (it's the subject of ADR 0003) but lives nowhere
   in the type system — it's just a magic string.

A typed, injectable holder is the obvious fix. The question this ADR
resolves is *where it lives in the source tree* given ADR 0009's strong
stance against junk-drawer folders, and the fact that the codebase has
no existing home for a class of this shape.

## Decision

Introduce `app/Services/` as a new type folder for **request-scoped or
session-scoped resolver classes that hold or expose ambient state** and
don't fit the existing type folders. The first occupant is
`app/Services/Team/TeamContext.php`, replacing the
`app('currentTeam')` binding.

Per ADR 0009:

- The folder subgroups by domain noun (Decision 5 default for new type
  folders).
- Filenames retain the noun prefix
  (`Services/Team/TeamContext.php`, not `Services/Team/Context.php`),
  per Decision 7.
- The arch test from Decision 6 extends to forbid classes at the root
  of `app/Services/`.

### Scope — what goes in `Services/`

A class belongs in `app/Services/` if it:

- Holds or exposes **ambient state for the current request or session**
  (the resolved current team, a per-request feature-flag snapshot,
  etc.), AND
- Does not fit `Actions/` (single-purpose verbs / commands), `Data/`
  (DTOs), `Concerns/` (traits), `Policies/` (authorization),
  `Observers/` (model lifecycle), or `Listeners/` (event handlers).

The first occupant — `TeamContext` — is a request-scoped holder
populated by middleware and consumed by controllers and the Inertia
share middleware via constructor injection.

### Scope — what does *not* go in `Services/`

- **Verbs.** `CreateTeam`, `InviteMember`, `ResetCurrentTeam` — these
  remain in `Actions/`.
- **Stateless utility classes** such as formatters, calculators, or
  generic helpers. These are rare in this codebase and, if needed,
  belong in a static method on the relevant Data class or as a Concern.
- **Anything that can be expressed as a method on an existing model or
  DTO.** `User::currentTeam()` (the Eloquent relationship) is not a
  service; `TeamContext::current()` (the resolved active team for *this
  request*) is.

## Considered options

- **`app/Services/Team/TeamContext.php`** (chosen). New type folder
  named after the de-facto Laravel-community convention. Discoverable —
  a new contributor searching for "where's the thing that gives me the
  current team" finds it on the first try. Constructor-injectable.
- **`Request` macro: `$request->currentTeam()`.** Very Laravel-flavoured
  (mirrors `$request->user()`). Populated by middleware via
  `$request->attributes->set()`, registered in a ServiceProvider.
  Rejected because it re-introduces the service-locator concern: every
  consumer still reaches into a global object with a method name rather
  than declaring a typed dependency. Macros also resist static analysis
  without IDE-helper plugins.
- **Cram into `app/Actions/Team/`.** Rejected: `Actions/` is verbs (the
  existing inhabitants are `ResetCurrentTeam`, `SetCurrentTeamSlug`,
  etc.). A state-holding context is not a verb.
- **Generic `app/Support/`.** Rejected: ADR 0009 Decision 4 already
  forbids generic catch-all folders (`Shared/`, `Util/`, `Common/`,
  `Helpers/`). `Support/` is the same pattern under a different name
  and would attract the same drift.
- **Status quo: keep `app('currentTeam')`.** Rejected per the three
  problems in Context.

## Consequences

- A new type folder appears under `app/`. The arch test exemption list
  in `tests/Arch/` extends to cover it (no class at the root of
  `app/Services/`, same rule as every other subgrouped type folder).
- The string binding `app('currentTeam')` and `app()->instance(
  'currentTeam', $team)` calls are removed. `SetCurrentTeam` middleware
  is rewritten to inject `TeamContext` and call `setTeam()`.
- Consumers (`BillingController`, `CheckoutController`,
  `PortalController`, `HandleInertiaRequests`) inject `TeamContext` and
  call `current()` or `currentOrFail()`.
- The "scope" rules above are enforced by code review, not arch test —
  same trade-off as ADR 0009 Decision 6 (structural rules automated,
  semantic rules left to review). The risk that `Services/` drifts into
  a junk drawer is real and is the cost of introducing the folder.
- Future request-scoped resolvers (e.g. a hypothetical
  `LocaleContext`, `FeatureSnapshot`) have a clear home without
  needing another ADR.
- Reverting is non-trivial but bounded: move `TeamContext` to a chosen
  alternative location and update consumers. The folder itself is
  cheap to delete if it never gains a second occupant.
