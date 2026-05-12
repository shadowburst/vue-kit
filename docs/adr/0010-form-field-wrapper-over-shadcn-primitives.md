# ADR 0010: Form Field Wrapper Over shadcn Primitives

- **Status:** Accepted
- **Date:** May 2026

## Context

The shadcn `field` block in `resources/js/components/ui/field/` provides
layout primitives (`Field`, `FieldLabel`, `FieldDescription`, `FieldError`,
`FieldGroup`, `FieldSet`, `FieldLegend`, `FieldContent`, `FieldSeparator`,
`FieldTitle`) but does no a11y wiring: every consumer must hand-roll the
`id` / `for` link between label and input, hand-roll `aria-describedby`
to point at description and error nodes, and remember to pass `disabled`
/ `required` consistently. The block is also missing the piece that makes
that wiring possible — there is no `FieldControl` to adopt the input
element and inject the right attributes.

We need a thin custom layer above the shadcn primitives that owns the
a11y plumbing centrally so feature pages can stop touching it.

## Decision

A new wrapper layer lives at
`resources/js/components/ui/custom/form/`, exposing `Field`,
`FieldControl`, `FieldLabel`, `FieldDescription`, `FieldError`,
`FieldSet`, `FieldLegend`, `FieldGroup`, `FieldContent`, `FieldSeparator`,
`FieldTitle`. Every shadcn primitive gets its own wrapper file (no
pass-through re-exports) so future class tweaks land in one place per
primitive.

**a11y wiring is owned by `Field` via context.** `Field` allocates an
`id` (with `useId()` if not provided as a prop), exposes `required` and
`disabled` as computed refs, and provides setters
`setDescriptionId` / `setErrorId` that `FieldDescription` and
`FieldError` call from `onMounted` / `onBeforeUnmount`. The context also
exposes a computed `invalid = !!errorId.value`.

**`FieldControl` is the slot-only attribute injector.** It uses
reka-ui's `Slot` (no `as-child` toggle, no fallback element) to merge
onto its single child: `id`, `required`, `disabled`,
`aria-describedby` (built from `descriptionId` + `errorId`,
*appending* any consumer-provided `aria-describedby`), and `aria-invalid`
(driven by the computed `invalid`). `aria-required` is omitted — the
native `required` attribute is preferred.

**Sub-component props win over context.** If `FieldLabel` receives an
explicit `for`, `FieldDescription`/`FieldError` an explicit `id`, or
`FieldControl` an explicit `id` / `required` / `disabled`, those values
take precedence. Sub-components are usable outside a `Field`: with no
context, they fall back to plain prop-driven behaviour (`FieldControl`
becomes an inert `Slot`).

**`Field` root surfaces four `data-*` attributes for CSS hooks** —
`data-orientation`, `data-disabled`, `data-required`, `data-invalid` —
driven from context. `FieldLabel` renders a visible
`<span aria-hidden="true">*</span>` when `required` is true.

**The shadcn primitives are banned outside `resources/js/components/ui/**`.**
An ESLint `no-restricted-imports` rule on `@/components/ui/field` /
`@/components/ui/field/*` enforces this; the rule is turned off for any
file under `resources/js/components/ui/**` so future shadcn components
can compose the primitives if needed. `@/components/ui/label`'s `Label`
remains freely importable — it is a generic primitive, not part of the
field block.

## Alternatives Considered

**Use the shadcn primitives directly and wire ids/aria by hand at every
call site.** Rejected: every form page would re-implement the same five
or six attributes, and one omitted `aria-describedby` is invisible until
a screen-reader user reports it.

**Fork the shadcn block in place — edit `ui/field/Field.vue` and
friends to add context.** Rejected: loses the ability to re-pull the
block via the shadcn-vue CLI cleanly. The wrapper layer keeps the
primitives pristine.

**Add a heavier `Form` / `FormField` component à la shadcn-vue's
example, coupled to a form library (vee-validate, etc.).** Rejected:
the kit does not standardise on a form library — Inertia's `useForm`
is the dominant pattern. A thin context-only wrapper composes with
`useForm` without coupling to it.

**Use a registration `Set<string>` for description/error ids
(supporting multiple instances per `Field`).** Rejected as overkill:
the existing `FieldError` already accepts an `errors` array and
renders a `<ul>` for multi-message validation, so the multi-message
case is handled within a single `<FieldError>`. Two single-id setters
(`setDescriptionId` / `setErrorId`) cover the supported usage.

**Write `FieldControl` on top of `Primitive` with `as-child`** (the
project's existing pattern in `Button.vue`, `Item.vue`).
Rejected: `FieldControl` has no sensible default render — its only
job is to adopt its child. `Slot` enforces that contract; `Primitive`
without `as-child` would render a wrapper element that defeats the
attribute-injection purpose.

## Consequences

- New consumers must import field components from
  `@/components/ui/custom/form`. The ESLint rule produces a clear
  message pointing to it.
- `data-required` is rendered on the root even though no CSS rule
  consumes it today; this is an intentional hook for later styling
  decisions.
- `FieldError` registering / un-registering on mount means
  `aria-invalid` flips reactively when the error node appears or
  disappears — consumers do not maintain a separate `:invalid` prop.
  The single source of truth is "is `<FieldError>` rendered?".
- Multiple `<FieldDescription>` or `<FieldError>` inside one `Field`
  is unsupported: the last setter call wins. The existing `errors`
  array prop on `FieldError` is the supported way to render multiple
  messages.
- A Vitest + `@vue/test-utils` setup is introduced as a prerequisite
  to land specs covering id wiring, `aria-describedby` append,
  prop-overrides-context, outside-`Field` fallback, and reactive
  registration. CLAUDE.md's "every change must be programmatically
  tested" rule was previously unmet for Vue components in this repo.
