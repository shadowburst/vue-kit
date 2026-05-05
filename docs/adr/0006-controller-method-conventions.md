# Controllers use Laravel resource method names; no `__invoke`

Every concrete controller under `App\Http\Controllers` must satisfy three rules, enforced by arch tests in `tests/`:

1. Every public **instance** method is either `__construct` or one of the seven Laravel resource verbs: `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`.
2. No `__invoke` (a subset of rule 1, called out separately so failures point at it directly).
3. Every non-public method is `private` — never `protected`.

Public `static` methods are exempt to allow framework hooks such as `HasMiddleware::middleware()`. Vendor controllers (Fortify, etc.) live under different namespaces and are out of scope.

The motivation is consistency. Half the existing controllers already use resource-style methods (`ProfileController::edit`/`update`/`destroy`, `SecurityController::edit`/`update`, `LocaleController::edit`/`update`/`store`); the `__invoke` ones (`Home`, `Dashboard`, `Appearance`) are the outliers. Picking one style across the codebase makes route → method navigation predictable and removes the "is this a single-action invokable or a resource?" cognitive step when reading routes.

`__invoke` single-action controllers are Laravel-recommended in the docs, so this is a deliberate deviation from idiomatic Laravel in favor of a single in-repo convention.

## Consequences

- Routes cannot use the `Controller::class` shorthand for single-action controllers. Use the explicit `[Controller::class, 'verb']` form: `Route::get('/', [HomeController::class, 'index'])` instead of `Route::get('/', HomeController::class)`.
- Landing-page controllers (`HomeController`, `DashboardController`) use `index` — the resource verb that means "the page you land on for this section." Settings form controllers (`AppearanceController`, etc.) use `edit` to match the rest of `Settings/*`.
- The base `App\Http\Controllers\Controller` is intentionally empty (Laravel 11+ slim skeleton). If a future need for shared controller behavior appears, that decision should be made deliberately and may require revisiting rule 3 (currently `private`-only) — easier to relax later than to tighten now.
- The arch tests live alongside other Pest tests. They use a small reflection helper because Pest Arch's built-in expectations don't natively cover method-name whitelisting or visibility checks.
