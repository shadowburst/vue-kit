## Purpose

Define DataTable filter submission, persistence, active-state, and reset behavior.

## Requirements

### Requirement: URL-backed filter submission

The system SHALL submit filter data as URL query parameters through an Inertia visit while omitting empty values from the submitted query.

#### Scenario: Empty values are omitted from submitted query data

- **WHEN** a filter form contains `undefined`, blank string, or empty array values
- **THEN** the submitted query data excludes those values

#### Scenario: Non-empty values are included in submitted query data

- **WHEN** a filter form contains non-empty values
- **THEN** the submitted query data includes those values under their filter keys

### Requirement: Remembered filter state

The system SHALL remember filter state per URL path and restore remembered values when the same filter composable is initialized again.

#### Scenario: Filters restore from session storage

- **WHEN** remembered filter data exists for the current URL path
- **THEN** the filter form initializes with the remembered values

#### Scenario: Reset URL parameter clears remembered filters

- **WHEN** the URL contains `reset_filters`
- **THEN** remembered filter data for the current URL path is cleared before restoring form values

### Requirement: DataTable table chrome fields

The system SHALL treat `q`, `page`, `per_page`, `sort_by`, and `sort_direction` as DataTable table chrome fields rather than custom filter fields.

#### Scenario: Search query is not an active custom filter

- **WHEN** only `q` has a non-empty value
- **THEN** the DataTable filters report no active custom filters

#### Scenario: Pagination and sorting are not active custom filters

- **WHEN** only `page`, `per_page`, `sort_by`, or `sort_direction` have non-empty values
- **THEN** the DataTable filters report no active custom filters

### Requirement: Active custom filter detection

The system SHALL expose DataTable active-filter detection that returns true only when at least one non-empty custom filter field is present.

#### Scenario: Custom filter is active

- **WHEN** a non-table-chrome custom filter field has a non-empty value
- **THEN** the DataTable filters report active custom filters

#### Scenario: Empty custom filters are inactive

- **WHEN** all custom filter fields are `undefined`, blank strings, or empty arrays
- **THEN** the DataTable filters report no active custom filters

### Requirement: Custom filter reset

The system SHALL expose a DataTable reset helper that clears custom filter fields while preserving table chrome fields.

#### Scenario: Reset preserves search pagination and sorting

- **WHEN** the DataTable filter reset helper is called
- **THEN** `q`, `page`, `per_page`, `sort_by`, and `sort_direction` keep their current values

#### Scenario: Reset clears custom filters

- **WHEN** the DataTable filter reset helper is called
- **THEN** non-table-chrome custom filter fields are reset to their default empty values

### Requirement: Inertia form compatibility

The system SHALL preserve Inertia form behavior for core form properties and methods while adding DataTable-specific filter helpers.

#### Scenario: Native dirty state remains available

- **WHEN** a consumer reads the form's native dirty state
- **THEN** it reflects Inertia form data differences from defaults rather than DataTable active-filter status

#### Scenario: Native reset remains available

- **WHEN** a consumer calls the form's native reset method
- **THEN** it behaves consistently with Inertia `useForm()` reset semantics
