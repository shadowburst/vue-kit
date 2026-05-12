## Why

The shared filter composables need clearer semantics before they are wired into DataTable consumers. The current proxy-based overrides blur Inertia form behavior with table-specific filter behavior, which makes `reset` and `isDirty` unreliable and hard to reason about.

## What Changes

- Clarify the public API for DataTable filters so table chrome fields (`q`, `page`, `per_page`, `sort_by`, and `sort_direction`) are handled separately from actual filter fields.
- Replace or avoid proxy overrides that mask Inertia `useForm()` behavior for `reset` and `isDirty`.
- Add explicit DataTable filter helpers for resetting only custom filter fields and detecting whether custom filters are active.
- Keep search query `q` out of active-filter detection and custom-filter reset behavior.
- Preserve the existing URL query submission and session-backed restoration behavior while making transform, reset, and dirty-state behavior predictable.
- Add frontend tests for reset behavior, active-filter detection, remembered values, and query serialization.

## Capabilities

### New Capabilities

- `data-table-filters`: Shared filter composable behavior for URL-backed DataTable search, pagination, sorting, custom filters, reset semantics, and active-filter detection.

### Modified Capabilities

None.

## Impact

- Affects `resources/js/composables/filters/useFilters.ts` and `resources/js/composables/filters/useDataTableFilters.ts`.
- Adds or updates Vitest coverage under `resources/js/composables/filters`.
- Preserves Inertia Vue `useForm()` compatibility and does not add frontend dependencies.
- May introduce a small public API refinement for consumers that currently rely on proxied `reset` or `isDirty` semantics.
