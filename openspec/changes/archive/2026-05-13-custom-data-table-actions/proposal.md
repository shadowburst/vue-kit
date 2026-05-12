## Why

The custom DataTable is becoming a shared UI primitive, but its action and responsive column APIs still feel implementation-driven. This change makes row actions, bulk actions, and responsive column visibility declarative and predictable while staying close to TanStack table concepts.

## What Changes

- Replace the `getActions` callback prop with declarative row `actions` and selected-row `bulkActions` objects.
- Resolve action object entries into the existing `ActionMenu` items for both per-row menus and bulk selection menus.
- Remove the DataTable-specific custom action component path in favor of the shared `ActionMenu` rendering model.
- Add a responsive column visibility API where consumers provide column ids mapped to Tailwind breakpoint refs, such as `{ email: md }`.
- Preserve TanStack column visibility behavior for columns that are not configured for responsive hiding.
- **BREAKING**: Existing DataTable consumers using `getActions` or custom DataTable action definitions must migrate to action objects.

## Capabilities

### New Capabilities

- `custom-data-table`: Shared DataTable behavior for action menus, bulk actions, and responsive column visibility.

### Modified Capabilities

None.

## Impact

- Affects DataTable components and composables under `resources/js/components/ui/custom/data-table` and `resources/js/composables/data-table`.
- Affects TypeScript APIs exported by the custom DataTable package.
- Reuses existing `ActionMenu`, `SmartMenu`, Tailwind breakpoint composables, and TanStack Vue Table state.
- Does not add new frontend dependencies.
