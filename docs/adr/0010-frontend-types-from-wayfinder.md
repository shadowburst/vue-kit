# ADR 0010: Frontend Types Always Come From Wayfinder

- **Status:** Accepted
- **Date:** May 2026

## Context

Wayfinder generates TypeScript for routes, models, enums, Inertia
shared data, per-page response shapes, and FormRequest input shapes
into `resources/js/wayfinder/`. Today the kit uses Wayfinder for
*route* helpers (`ProfileController.update.form()` etc.) but the
generated *type* surface is largely unused: pages hand-roll
`type Props = { ... }`, components import a hand-rolled `User` /
`Team` / `Auth` from `resources/js/types/auth.ts`, and
`resources/js/types/global.d.ts` re-declares `InertiaConfig.sharedPageProps`
in direct conflict with Wayfinder's own `inertia-config.d.ts`.

The duplication is silent (TS module-augmentation merges the two
declarations) but corrosive: the hand-rolled types drift, fields go
missing, and pages reach for `as string` casts when generated types
say `unknown`. This ADR records the rule that closes that drift, the
mechanics that make it work, and the explicit exceptions.

---

## Decision 1 — Wayfinder Owns Every Type That Crosses the Laravel/JS Boundary

### Choice Made

Anything that originates on the backend — page props, FormRequest
input shapes, Inertia/JSON response shapes, model attributes,
enum values — is consumed in TypeScript via the Wayfinder-generated
types under `resources/js/wayfinder/`. Hand-rolled TypeScript types
for backend-shaped data are forbidden.

Hand-rolled types remain legal *only* for purely client-side concerns:
UI state, navigation config, local component variants, etc.
Concretely: `resources/js/types/navigation.ts` and `ui.ts` stay;
`resources/js/types/auth.ts` is deleted.

### Alternatives Considered

**Hand-rolled TS types as the source of truth.**
*Rejected:* this is the status quo and it is the failure mode this
ADR is written to fix. `User`, `Team`, and `Auth` already drift
between `resources/js/types/auth.ts` and `App.Models.*`; the
`InertiaConfig` declaration in `global.d.ts` competes with Wayfinder's
generated one; pages cast `page.props.locale as string` because the
hand-rolled shared-data type lies about it. There is no path to
correctness that keeps the hand-rolled source.

**Spatie Laravel Data DTOs as the canonical input/output shape**
(controllers accept `ProfileData`, return `UserResource` produced
from a Data class). `App.Data.UserSettingsData` already proves the
pipeline works.
*Rejected for now:* it is a larger architectural shift than this ADR
needs to make. Wayfinder already reads FormRequest + `Inertia::render`
shapes directly; that is the smallest mechanism that closes the
drift. A future move to Data DTOs for input/output is not blocked
by this ADR — Wayfinder will read those shapes too.

### Reasoning

Generated types remove a class of bug entirely: backend changes
that ought to break the frontend now do break the frontend, in
typecheck, before the PR ships. The cost is that backend authors
must keep their controllers and FormRequests written in a way
Wayfinder can statically inspect (Decision 4); that cost is small
and localised compared to the cost of the silent drift it replaces.

### Consequences

- `resources/js/types/auth.ts` is deleted. The `InertiaConfig`
  declaration block inside `resources/js/types/global.d.ts` is also
  deleted — Wayfinder's `inertia-config.d.ts` is the only declaration.
- `resources/js/types/navigation.ts`, `ui.ts`, `vue-shims.d.ts`, and
  the Vite `ImportMetaEnv` augmentation in `global.d.ts` remain.
- New backend models / enums / FormRequests automatically gain
  TypeScript types when Wayfinder regenerates; no parallel TS
  edit is needed.

---

## Decision 2 — Page Components Use `Inertia.Pages.*`, Not `App.Http.Controllers.*.Response`

### Choice Made

Inertia page components type their props as `Inertia.Pages.<PagePath>`
— the page-shaped type Wayfinder derives from
`Inertia::render(...)` plus the merged `Inertia.SharedData`.

```ts
// resources/js/pages/settings/Profile.vue
defineProps<Inertia.Pages.Settings.Profile>();
```

`App.Http.Controllers.*.Response` is reserved for non-Inertia
endpoints (JSON / XHR responses). For Inertia routes the two types
point at the same shape, so using the controller-shaped type would
just be a longer name for the same thing.

### Alternatives Considered

**Use `App.Http.Controllers.*.<Action>.Response`.**
*Rejected:* the controller-shaped type for an Inertia route resolves
to `Inertia.Pages.<Page>` anyway. Picking the controller view forces
the page file to import a name that doesn't match its own location
(`Settings.Profile.vue` ↔ `ProfileController.Edit`), and gives up
the automatic `Inertia.SharedData` merge.

**Keep a local `type Props` in each page file, mirroring the
generated shape.**
*Rejected:* this is the duplication this ADR exists to remove.

### Reasoning

The page is the natural unit on the frontend. `Inertia.Pages.*` is
keyed by component path, mirrors the file's own location, and
already merges shared data so pages don't have to think about it.
The controller view exists for endpoints that don't render a page
(future API routes), where there is no page-shaped type to use.

### Consequences

- Backend becomes the canonical source for prop shapes.
  `HandleInertiaRequests::share()` must declare precise return
  types — the current `name: unknown`, `appLocales: []`, untyped
  `locale` are tightened so Wayfinder emits useful types and pages
  can drop `as string` casts.
- Controller methods returning `Inertia::render(...)` must use
  inline array literals with values whose static types Wayfinder
  can infer. PHPDoc-only annotations on session-derived values
  (`status` from `$request->session()->get('status')`) get tightened
  to typed locals or typed accessors so the prop is `string|null`,
  not `unknown`.

---

## Decision 3 — Sub-Components Reference `App.Models.*` / `App.Data.*` / `App.Enums.*` Directly

### Choice Made

Components below the page level type backend-shaped props using the
generated model / data / enum types directly:

```ts
// resources/js/components/UserInfo.vue
defineProps<{ user: App.Models.User; showEmail?: boolean }>();
```

Hand-rolled fields for purely UI concerns (`showEmail` above) remain
hand-rolled. Only the backend-shaped pieces must come from the
generated namespace.

### Alternatives Considered

**`Pick<Inertia.Pages.X, 'user'>` so sub-components stay coupled to
the page that renders them.**
*Rejected:* couples a reusable component to a single page's prop
shape. `UserInfo.vue` is rendered from many places.

**Hand-rolled subset (`{ user: { id: number; name: string } }`)
declaring only the fields the component reads.**
*Rejected:* re-introduces hand-rolled types by the back door. The
narrowing benefit is theoretical — `App.Models.User` is the same
type the parent already passed in, and over-typing rarely causes
real bugs.

### Reasoning

Backend-shaped data passed as a prop is still backend-shaped
data — there is no point at which the rule from Decision 1 should
stop applying. Components, layouts, composables, and stores all
follow the same rule.

### Consequences

- The rule is "if it crosses the Laravel boundary, Wayfinder owns
  it" — not "if it's a page, Wayfinder owns it". This is wider in
  scope than the file path `pages/**` would suggest.
- Existing components importing `User` / `Team` / `Auth` from
  `@/types` (`UserMenuContent.vue`, `UserInfo.vue`, etc.) migrate
  to `App.Models.*`.

> **Narrowed by ADR-0017:** For Eloquent model shapes specifically, sub-components reference `App.Http.Resources.*`-derived types (not `App.Models.*`) — see [ADR-0017](0017-eloquent-via-jsonresource.md). Non-Eloquent typed shapes (`AuthAbilitiesData`, `UserSettingsData`) remain in `App.Data.*`.

---

## Decision 4 — FormRequests Use Inline Rules; Fortify Gets Typed via Custom Controllers

### Choice Made

Every FormRequest's `rules()` method returns an inline array literal
that Wayfinder can statically inspect. Trait-based rule composition
(`return $this->profileRules(...);`) is removed. The
`App\Concerns\ProfileValidationRules` trait is deleted; the same
rules are written inline in `ProfileUpdateRequest` and any other
caller.

Fortify's vendor controllers cannot accept project FormRequests
directly. To produce typed Wayfinder Request shapes for auth routes,
the project replaces Fortify's default routing with custom thin
controllers that delegate to Fortify but accept a project-defined
FormRequest. The exact mechanism — `Fortify::ignoreRoutes()` plus
project-owned routes, or Fortify's `using()` callbacks — is to be
confirmed at implementation time; the constraint is that the
generated `Request` namespace for auth actions must not be
`Record<string, unknown>` or the malformed `{ "": string; ... }`
currently emitted.

### Alternatives Considered

**Keep the trait-based rule composition.**
*Rejected:* Wayfinder reads the literal returned by `rules()`. A
trait method whose return is dynamic ends up as `Record<string, unknown>`,
which makes the generated Request type useless. The DRY benefit of
the trait is small (a few lines per FormRequest); the type-loss
cost is measured per-form-submission for the lifetime of the kit.

**PHPDoc the `rules()` return shape**
(`@return array{name: string, email: string}`).
*Rejected:* Wayfinder's existing output for this codebase shows it
reads the literal expression, not the docblock — `LocaleStoreRequest`
generates `{ locale: string }` from its inline literal, while
`ProfileUpdateRequest` generates `Record<string, unknown>` despite
having a structured `@return` on its trait. The PHPDoc path is not
a working escape hatch with the current Wayfinder version.

**Wrap Fortify in custom controllers** vs. **add Fortify to the
Wayfinder ignore list and hand-type `useForm` initial values for
auth pages.**
*Chosen:* wrap. Auth pages are too important to leave as the one
exception to the rule; making them conform with the rest of the
codebase is worth a small amount of routing setup.

**Move to Data DTOs for input** (controllers accept `ProfileData`,
not `ProfileUpdateRequest`).
*Deferred:* see Decision 1's alternatives. Not blocked by this ADR.

### Reasoning

Wayfinder's generation works by statically reading PHP source. Any
indirection between the controller's typed parameter and the
literal array in `rules()` breaks the chain. Inline rules and
project-owned controllers keep the chain short enough that the
mechanism actually delivers types.

### Consequences

- `App\Concerns\ProfileValidationRules` is deleted. `name` /
  `email` rules are written inline in `ProfileUpdateRequest`
  and any other caller. The DRY loss is ~10 LOC.
- Auth routes (login, register, two-factor, recovery codes)
  gain project-owned controller wrappers; ADR 0006 (controller
  method conventions) extends to cover them.
- The Wayfinder-generated `Request` namespace under
  `resources/js/wayfinder/Laravel/Fortify/...` becomes unused
  once auth routes resolve to project controllers. Adding
  `Laravel\Fortify\*` to `wayfinder.ignore.names` prevents the
  malformed Fortify Request types from being regenerated at all.

---

## Decision 5 — Enums Are Imported From `@/wayfinder/App/Enums/*`, Never Sent as Page Props

### Choice Made

When the frontend needs the values of a backend enum (e.g.
`RoleName`, `PermissionName`, `AppLocale`), it imports them
directly from the generated module:

```ts
import { RoleName } from '@/wayfinder/App/Enums/RoleName';
```

Controllers do not pass enum values or `Enum::cases()` arrays
through `Inertia::render(...)` props.

### Alternatives Considered

**Pass `RoleName::cases()` as a page prop** so the page sees the
canonical list as data.
*Rejected:* the list is already statically known on both sides.
Sending it through props makes every page that needs it fatter,
introduces an extra place where the list could fall out of sync,
and adds a TypeScript narrowing burden (the prop type is
`RoleName[]`, not literal `'owner' | 'admin' | 'member'`).

### Reasoning

Enums are pure values, not state. There is no per-request decision
the backend needs to make about which cases exist. The Wayfinder
TypeScript module is the single source for both the type and the
runtime values.

### Consequences

- Pages and components that need enum values import them; no
  prop wiring required.
- Adding a case to a backend enum automatically propagates to the
  frontend at next Wayfinder regen.

---

## Decision 6 — Enforcement: Typecheck + CI Wayfinder Regen, No Custom Lint Rules

### Choice Made

The rules in this ADR are enforced by:

1. **`vue-tsc --noEmit`** in `pnpm run check`. Once pages use
   `defineProps<Inertia.Pages.X>()` and components use
   `App.Models.*`, any backend drift surfaces as a typecheck error.
2. **CI runs `php artisan wayfinder:generate` before
   `pnpm run check`** so generated types are fresh in CI. Local
   development relies on `vite-plugin-watch-and-run`'s existing
   regen-on-PHP-change watcher.
3. **This ADR**, referenced in code review when a hand-rolled
   backend-shaped type is proposed.

No custom ESLint rules are added. No `no-restricted-imports` rule
on `@/types` (the `User` / `Team` / `Auth` exports are deleted, so
re-introducing them would already fail compilation).

### Alternatives Considered

**Custom ESLint rule flagging hand-rolled object types matching
generated names** (e.g. forbid a local `type User = { ... }`).
*Rejected:* high maintenance, narrow value. The failure mode it
protects against — "someone declares `type Props = { user: { id:
number; name: string } }` from scratch" — is caught instead by
review against this ADR, and the result still typechecks against
its caller, so the actual blast radius is small.

**Pre-commit Wayfinder regen** (run `wayfinder:generate` in a
`pre-commit` hook so committed `wayfinder/` is always fresh).
*Rejected:* slows every commit, fights `vite-plugin-watch-and-run`,
and doesn't add anything CI doesn't already cover. CI is the
authoritative gate; local watcher is the fast feedback.

**Run `wayfinder:generate` as part of `pnpm run check` locally.**
*Rejected:* the PHP toolchain doesn't always belong inside the
JS check pipeline, and local dev already has the watcher. CI
runs `wayfinder:generate` separately before `pnpm run check`.

### Reasoning

Typecheck is the only enforcement that fails on real correctness
violations rather than stylistic ones. Custom lint rules either
under-cover (miss real cases) or over-cover (block legitimate
hand-rolled UI types). The combination of typecheck + ADR-anchored
review covers the realistic failure modes.

### Consequences

- CI pipeline gains a `php artisan wayfinder:generate` step before
  the existing `pnpm run check`.
- A reviewer encountering a new hand-rolled backend-shaped type
  has this ADR to point at; the rule is not "I think this is bad"
  but "see ADR 0010".
- If `Inertia.SharedData` or a per-page response type is wrong in
  practice, the fix is on the backend (tighten the share() return
  types or the `Inertia::render` payload), not in TypeScript.
