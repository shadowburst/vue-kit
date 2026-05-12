## 1. Action API

- [x] 1.1 Define DataTable row action and bulk action TypeScript types with static values and row/rows resolver functions.
- [x] 1.2 Replace the DataTable `getActions` prop and root context field with `actions` and `bulkActions` object props.
- [x] 1.3 Add DataTable action resolution logic that filters hidden actions and converts visible row actions into `ActionMenu` items.
- [x] 1.4 Add DataTable bulk action resolution logic that filters hidden actions and converts visible selected-row actions into `ActionMenu` items.
- [x] 1.5 Remove the DataTable-specific custom action type and builder path.

## 2. Responsive Column Visibility

- [x] 2.1 Add a responsive column visibility option that accepts a map of column ids to Tailwind breakpoint refs.
- [x] 2.2 Wire responsive visibility changes into TanStack column visibility state for configured columns only.
- [x] 2.3 Ensure unconfigured columns are not changed by breakpoint visibility logic.
- [x] 2.4 Preserve existing `initialState.columnVisibility` behavior for static TanStack visibility defaults.

## 3. Usage Migration

- [x] 3.1 Update existing DataTable component imports that still point to the previous data-table path.
- [x] 3.2 Update any current DataTable usages from `getActions` or action builder helpers to `actions` and `bulkActions` objects.
- [x] 3.3 Replace any custom DataTable action rendering with supported `ActionMenu` actions or table cell slots.

## 4. Verification

- [x] 4.1 Add or update Vitest coverage for row action resolution, bulk action resolution, and hidden/disabled action behavior.
- [x] 4.2 Add or update Vitest coverage for responsive column visibility using Tailwind breakpoint refs.
- [x] 4.3 Run the smallest relevant frontend test command for the DataTable changes.
- [x] 4.4 Run TypeScript analysis for affected frontend code.
