## ADDED Requirements

### Requirement: Simplified ADR Migration
The OpenSpec architecture-decision capability SHALL distill legacy ADRs from `docs/adr/` into current architectural rules, collapsing duplicate numbers, superseded chains, and obsolete alternatives into a cleaner spec.

The legacy ADR inventory SHALL be represented by active decision area rather than file order: ADR-0001 through ADR-0009; duplicate ADR-0010 entries for form wrappers and frontend types; ADR-0011 through ADR-0013; duplicate ADR-0014 entries for superseded confirm dialog stacking and active team ownership identity; ADR-0015 and ADR-0016; duplicate ADR-0017 entries for active dialog depth and active Spatie Data serialization; ADR-0018 through ADR-0024. ADR-0013 is superseded by ADR-0016, and ADR-0014 confirm-dialog stacking is superseded by ADR-0017 dialog depth.

#### Scenario: Legacy ADR is migrated
- **WHEN** an accepted or superseded legacy ADR is represented in OpenSpec
- **THEN** the migrated requirement captures the active rule, references the ADR number plus slug or title where useful, and omits historical prose that no longer guides implementation

### Requirement: Membership and Authorization Architecture
The project SHALL preserve the accepted architecture decisions for implicit Membership via Spatie `model_has_roles` (ADR-0001), Permission-based authorization (ADR-0002), current-Team request scoping via `users.current_team_id` (ADR-0003), Team ownership as identity (ADR-0014 team ownership), and team-scoped `manager` naming (ADR-0015).

Membership storage SHALL have one source of truth in `model_has_roles`; active Team switching SHALL use `PUT /current-team`; owner-only checks SHALL compare `teams.owner_id` to the acting User; Role names SHALL be assignment and seeding data only; and the team-scoped people-management Role SHALL be persisted as `manager`, while the global Role and Permission strings may both remain `admin` because they live in different Spatie tables.

#### Scenario: Authorization architecture is changed
- **WHEN** a future implementation touches Membership storage, active Team resolution, Role names, Permission checks, or owner-only policy logic
- **THEN** it keeps Membership implicit, avoids Role-name branching in authorization, scopes team requests through `current_team_id`, checks ownership through `teams.owner_id`, and uses `manager` for the team-scoped people-management Role

### Requirement: Billing, Tier, and Over-cap Architecture
The project SHALL preserve the accepted billing and tier decisions that the Team is the Cashier billable (ADR-0007), Tier-gated capabilities use Pennant with Permissions as a separate axis (ADR-0008), destructive member-cap pruning on downgrade is superseded (ADR-0013), and voluntary over-cap downgrade is blocked while involuntary cancellation becomes read-only recovery (ADR-0016).

The Team model SHALL carry Cashier `Billable`, `teams.stripe_id` SHALL reference the Stripe Customer lazily created at checkout, subscription name SHALL remain `default`, Free SHALL be absence of active Subscription, and pricing SHALL remain flat-fee per Team. Pennant SHALL use the database driver scoped to Team, subscription webhooks SHALL purge Team Feature values, policies MAY combine Permissions and Features where both axes apply, and `Team::isOverCap()` SHALL be the single predicate for read-only recovery. Stripe Portal cancellation SHALL remain disabled so voluntary cancellation can be enforced by the application controller.

#### Scenario: Subscription transition logic is changed
- **WHEN** a future change modifies checkout, subscription cancellation, resubscribe, tier swap, member caps, Pennant Feature resolution, or Stripe webhook handling
- **THEN** it keeps billing Team-scoped, treats Free as absence of active Subscription, purges tier-derived Feature values when subscription state changes, blocks voluntary over-cap transitions, preserves Memberships on involuntary cancellation, and uses read-only recovery for Over-cap Teams

### Requirement: PHP Architecture and Testing Conventions
The project SHALL preserve the Pest architecture conventions (ADR-0004), FormRequest-to-Spatie-Data validation direction as superseded by later Data decisions (ADR-0005 and ADR-0017 Spatie Data), Laravel resource controller method conventions (ADR-0006), domain-noun file grouping (ADR-0009), Feature test base conventions (ADR-0011), request-scoped resolver services under `app/Services/` (ADR-0012), string max length enum tiers (ADR-0018), and inline translated validation attribute maps on Data classes (ADR-0019).

Architecture tests SHALL preserve the dependency seams, Action class shape, Data class shape, final-modifier scope, and enum-shape rules from ADR-0004. Controllers SHALL use Laravel resource method names and private helpers, not `__invoke` or protected helpers. Source files SHALL group by singular domain noun, integration boundary, or cohesive feature with no generic shared bucket and with structural enforcement by Pest. Feature tests SHALL use `LazilyRefreshDatabase`, auto-seed `RolePermissionSeeder`, and flush Spatie Permission cache before enum model caches. Request-scoped ambient state SHALL live in typed services under `app/Services/{Noun}/`. String validation `max:` values in Data request classes SHALL use `StringMaxLength` tiers. Data input classes SHALL inline literal translated `attributes()` maps with parity enforced by architecture tests.

#### Scenario: PHP structure or validation is changed
- **WHEN** a future change adds or modifies PHP classes, controllers, Actions, Data classes, validation rules, tests, request-scoped context, or source layout
- **THEN** it follows the existing Pest-enforced architecture shape, resource-method controller names, domain-noun subgrouping, Feature test cache and seeding rules, typed request-scoped services, enum-backed string length tiers, and per-class translated attribute maps

### Requirement: Frontend Boundary and UI Architecture
The project SHALL preserve the decisions for custom form wrappers over shadcn field primitives (ADR-0010 form wrapper), frontend route helpers and enum values from Wayfinder where applicable (ADR-0010 frontend types), backend Inertia/JS shapes through Spatie Data (ADR-0017 Spatie Data), ConfirmDialog stacking superseded by dialog-depth module counter (ADR-0014 confirm dialog and ADR-0017 dialog depth), and translated data attributes (ADR-0019).

Consumers SHALL import form field components from `@/components/ui/custom/form`; the wrapper layer owns label, control, description, error, required, disabled, invalid, and `aria-describedby` wiring. Backend-shaped frontend types SHALL come from generated backend contracts, while purely client-side types may remain hand-written. Frontend enum runtime values SHALL be imported from Wayfinder modules and not passed as Inertia props. Dialog stacking SHALL be handled internally by `SmartDialogContent` through a module-level depth counter and inline z-index style, with LIFO close order as the contract.

#### Scenario: Frontend form, route, type, or dialog behavior is changed
- **WHEN** a future change touches Vue forms, backend-shaped TypeScript types, generated route helpers, enum imports, Inertia page props, shared data, or nested dialogs
- **THEN** it uses the custom form primitives, keeps backend-shaped types in generated Spatie Data namespaces, uses Wayfinder for route helpers and runtime enum values, and relies on `SmartDialogContent` depth handling rather than hard-coded nested dialog z-index overrides

### Requirement: Spatie Data Serialization Architecture
The project SHALL preserve the ADR-0017 Spatie Data decision that backend-shaped data crossing the Inertia/JS boundary is represented by Spatie Data or Resource classes, with Resources for output, Data for request/input shapes, Props classes per Inertia page, SharedData for shared props, and Wayfinder scoped to route helpers, enum runtime values, and environment variables.

The project SHALL not use `JsonResource` as the source of frontend contracts. Data classes SHALL live under `app/Data/{Noun}/` with suffixes `{Noun}Resource`, `{Noun}{Verb}Request`, `{Noun}{PageName}Props`, and `{Noun}{Purpose}Data`. Resource classes SHALL declare database columns as required typed properties and conditional relationships or computed fields as `Lazy`. Page Props SHALL be explicit Spatie Resource classes. Shared Inertia props SHALL be `App\Data\Shared\SharedData`. Lists SHALL use `PaginatedDataCollection` for unbounded data and `DataCollection` for bounded sets. `Model::preventLazyLoading(! app()->isProduction())` SHALL remain enabled, and feature tests SHALL assert received Inertia shapes instead of standalone resource unit tests.

#### Scenario: Backend data crosses the frontend boundary
- **WHEN** a controller, middleware, or API endpoint sends backend-shaped data to Vue or JSON consumers
- **THEN** it uses named Spatie Data or Resource classes that generate stable TypeScript types and avoids hand-rolled frontend types or anonymous backend-shaped payloads as the source of truth

### Requirement: Operator Panel, Deletion, Impersonation, and Audit Architecture
The project SHALL preserve the Operator panel and support decisions: Filament shares the Fortify session and is gated by the global `admin` Permission (ADR-0020), user-facing data soft-deletes by default with explicit admin deletion preconditions (ADR-0021), Filament Resource coverage is enforced by Pest schema tests (ADR-0022), Impersonation is full-state with admin-on-admin refusal and mutation guards (ADR-0023), and operator audit history uses Spatie Activity Log under `log_name = 'admin'` (ADR-0024).

Operator panel access SHALL use `User::canAccessPanel()` with a Permission check and no separate panel login. User-facing Eloquent models SHALL soft-delete by default; deleting a User that owns Teams SHALL require ownership transfer first; deleting a Team SHALL cancel active Stripe subscriptions, null matching `users.current_team_id`, and then soft-delete; force-delete SHALL require no blocking dependencies. Filament coverage SHALL be enforced by tests for Resource existence and schema column coverage with justified allow-lists. Impersonation SHALL reproduce target user, current Team, Permissions, and Features, while refusing protected mutations and admin-on-admin targets. Activity Log SHALL use stable machine-readable descriptions and a read-only Filament Activity Resource scoped to `admin`.

#### Scenario: Operator functionality is changed
- **WHEN** a future change touches Filament resources, admin access, deletion and restoration, Impersonation, support subscription mutations, admin role grants, or operator audit history
- **THEN** it keeps the shared Fortify session, Permission-gated Operator panel access, soft-delete and force-delete preconditions, Filament coverage tests, full-state Impersonation semantics, mutation guards, and Activity Log entries under `admin`

### Requirement: Superseded ADR Handling
Superseded legacy ADRs SHALL be collapsed into their active replacement decisions instead of being preserved as standalone normative requirements.

#### Scenario: Superseded decision is encountered
- **WHEN** a future maintainer reads an OpenSpec requirement migrated from ADR-0013 or ADR-0014 confirm dialog stacking
- **THEN** the requirement points to the active replacement decision from ADR-0016 or ADR-0017 dialog depth and omits the superseded behavior except as brief context when needed

### Requirement: Legacy Documentation Removal
After OpenSpec specs contain the cleaned domain and architecture rules, the implementation SHALL remove `CONTEXT.md` and `docs/adr/` so OpenSpec is the single documentation source of truth.

#### Scenario: Migration is complete
- **WHEN** the migrated OpenSpec specs have been reviewed against `CONTEXT.md` and `docs/adr/`
- **THEN** `CONTEXT.md` and `docs/adr/` are removed from the repository in the same implementation change

### Requirement: No Runtime Behavioral Change During Documentation Migration
The migration from `CONTEXT.md` and `docs/adr/` to OpenSpec SHALL NOT change application behavior, schema, dependencies, routes, tests, or runtime configuration by itself.

#### Scenario: Migration implementation is reviewed
- **WHEN** the documentation migration change is reviewed
- **THEN** reviewers can verify that source material was converted into OpenSpec specs without production code, dependency, database, or runtime behavior changes
