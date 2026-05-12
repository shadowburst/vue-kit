# PHP Conventions

This file is the human-readable PHP convention reference for this repo. The architecture tests remain the executable source of truth; when this file and a test disagree, fix the drift instead of ignoring either one.

## Baseline

- Use `declare(strict_types=1);` in every PHP file.
- Follow the existing Laravel and Spatie style used in nearby files.
- Use explicit parameter and return types on methods and functions.
- Use PHPDoc for generics, Eloquent properties, array shapes, and other type information PHP cannot express.
- Prefer descriptive names such as `isRegisteredForDiscounts` over abbreviations such as `discount()`.
- Run `composer fix` when changing PHP code, or the narrower `composer format`, `composer lint`, and `composer analysis:check` commands when appropriate.

## Architecture

- Keep classes in the existing Laravel structure; do not add new top-level folders without a clear need.
- Group type folders by noun, domain, integration boundary, or cohesive feature.
- Do not place PHP classes directly at the root of enforced type folders: `app/Actions`, `app/Data`, `app/Enums`, `app/Http/Controllers`, `app/Http/Middleware`, or `app/Services`.
- `app/Models`, `app/Policies`, `app/Observers`, `app/Providers`, `app/Concerns`, and `app/Listeners` are intentionally exempt from noun subgrouping.
- Models must not depend on the HTTP layer or actions.
- Providers and actions must not depend on controllers.

## Controllers

- Controllers are `final`, except for the base `App\Http\Controllers\Controller` class.
- Use resource-style public methods only: `index`, `create`, `store`, `show`, `edit`, `update`, and `destroy`.
- Do not use invokable controllers.
- Keep controller helpers `private`; do not add `protected` controller methods.
- Keep controllers thin: authorize, delegate business work to actions or models, and return responses.
- Use `Inertia::render()` for Inertia pages and named routes for redirects.
- Use Spatie Data request objects for validated input instead of Laravel `FormRequest` classes.

## Actions

- Put reusable business operations in `app/Actions/<Noun>`.
- Non-Fortify actions are `final`.
- Do not add an `Action` suffix to non-Fortify action class names.
- Non-Fortify actions use the `Spatie\QueueableAction\QueueableAction` trait.
- Non-Fortify actions expose exactly one public non-constructor method, named `execute`.
- Use database transactions inside actions when multiple writes must succeed or fail together.

## Data Objects

- Use Spatie Laravel Data instead of Laravel `FormRequest` and `JsonResource` classes.
- Put every data class in a noun subdirectory under `app/Data`.
- Use these suffixes intentionally:
  - `*Request` and `*Data` extend `Spatie\LaravelData\Data`.
  - `*Resource` and `*Props` extend `Spatie\LaravelData\Resource`.
- Concrete data classes are `final` unless they are intentionally abstract bases.
- Request data classes with validation rules must declare `attributes()` with one entry per public property.
- String `max:` validation rules must use `App\Enums\Validation\StringMaxLength` instead of literal numeric rules.
- Use lazy properties on resources when a value depends on loaded relationships or appended attributes.

## Policies

- Policies are `final`.
- Check permissions, not roles, inside policies.
- Do not import the `Role` enum in policies.
- Do not call `hasRole`, `hasAnyRole`, `hasAllRoles`, or `hasExactRoles` in policies.
- Do not use role-name string literals in policies.

## Models

- Keep model-owned domain behavior on the model when it naturally belongs to that model.
- Document model properties, relationships, and computed attributes with PHPDoc.
- Type relationship methods with Laravel relation return types such as `BelongsTo`.
- Use Eloquent attribute objects for computed attributes and casts that need behavior.
- Prefer factories in tests over manually constructing model rows.

## Enums

- Files under `app/Enums` must be PHP enums.
- Use TitleCase case names, such as `Manager`, `TeamView`, or `Monthly`.
- Group enums in noun subdirectories.

## Tests

- Use Pest for PHP tests.
- Prefer feature tests for application behavior.
- Add or update tests for every behavior change.
- Keep tests out of the root of `tests/Feature`; group them by domain or feature.
- Run the smallest relevant test command first, such as `php artisan test --compact tests/Feature/Team/CreateTeamTest.php`.
- Run architecture tests when changing conventions, structure, controllers, actions, policies, or data objects.

## Enforcement

These conventions are partially enforced by tests in `tests/Arch` and `tests/Feature/Architecture`. If a convention should never regress, prefer adding or updating an architecture test alongside this document.
