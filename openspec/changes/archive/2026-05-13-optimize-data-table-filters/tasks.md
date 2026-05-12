## 1. Test Coverage

- [x] 1.1 Add Vitest coverage for `useFilters()` query serialization that omits `undefined`, blank strings, and empty arrays while preserving non-empty values.
- [x] 1.2 Add Vitest coverage for `useFilters()` remembered state restoration and `reset_filters` clearing behavior.
- [x] 1.3 Add Vitest coverage for `useDataTableFilters()` active custom-filter detection, including that `q`, `page`, `per_page`, `sort_by`, and `sort_direction` are ignored.
- [x] 1.4 Add Vitest coverage for `useDataTableFilters()` custom-filter reset behavior, including that `q`, pagination, and sorting values are preserved.
- [x] 1.5 Add Vitest coverage that native Inertia form `isDirty` and `reset()` semantics remain available separately from DataTable-specific helpers.

## 2. Core Filter Composable

- [x] 2.1 Extract shared internal empty-value detection for query serialization and active-filter checks.
- [x] 2.2 Keep `useFilters()` submission URL-query based through `router.visit()` with cleaned query data and existing default visit options.
- [x] 2.3 Clarify whether remembered state stores transformed or raw form data, then align implementation and tests to the chosen behavior.
- [x] 2.4 Ensure `transform()` chaining returns the public form object consistently.

## 3. DataTable Filter API

- [x] 3.1 Replace proxy-based `reset` and `isDirty` overrides with explicit DataTable-specific helpers such as `resetFilters()` and `hasActiveFilters`.
- [x] 3.2 Define table chrome fields as `q`, `page`, `per_page`, `sort_by`, and `sort_direction` for active-filter detection and custom-filter reset behavior.
- [x] 3.3 Ensure `resetFilters()` clears only custom filter fields and preserves table chrome field values.
- [x] 3.4 Ensure active-filter detection returns true only for non-empty custom filter fields.
- [x] 3.5 Review watch/debounce behavior so filter changes reset page before submitting without unnecessary duplicate or mount-time visits.

## 4. Verification

- [x] 4.1 Run the focused filters Vitest file once added or updated.
- [x] 4.2 Run `pnpm run analysis:check` for TypeScript compatibility.
- [x] 4.3 Run `pnpm run lint:check` or the relevant frontend check command for affected files.
