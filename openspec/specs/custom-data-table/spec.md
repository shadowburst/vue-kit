## Purpose

Define the custom DataTable action and responsive visibility behavior.

## Requirements

### Requirement: Declarative row actions

The custom DataTable SHALL accept row actions as an object keyed by stable action ids. Each row action SHALL resolve against the current row and render through the shared ActionMenu.

#### Scenario: Rendering row actions for a table row

- **WHEN** a DataTable receives a row action object and renders a row action cell
- **THEN** the row action cell renders an ActionMenu containing the resolved actions for that row

#### Scenario: Resolving row action hrefs and callbacks

- **WHEN** a row action defines an href or callback as a function of the row
- **THEN** the DataTable resolves that function with the row original before passing the action to ActionMenu

#### Scenario: Hiding row actions

- **WHEN** a row action resolves `hidden` to true for a row
- **THEN** the DataTable excludes that action from the row's ActionMenu

### Requirement: Declarative bulk actions

The custom DataTable SHALL accept bulk actions as an object keyed by stable action ids. Each bulk action SHALL resolve against the currently selected row originals and render through the shared ActionMenu for selected rows.

#### Scenario: Rendering bulk actions for selected rows

- **WHEN** one or more rows are selected and bulk actions are configured
- **THEN** the DataTable renders an ActionMenu containing the resolved bulk actions for the selected row originals

#### Scenario: Resolving bulk action callbacks

- **WHEN** a bulk action defines a callback as a function of selected rows
- **THEN** the DataTable passes the selected row originals to the callback

#### Scenario: Hiding bulk actions

- **WHEN** a bulk action resolves `hidden` to true for the selected row originals
- **THEN** the DataTable excludes that action from the bulk ActionMenu

### Requirement: ActionMenu compatibility

The custom DataTable SHALL adapt row and bulk action definitions into the existing ActionMenu item shape. DataTable actions SHALL support labels, icons, hrefs, callbacks, disabled state, hidden state, variants, sizes, and classes when those fields are supported by ActionMenu.

#### Scenario: Rendering actions without custom DataTable components

- **WHEN** a DataTable action is visible for a row or selected rows
- **THEN** the action is rendered by ActionMenu rather than by a DataTable-specific custom action renderer

#### Scenario: Resolving static and dynamic action values

- **WHEN** an action field is provided as a static value
- **THEN** the DataTable passes that value through to ActionMenu
- **WHEN** an action field is provided as a resolver function
- **THEN** the DataTable resolves the function with the current row or selected rows before passing the value to ActionMenu

### Requirement: Responsive column visibility

The custom DataTable SHALL support responsive column visibility as a map of column ids to Tailwind breakpoint refs. A configured column SHALL be visible when its breakpoint ref is truthy and hidden when its breakpoint ref is falsy.

#### Scenario: Hiding a column below its breakpoint

- **WHEN** responsive column visibility is configured as `{ email: md }` and `md` is false
- **THEN** the DataTable sets TanStack column visibility for `email` to false

#### Scenario: Showing a column at its breakpoint

- **WHEN** responsive column visibility is configured as `{ email: md }` and `md` is true
- **THEN** the DataTable sets TanStack column visibility for `email` to true

#### Scenario: Leaving unconfigured columns untouched

- **WHEN** a column id is absent from the responsive column visibility object
- **THEN** the DataTable does not change that column's visibility based on breakpoint state

#### Scenario: Preserving TanStack visibility semantics

- **WHEN** responsive breakpoint state changes
- **THEN** the DataTable updates TanStack column visibility state rather than maintaining a separate hidden-column model
