## Context

The current custom DataTable wraps TanStack Vue Table and shadcn-vue table primitives, with row selection, pagination, column pinning, column visibility, and row rendering already present. Row and bulk actions are currently exposed through a `getActions(items)` callback, while older DataTable action types include a `custom` action variant that bypasses the shared `ActionMenu` abstraction.

The desired direction is for DataTable consumers to provide declarative action objects directly, and for DataTable internals to adapt those row-aware definitions into the generic `ActionMenu` item shape. Responsive column hiding should stay close to TanStack's column visibility model while giving consumers a concise Tailwind breakpoint API.

## Goals / Non-Goals

**Goals:**

- Make row actions and bulk actions declarative objects keyed by stable action ids.
- Allow action values to be static or resolved from the current row or selected rows.
- Render DataTable actions through the existing `ActionMenu` rather than a DataTable-specific custom action component.
- Support responsive column visibility with a simple consumer API such as `{ email: md }`, where `md` is a Tailwind breakpoint ref.
- Leave columns that are not configured for responsive visibility untouched by breakpoint logic.
- Preserve TanStack's `columnVisibility` state semantics and avoid inventing a separate hidden-column state model.

**Non-Goals:**

- Add a new table rendering library or replace TanStack Vue Table.
- Add new dependencies for breakpoints, menus, or table state.
- Define server-side filtering or sorting behavior beyond keeping the table API compatible with existing pagination and filter composables.
- Preserve backward compatibility for `getActions` or the DataTable-specific custom action variant.

## Decisions

### Use action objects instead of `getActions`

DataTable will accept `actions` and `bulkActions` props as objects keyed by action id. This keeps call sites readable and gives each action a stable identity for rendering, testing, and future customization.

Alternative considered: keep `getActions(items)` and let consumers map rows to menu items manually. This preserves flexibility but forces each consumer to repeat the same adaptation logic and makes row actions awkward because a single row is passed as a one-item array.

### Keep `ActionMenu` generic and adapt inside DataTable

DataTable-specific action definitions should resolve into the existing `ActionMenu` `ActionItem[]` shape. `ActionMenu` should not know about TanStack rows or selected DataTable rows.

Alternative considered: teach `ActionMenu` about row-aware resolver functions. That would couple a generic menu primitive to DataTable concepts and make it harder to reuse elsewhere.

### Remove the DataTable custom action variant

The DataTable action model should describe menu actions: labels, icons, hrefs, callbacks, disabled state, hidden state, variants, sizes, and classes. Custom rendering belongs either in a table cell slot or in a future generic `ActionMenu` extension, not in a DataTable-only action variant.

Alternative considered: keep `custom` for escape hatches. This keeps maximum flexibility but weakens the shared menu contract and complicates the migration to a consistent action menu.

### Model responsive column visibility as breakpoint refs per column

Consumers will configure responsive visibility as a map of column ids to breakpoint refs:

```ts
const { md, lg } = useTailwindBreakpoints();

const responsiveColumnVisibility = {
    email: md,
    role: lg,
};
```

Each configured column is visible when its breakpoint ref is truthy and hidden when it is falsy. Columns absent from this object are not changed by responsive logic.

This keeps the consumer API compact while still resolving into TanStack's existing `columnVisibility` state:

```txt
{ email: md }
      │
      ▼
table.setColumnVisibility({ email: Boolean(md.value) })
```

Alternative considered: use a nested breakpoint object like `{ base: { email: false }, md: { email: true } }`. That mirrors TanStack visibility state values closely but is more verbose and less natural when the intended behavior is “show this column from this breakpoint upward.”

### Keep responsive visibility separate from initial TanStack visibility

The option should not replace `initialState.columnVisibility`. Initial visibility remains the TanStack-compatible place for static visibility defaults, while responsive visibility overlays only the configured responsive columns.

Alternative considered: overload `columnVisibility` to accept either TanStack state or responsive breakpoint refs. That is terse but ambiguous for consumers and type definitions.

## Risks / Trade-offs

- **Responsive visibility may override user-controlled visibility for the same column** → Treat responsive visibility as authoritative only for configured columns and document that consumers should not also manually toggle those columns unless they intentionally want breakpoint-driven behavior to win.
- **Action object values can become overly dynamic** → Keep the supported resolver surface small and aligned with `ActionMenu`: label, icon, href, callback, disabled, hidden, variant, size, and class.
- **Removing `custom` is a breaking change** → Make the migration explicit in tasks and update all current internal imports/usages together.
- **Breakpoint refs are Vue-specific** → This is acceptable because the custom DataTable is a Vue component and already depends on project Vue composables.

## Migration Plan

- Replace `getActions` props at DataTable call sites with `actions` and `bulkActions` object props.
- Convert row-level action callbacks from `(items) => ...` to `(row) => ...`.
- Convert bulk action callbacks from `(items) => ...` to `(rows) => ...`.
- Replace custom DataTable action renderers with table slots or supported `ActionMenu` action items.
- Add responsive visibility only for columns that should hide below a Tailwind breakpoint.

## Open Questions

- Should responsive column visibility be named `responsiveColumnVisibility`, `columnBreakpoints`, or another prop name that better matches the final component API?
- Should the default row action menu expose a configurable `buttons` count, or should DataTable keep the current row default of two visible buttons before overflow?
