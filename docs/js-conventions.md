# JavaScript Conventions

This file is the human-readable JavaScript, TypeScript, and Vue convention reference for this repo. ESLint, Prettier, TypeScript, and Vitest remain the executable source of truth; when this file and tooling disagree, fix the drift instead of ignoring either one.

## Baseline

- Use TypeScript for application code.
- Use `<script setup lang="ts">` in Vue single-file components.
- Follow the existing Vue, Inertia, and Spatie style used in nearby files.
- Prefer descriptive names over abbreviations.
- Run `pnpm run fix` when changing frontend code, or the narrower `pnpm run format`, `pnpm run lint`, and `pnpm run analysis:check` commands when appropriate.

## Formatting

- Use 4-space indentation.
- Use semicolons.
- Use single quotes.
- Keep lines within 120 characters.
- Always use curly braces for control statements.
- Let Prettier organize imports, attributes, and Tailwind classes.

## TypeScript

- Prefer `const`; use `let` only when reassignment is required.
- Never use `var`.
- Use `import type` for type-only imports.
- Define named object types for component props, return values, and reusable data shapes.
- Avoid `any` unless integrating with an untyped external boundary.

## Vue Components

- Use `<script setup lang="ts">`.
- Keep components in the existing structure under `resources/js/components`, `resources/js/pages`, and `resources/js/layouts`.
- Use `defineProps`, `defineModel`, and `withDefaults` consistently with nearby components.
- Prefer computed values over duplicating derived state.
- Keep templates readable; extract a component when a section has its own responsibility.

## Inertia

- Use Wayfinder controller imports for routes instead of hardcoded URLs.
- Use `setLayoutProps()` for page layout metadata.
- Use `useForm()` for Inertia-backed forms.
- Use `useHttp()` for standalone Inertia HTTP requests.
- Use `Head` for page titles.

## Forms

- Import `Form`, `Field`, `FieldControl`, `FieldLabel`, and `FieldError` from `@/components/ui/custom/form`.
- Do not import `Form` directly from `@inertiajs/vue3`.
- Do not import field primitives from `@/components/ui/field` in application code.
- Bind validation errors through `FieldError`.

## Spatie Generated Types

- Import backend-shaped TypeScript contracts from `@/spatie/types`.
- Use `import type` for Spatie types; they are compile-time contracts, not runtime values.
- Use generated `*Props` types with `defineProps<...>()` on Inertia pages.
- Use generated `*Resource` types for props that receive backend resources such as `UserResource` or `TeamResource`.
- Use generated `*Request` types for `useForm()` data and `useHttp()` request payloads.
- Do not hand-write frontend types for backend-shaped payloads when a Spatie Data, Resource, Props, or Request class can be the source of truth.
- Use PHP `Resource` classes for output/page props and PHP `Data` classes for request/input/nested data.
- Keep shared Inertia props flowing through `App\Data\Shared\SharedData`; frontend access comes from Inertia `PageProps`.
- Use Wayfinder, not Spatie types, when runtime values are needed, such as enum values, routes, and controller actions.
- Regenerate types with `php artisan typescript:transform` after changing PHP Data, Resource, Props, Request, or enum classes.

## i18n

- Use `trans()` and `trans_choice()` from `laravel-vue-i18n`.
- Do not use `$t()` or `$tChoice()`.
- Keep translation keys explicit and grouped by feature.

## Styling

- Use Tailwind utility classes.
- Use `cn()` when composing conditional class names.
- Let the Tailwind Prettier plugin sort class order.
- Reuse existing shadcn-vue/custom UI components before adding new primitives.

## Composables

- Put reusable stateful logic in `resources/js/composables`.
- Name composables with the `use*` prefix.
- Return typed objects for public composable APIs.
- Keep shared module-level state intentional and obvious.

## Tests

- Use Vitest for frontend tests.
- Put tests near the code when that is the existing pattern.
- Add or update tests for behavior changes and lint convention changes.
- Run the smallest relevant test command first.

## Enforcement

These conventions are partially enforced by ESLint, Prettier, TypeScript, and Vitest. If a convention should never regress, prefer adding or updating an executable lint rule or test alongside this document.
