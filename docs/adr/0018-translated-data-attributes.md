# ADR 0018: Validation Labels on Spatie Data Classes Auto-Derive From Per-Noun Lang Files

- **Status:** Accepted
- **Date:** May 2026

## Context

ADR-0017 established Spatie Data as the serialisation layer crossing the Inertia/JS boundary, with `*Request` and hydrated `Data` classes carrying their own `rules()`. Validation messages reference `:attribute`, which Laravel substitutes with the property name — `password_confirmation` reads as `password_confirmation` in the rendered error, untranslated and snake-case.

Spatie Data exposes a `public static function attributes(): array` hook on every `Data` subclass for swapping the substitution. The repo already runs `lang/en/` and `lang/fr/` per-noun (`auth.php`, `team.php`, `billing.php`, `settings.php`), but neither the `attributes` keys nor the `attributes()` methods are populated — French users currently see the raw English property names.

## Decision

Every Spatie Data subclass that is hydrated from user input (the four-stub taxonomy's `data` and `data-request`) declares its property labels via a per-noun translation file. The mechanism is a single trait, `App\Concerns\WithTranslatedAttributes`, applied via the relevant artisan stubs so generated classes pick it up by default.

The trait implements `attributes()` by reflecting on the class's public constructor properties and the namespace it lives under. A class at `App\Data\{Noun}\…` maps every public property `$prop` to `__("{lowercase-noun}.attributes.{prop}")`. The class declares no labels itself; the lang file is the source of truth.

Lang files gain a flat `attributes` sub-array per noun:

```php
// lang/en/auth.php
'attributes' => [
    'email' => 'email address',
    'password' => 'password',
    'name' => 'name',
    'password_confirmation' => 'password confirmation',
    'token' => 'reset token',
],
```

Keys are shared across every Data class within the noun — `AuthLoginRequest::email` and `AuthRegisterRequest::email` both resolve to `auth.attributes.email`. Every key landed in `en` must also land in `fr`; enforced in PR review, not by an arch test.

`data-resource` and `data-props` (extending `Spatie\LaravelData\Resource`) are exempt — they have no validation pipeline that would consult `attributes()`.

## Alternatives Considered

**Hand-written `attributes()` per class.**
Rejected: 11 existing request classes and a growing surface area. The convention's failure mode — forgetting to add a label when adding a property — is exactly what a forced `use` line plus stub baking removes. Per-class methods also duplicate the same noun-lookup logic on every class.

**Global `lang/{locale}/validation.php` `attributes` array.**
Rejected: app-global names collide across nouns. `name` means "user name" in `AuthRegisterRequest` and "team name" in `TeamCreateRequest` — one global label cannot serve both. Per-noun grouping mirrors ADR-0009 and lets the same property name carry context-appropriate labels.

**Nested per-class keys (`auth.attributes.register.email`).**
Rejected: when two classes within a noun share a property, they share its meaning. Per-class nesting is structural defence against a hypothetical drift that the codebase doesn't exhibit. Promote to nested keys only if a genuine context split appears.

**Arch test enforcing parity between `rules()` keys and lang entries.**
Rejected for now. The auto-derivation's failure mode is visible: `__()` returns the raw key string when nothing matches, so a missing entry renders as `auth.attributes.foo field is required` — loud enough to catch in development without CI gating. Reintroducing an arch test is a one-line addition if the visible failure proves insufficient.

**Trait in `app/Data/Concerns/` or a project base class `App\Data\AppData`.**
Rejected: a trait the developer must remember to `use` re-introduces the forgettability problem. A base class at `app/Data/AppData.php` violates the ADR-0017 D3 noun-subgrouping arch guard. Placing the trait at `app/Concerns/WithTranslatedAttributes.php` keeps it next to other cross-cutting concerns and lets the stubs bake the `use` line in — the failure mode then requires deleting the line, not forgetting to add it.

## Reasoning

The trait collapses three concerns — *what labels exist*, *where they live*, and *which classes reference them* — onto one convention readable from a single class header (`use WithTranslatedAttributes;`) and one lang file path (`lang/{locale}/{noun}.php`). Authors write nothing per property; the namespace and the property names are the contract. Adding a property to a Data class is one line; adding its translation is one line per locale.

Baking the trait into the stubs makes the convention the default, not a discipline. A future reader of a generated `*Request` class sees `use WithTranslatedAttributes;` at the top and the property names in the constructor — the lang lookup follows mechanically. The "magic" is one trait deep and lives in one file.

## Consequences

- `app/Concerns/WithTranslatedAttributes.php` is the single implementation of `attributes()` for the project's Data layer. It reflects public constructor properties and derives the noun from the class's `App\Data\{Noun}\…` namespace.
- `stubs/data.stub` and `stubs/data-request.stub` import and apply the trait. `stubs/data-resource.stub` and `stubs/data-props.stub` do not.
- `lang/en/{noun}.php` and `lang/fr/{noun}.php` each gain a flat `attributes` sub-array covering every public property across every hydrated Data class in that noun. Existing nouns affected: `auth`, `settings`, `team`, `billing`.
- The 11 existing `*Request` classes are backfilled in one PR alongside the trait and stub changes: 5 Auth, 4 Settings, 1 Team, 1 Billing. No transitional state.
- `AuthAbilitiesData` and `UserSettingsData` are confirmed output-only and out of scope. Independent of this ADR, both arguably should be rebased on `Spatie\LaravelData\Resource` per ADR-0017 D2.
- No arch test is added. The runtime failure (raw key in validation message) is the regression signal.
- Missing keys in a non-default locale fall back to the raw key string, not to `en`. The convention is "land the key in every configured locale at write time" — caught in review.
