## Context

The current filter composables wrap Inertia Vue `useForm()` to provide URL query submission, session-backed restoration, and DataTable-specific behavior. `useDataTableFilters()` currently returns a `Proxy` around the Inertia form to override `reset` and `isDirty`, but those names already have meaning inside Inertia's form lifecycle.

DataTable filter state has two different categories of fields:

```txt
Table chrome fields
    q
    page
    per_page
    sort_by
    sort_direction

Custom filter fields
    every other field supplied by the consumer
```

`q` is intentionally table chrome: it participates in query submission and remembered state, but it MUST NOT count as an active custom filter and MUST NOT be cleared by a custom-filter reset helper.

## Goals / Non-Goals

**Goals:**

- Preserve Inertia form compatibility by avoiding proxy overrides for core form semantics where possible.
- Provide explicit helpers for DataTable-specific active-filter detection and reset behavior.
- Keep `q`, pagination, and sorting separate from custom filters.
- Keep empty values out of URL query submissions consistently.
- Keep session-backed restoration behavior predictable and covered by tests.
- Add focused Vitest coverage for the composables before relying on them in table consumers.

**Non-Goals:**

- Change backend filtering or sorting behavior.
- Add a new table component API beyond the filter composable contract.
- Replace Inertia `useForm()` with a different form or state library.
- Count the search query `q` as an active custom filter.
- Add new frontend dependencies.

## Decisions

### Keep Inertia form semantics intact

The DataTable composable should avoid masking `isDirty` and `reset` with a `Proxy`. Inertia stores `isDirty` as mutable reactive form state and computes it against internal defaults. Overriding reads through a proxy makes consumer behavior depend on how the form object is accessed and can be bypassed by methods that return the original target.

Instead, DataTable-specific behavior should use explicit names such as `hasActiveFilters` and `resetFilters`. This keeps normal Inertia form behavior available while making DataTable behavior discoverable.

Alternative considered: keep the proxy and fix edge cases for `transform()`, `resetAndClearErrors()`, and chained method returns. That preserves the current surface area but continues to overload Inertia concepts and makes future maintenance more fragile.

### Treat `q` as table chrome, not a custom filter

`q` should be grouped with pagination and sorting fields for DataTable-specific reset and active-filter detection. Search text is part of the table's query state, but it is not a custom filter badge/count signal.

Alternative considered: classify `q` as a filter because it narrows results. The user explicitly decided not to count `q` as a filter, so the implementation should encode that distinction.

### Centralize empty-value and table-field checks

The composables should share small internal helpers for determining empty query values and table chrome fields. This avoids repeating checks for `undefined`, empty arrays, and blank strings across submit, reset, and active-filter detection logic.

Alternative considered: leave the checks inline in each reducer or loop. That keeps fewer named functions but risks drift between query serialization and active-filter detection.

### Submit behavior should remain URL-query based

`useFilters()` should continue to submit through `router.visit()` with cleaned query data, `preserveScroll`, `preserveState`, and `replace` defaults. The change should make this behavior easier to test and reason about, not change the navigation model.

Alternative considered: use Inertia form `get()` directly. The current wrapper intentionally controls query cleanup and default visit options, so preserving that behavior is the smaller change.

## Risks / Trade-offs

- Existing consumers may expect `filters.isDirty` to mean active custom filters → Mitigation: expose a clearly named replacement and update call sites during implementation.
- Keeping Inertia `reset()` unchanged means DataTable-specific reset requires a new helper → Mitigation: document and test `resetFilters()` as the DataTable reset path.
- Session storage and transform behavior can remain coupled if not clarified → Mitigation: add tests that pin whether transformed data or raw form data is remembered.
- Watch-driven submission can be noisy on mount or during reset → Mitigation: add tests around reset and page-reset behavior before changing debounce or immediate watcher semantics.
