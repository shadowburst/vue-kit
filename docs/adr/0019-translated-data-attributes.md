# ADR 0019: Validation Labels on Spatie Data Classes Are Inlined Per Class

- **Status:** Accepted
- **Date:** 2026-05-12

## Context

ADR-0017 established Spatie Data as the serialisation layer across the Inertia/JS boundary, with `*Request` and hydrated `*Data` classes carrying their own `rules()`. Validation messages reference `:attribute`, which Laravel substitutes with the property name — `password_confirmation` reads as `password_confirmation` in the rendered error, untranslated and snake-case.

Spatie Data exposes a `public static function attributes(): array` hook on every `Data` subclass for swapping the substitution. The repo runs `lang/en/` and `lang/fr/` per-noun (`auth.php`, `team.php`, `billing.php`, `settings.php`), each carrying a flat `attributes` sub-array.

An earlier iteration of this ADR auto-derived the label map via a `WithTranslatedAttributes` trait that reflected on public constructor properties and resolved each `$prop` to `__("{lowercase-noun}.attributes.{prop}")`. The trait was a single source of truth, but it locked every property in a class to the same noun. Cross-cutting properties (e.g. `query`, `sort_direction`) that aren't owned by any one noun couldn't reach a shared `common.attributes.*` key without subclass override, and the resulting "trait plus selective override" surface was more code, not less, than writing the map out.

## Decision

Every Spatie Data subclass hydrated from user input (the four-stub taxonomy's `data` and `data-request`) declares a `public static function attributes(): array` returning a **literal map** from property name to translated label:

```php
public static function attributes(): array
{
    return [
        'name'                  => __('auth.attributes.name'),
        'email'                 => __('auth.attributes.email'),
        'password'              => __('auth.attributes.password'),
        'password_confirmation' => __('auth.attributes.password_confirmation'),
    ];
}
```

The map's string on each line names the exact lang key consulted — `auth.attributes.email`, `common.attributes.query`, etc. — so the lookup is readable from the class file alone, with no reflection or namespace-derived convention to memorise.

**Per-noun lang files remain the default home for labels.** Existing `lang/{locale}/{noun}.php` `attributes` arrays are unchanged. Each Data class points its property to the noun it logically belongs to (`AuthLoginRequest::email` → `auth.attributes.email`, `TeamCreateRequest::name` → `team.attributes.name`).

**`lang/{locale}/common.php` `attributes` is added for properties that are genuinely noun-independent** — e.g. `query`, `sort_direction`, `per_page`, things owned by a generic listing or filter shape rather than any domain noun. Properties tied to a noun (even when they share a wire name across nouns, like `email`) stay in the noun file; the noun owns the label's context, even when the English string happens to repeat.

`stubs/data.stub` and `stubs/data-request.stub` ship with an empty `attributes()` skeleton so generated classes have the slot in place; the author fills it as they fill `rules()`.

`data-resource` and `data-props` (extending `Spatie\LaravelData\Resource`) are exempt — they have no validation pipeline that consults `attributes()`.

**An arch test in `tests/Arch/DataTest.php` enforces parity.** For every concrete class under `app/Data/**` that extends `Spatie\LaravelData\Data` (and not `Resource`), the test asserts that `array_keys(static::attributes())` equals the set of public non-static property names. A class that gains a property but forgets to extend `attributes()` fails the build; without the test, the failure would be silent (Laravel falls back to the snake_case property name, which reads as plausible English).

## Considered alternatives

**Auto-derive via `App\Concerns\WithTranslatedAttributes` trait.**
Rejected: a single noun-derivation rule is too rigid for a Data class with mixed property origins. A listing request with `query`, `sort_direction`, and a domain-specific `team_id` would need either three duplicated `common.attributes.team_id`/`team.attributes.query` entries (one per host noun) or a per-class override that defeats the trait. Inlining handles the mix on each line without machinery.

**Trait plus per-class overrides.**
Rejected: combines the cost of the trait (one indirection to understand) with the cost of inlining (the override has to live somewhere). Net surface is larger than either alone. If the override is needed often enough to matter, the trait isn't pulling its weight.

**Global `lang/{locale}/validation.php` `attributes` array.**
Rejected: app-global names collide across nouns. `name` means "user name" in `AuthRegisterRequest` and "team name" in `TeamCreateRequest`; one global label can't serve both. Per-noun grouping mirrors ADR-0009 and lets the same wire name carry context-appropriate labels.

**Helper method (`self::resolveNounAttributes(['name', 'email'])`).**
Rejected: the moment one property in a class needs a `common.attributes.*` or cross-noun lookup, the helper has to be combined with literal entries — and the file now mixes two notations. One notation (literal `__()` per line) is clearer than two.

## Reasoning

The cost of inlining is per-class boilerplate: each property name appears twice (once in the constructor, once in the map) and the noun prefix repeats once per line. The benefit is that the lang key consulted is on the line. There is no rule to memorise about how `Foo::bar` resolves; if a future contributor wants to know, they read the right-hand side of the arrow.

The previous auto-derivation traded that locality for a single point of declaration. That trade made sense when every property in every class wanted the same noun. It stops making sense the moment a class wants to mix nouns (one property to `common.*`, another to its own noun), because the trait then needs an override mechanism — and the file gains two notations instead of one.

The arch test recovers the safety net the auto-derivation gave for free. The previous regression signal — "trait produces `auth.attributes.foo`, no lang entry exists, `__()` returns the raw key, validation message renders the key visibly" — disappears once the trait is gone; without parity enforcement, a missed property falls back silently to the snake_case property name. The arch test puts the loud failure back at the build step.

Cross-locale lang parity (a key in `en` but missing in `fr`) is left to PR review, as in the previous ADR. The runtime signal for that case (`__()` returning the raw key string when the locale file lacks the entry) is unchanged by this decision.

## Consequences

- `app/Concerns/WithTranslatedAttributes.php` is deleted. The trait has no further callers.
- The 11 existing `*Request` classes declare their own `attributes()` returning a literal map. The keys and lang lookups they resolve to are identical to what the trait produced — no lang file edits are needed for the migration.
- `stubs/data.stub` and `stubs/data-request.stub` no longer import the trait and include an empty `attributes()` method skeleton.
- `tests/Arch/DataTest.php` gains a parity test scoped to concrete classes under `app/Data/**` extending `Spatie\LaravelData\Data` (and not `Resource`).
- `lang/{locale}/common.php` is the home for genuinely noun-independent attribute labels (`query`, `sort_direction`, etc.); the file gains an `attributes` sub-array the first time such a property is needed.
- Missing keys in a non-default locale fall back to the raw key string, not to `en`. The convention is "land the key in every configured locale at write time" — caught in review.
- This ADR supersedes the prior trait-based decision. The directory previously held two ADRs numbered `0018`; the translated-attributes ADR is renumbered to `0019` to resolve the collision.
