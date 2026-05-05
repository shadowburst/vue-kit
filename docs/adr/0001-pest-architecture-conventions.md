# ADR 0001: Pest Architecture Conventions

- **Status:** Accepted
- **Date:** May 2026
- **Issues:** #7, #9, #10, #12, #13

## Context

This kit ships a Pest arch test suite that enforces structural conventions on
the PHP codebase. Architecture tests catch class-shape violations at CI time
rather than in code review. Four categories of convention needed explicit
rationale because each involves a non-obvious trade-off:

1. Which namespace-to-namespace dependency directions to forbid.
2. What shape every Action class must take.
3. What shape every Data class must take.
4. Which layers require `final` and which are deliberately exempted.

---

## Decision 1 — Layering Rule Scope

### Choice Made

Four `not->toUse()` rules protect specific seam inversions:

- `App\Models` must not use `App\Http`
- `App\Models` must not use `App\Actions`
- `App\Providers` must not use `App\Http\Controllers`
- `App\Actions` must not use `App\Http\Controllers`

Controllers are **not** forbidden from using Eloquent directly.

### Alternatives Considered

**Strict MVC layering** — forbid controllers from touching models and require a
service/repository layer. Eliminates all Eloquent usage from controllers.
*Rejected:* over-engineering for a kit of this size. Laravel's built-in
conventions (route model binding, `authorize()`, inline queries for simple reads)
are idiomatic and widely understood.

**No layering rules at all** — rely on code review alone.
*Rejected:* the four chosen seams have inverted in practice: a model importing
an HTTP request class or an action calling back into a controller creates a
dependency cycle that is hard to detect in review.

**Full hexagonal / ports-and-adapters** — enforce a strict domain/application/
infrastructure split with no cross-layer imports.
*Rejected:* requires restructuring the entire namespace tree and adds friction
disproportionate to the app's current scale.

### Reasoning

The four rules protect exactly the seams that genuinely should not invert:
models are persistence-level objects that should be HTTP-agnostic; providers
bootstrap the application and should not pull in routing artefacts; actions are
called *by* controllers and must not call back into them. Every other direction
(controllers → models, controllers → actions, etc.) is normal Laravel flow and
is intentionally left unconstrained.

### Consequences

- Controllers may query models directly. Teams that want a stricter boundary can
  add more rules without touching these four.
- The rules are vacuously safe today (no violations in the baseline codebase)
  and will fire the moment a cycle is accidentally introduced.
- Fortify action classes are in `App\Actions\Fortify` and are subject to the
  layering rules (they must not use controllers) but are excluded from the
  Action shape rules (see Decision 2).

---

## Decision 2 — Action Class Shape

### Choice Made

Every non-Fortify class under `App\Actions` must satisfy all five constraints:

1. Class name ends with `Action`
2. Class is `final`
3. Class uses the `Spatie\QueueableAction\QueueableAction` trait
4. Exactly one own public method: `execute`
5. No own protected methods

Fortify action classes are excluded from constraints 1, 3, 4, and 5 because
they implement framework contracts with different method names (`create`,
`reset`). They are still required to be `final` (constraint 2).

### Alternatives Considered

**No shape convention** — each developer decides how to structure actions.
*Rejected:* leads to inconsistency: some actions have `handle`, some `__invoke`,
some `run`; callers need to inspect each class individually.

**`__invoke` instead of `execute`** — makes actions callable as closures.
*Rejected:* `execute` is the method name `QueueableAction` dispatches via the
queue; using `__invoke` would require extra wiring. The `execute` name also
makes the entry point unambiguous in IDE navigation.

**No `final`** — allow subclassing for test doubles or specialisations.
*Rejected:* subclassing an action creates "is this helper actually called from
outside?" ambiguity. Test doubles are better achieved through the action's
constructor dependencies; specialisation should be a new action.

**No `QueueableAction` trait** — add the trait only when a specific action needs
to run on a queue.
*Rejected:* inconsistency: callers cannot assume every action is queueable. The
trait has no runtime cost when synchronous dispatch is used, so mandating it on
all actions costs nothing and makes every action queueable for free.

**`protected` helpers allowed** — permit protected methods for code organisation.
*Rejected:* protected methods on a `final` class are accessible to no subclass
yet harder to inline-refactor than private methods. Any helper that needs
visibility beyond private should be extracted to its own class.

### Reasoning for the Reflection-Based Test

The suffix, `final`, and trait constraints are expressible via `arch()`. The
"exactly one public `execute`" and "no protected methods" constraints require
inspecting the set of *own-declared* methods (inherited methods must not be
counted), which requires `ReflectionClass`. The `toSatisfy()` matcher is not
available in the installed version of `pest-plugin-arch`, so a plain `test()`
loop with `ReflectionMethod::$class === $ref->getName()` achieves the same
granular failure messages.

### Consequences

- Every action can be dispatched to the queue with `(new SomeAction)->onQueue(...)->execute(...)` without any per-action opt-in.
- The `execute` convention makes grepping for action entry points trivial.
- Fortify actions (`CreateNewUser`, `ResetUserPassword`) are `final` but retain
  their framework-mandated method names and are not required to use
  `QueueableAction`.

---

## Decision 3 — Data Class Shape

### Choice Made

Every class under `App\Data` must satisfy all three constraints:

1. Extends `Spatie\LaravelData\Data`
2. Class name ends with `Data`
3. Non-abstract classes are `final`

### Alternatives Considered

**No dedicated namespace** — put DTOs alongside the models they relate to, or
in a generic `App\DTO` namespace.
*Rejected:* `App\Data` is the namespace the Spatie laravel-data package
documentation suggests, making it instantly recognisable to any developer
familiar with the package.

**No suffix requirement** — rely on the namespace alone to signal that a class
is a data object.
*Rejected:* the suffix makes the role visible at every call site (`new
UserSettingsData(...)`) without requiring the reader to remember the namespace
convention.

**`final` on abstract classes too** — enforce `final` everywhere with no
exceptions.
*Rejected:* PHP does not allow a class to be both `abstract` and `final`.
Abstract base Data classes are a legitimate pattern (shared cast logic), so the
rule targets only concrete classes.

**Allow subclassing of concrete Data classes** — treat Data classes like models
and allow extension.
*Rejected:* Data classes are value objects. Subclassing a concrete value object
can produce subtypes that violate Liskov's substitution principle silently (e.g.
a subclass that ignores a validation rule). `final` prevents accidental
subclassing.

### Reasoning

The three constraints together make Data classes scannable, unsurprising, and
safe to use as casts. The arch() matcher has no "non-abstract" filter, so the
`final` constraint is enforced with the same `ReflectionClass` loop pattern
used for Action shape checking.

### Consequences

- The `#[TypeScript]` attribute on Data classes causes `tsc-transform` to emit
  TypeScript interfaces; `final` does not affect this.
- Abstract base Data classes (if ever added) are only exempt from the `final`
  rule, not from the `Data` suffix or `toExtend` rules.

---

## Decision 4 — Final-Modifier Scope

### Choice Made

`final` is enforced on:

- All controllers (except the abstract base `Controller`)
- All actions (Fortify and non-Fortify alike)
- All non-abstract Data classes

`final` is **not** enforced on:

- Models
- FormRequests
- Middleware
- Providers

### Alternatives Considered

**`final` on Models** — prevent subclassing of Eloquent models.
*Rejected:* Eloquent's own documentation uses inheritance for polymorphism (e.g.
`HasFactory` extends). Community packages (Nova, Telescope) routinely extend
application models. Making models `final` would break a large ecosystem of
legitimate patterns and prevent the `User extends Authenticatable` chain that
Eloquent itself requires.

**`final` on FormRequests** — prevent subclassing of request classes.
*Rejected:* mixed community signal. Some teams use a base request class with
shared authorisation logic; others do not. The community convention is not
settled enough to mandate `final` in a kit that should serve diverse teams.

**`final` on Middleware** — prevent subclassing of middleware.
*Rejected:* same rationale as FormRequests. Laravel's own middleware classes
(e.g. `Authenticate`) are commonly extended by application middleware. Mandating
`final` would prevent a legitimate customisation pattern.

**`final` on Providers** — prevent subclassing of service providers.
*Rejected:* Laravel's framework providers (`AuthServiceProvider`,
`EventServiceProvider`) are designed to be extended. Deferred providers and
package providers also rely on inheritance. Making Providers `final` is
incompatible with framework expectations.

**No `final` anywhere** — rely on code review to prevent accidental subclassing.
*Rejected:* without enforcement, "accidentally final" classes (controllers,
actions, data) drift toward becoming base classes over time, creating hidden
coupling.

### Reasoning

The `final` modifier is applied only where the community signal is clear and
the friction is low. Controllers do not need subclassing: route model binding,
dependency injection, and dedicated service classes cover every use case.
Actions and Data classes are inherently leaf classes (see Decisions 2 and 3).
The three exempted layers (Models, Requests, Middleware, Providers) all have
legitimate extension patterns that would be broken by a blanket `final` rule.

The abstract base `Controller` is explicitly ignored in the arch rule:
`->ignoring('App\Http\Controllers\Controller')`.

### Consequences

- The arch suite will fail the moment a developer adds a concrete controller
  that is not `final`, catching the mistake before CI completes.
- Models, Requests, Middleware, and Providers remain unconstrained by `final`;
  teams that want tighter rules can add their own `arch()` expectations.
- The Pest arch `->classes()` modifier is required for the controllers rule to
  scope it to class declarations only, skipping any traits, enums, or interfaces
  that may exist in the namespace.
