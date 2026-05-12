## ADDED Requirements

### Requirement: Canonical Domain Vocabulary
The project SHALL define and use the domain terms Team, Personal Team, Membership, Role, Permission, Subscription, Tier, Feature, Operator panel, Impersonation, Activity Log, and Over-cap with the meanings migrated from `CONTEXT.md`.

Team means a shared workspace owned by a User and rendered through `users.current_team_id`, not a workspace, organization, or account. Personal Team means an ordinary auto-created Team whose owner is the signup User and whose default name comes from the `team` translation file. Membership means a User belongs to a Team with exactly one team-scoped Role through Spatie Permission's `model_has_roles`, with no `team_user` pivot. Role means a global or team-scoped named bundle of Permissions. Permission means the dot-notation authorization unit. Subscription means the Stripe/Cashier billing relationship for a Team. Tier means the Team access level derived from Subscription state, where Free is absence of active Subscription and Pro is a superset of Free. Feature means a Team-scoped Pennant value distinct from Permission. Operator panel means the Filament UI at `/admin`. Impersonation means an Operator assumes a target User's full session-derived state. Activity Log means Operator audit history recorded through Spatie Activity Log under `log_name = 'admin'`. Over-cap means non-owner Membership count exceeds the Team's Tier member cap.

#### Scenario: Domain language is needed for a change
- **WHEN** a future change introduces or modifies product behavior involving teams, billing, authorization, operators, impersonation, or audit history
- **THEN** the change uses the OpenSpec `domain-knowledge` terms instead of legacy or ambiguous terms such as workspace, organization, account, ability, capability, flag, sudo, login-as, admin panel, back office, ops dashboard, audit log, locked, frozen, suspended, or read-only-team

### Requirement: Team and Membership Semantics
A Team SHALL be the shared workspace for authenticated users, the user's active Team SHALL be tracked by `users.current_team_id`, and a Membership SHALL be represented by a team-scoped Role assignment in Spatie Permission's `model_has_roles` table with no separate `team_user` pivot.

A User has many Memberships and, through them, many Teams. A Team has many Memberships and, through them, many Users. Each Membership carries exactly one team-scoped Role. A User may also hold global Roles with `team_id = null`; those grant only global Permissions.

#### Scenario: Team-scoped behavior is evaluated
- **WHEN** a request renders or mutates team-scoped state
- **THEN** the request resolves against the authenticated user's active Team and treats team membership as the presence of exactly one team-scoped Role for that Team

### Requirement: Personal Team Semantics
A Personal Team SHALL be an ordinary Team auto-created on signup with the user as `owner_id` and a default name from the `team` translation file; it SHALL NOT be modeled as a separate Team type.

#### Scenario: Personal Team rules are applied
- **WHEN** the system evaluates whether a user's owned Team can be deleted
- **THEN** it enforces the rule that the last Team a user owns cannot be deleted without relying on a separate Personal Team type

### Requirement: Roles and Permissions Catalog
The project SHALL preserve the canonical Role and Permission catalog from `CONTEXT.md`: global `admin` grants `admin`, global `tester` grants `test`, team-scoped `manager` grants `user.*`, `team.view`, and `subscription.view`, and team-scoped `member` grants `user.viewAny`, `user.view`, and `team.view`.

The canonical Permission list is `admin`, `test`, `user.viewAny`, `user.view`, `user.create`, `user.update`, `user.delete`, `team.view`, and `subscription.view`. `user.*` means Membership management within a Team, including invite, view, change role, and remove; it does not mean editing global User profile fields. `team.create` is ungated for authenticated Users. The Team creator is recorded as `teams.owner_id` and assigned the team-scoped `manager` Role. Owners are always also Managers, and non-owner Managers may exist when an owner promotes a Member. `subscription.view` lets Managers see the current plan and invoices.

#### Scenario: Role permissions are seeded or reviewed
- **WHEN** Role or Permission seed data is created, updated, or reviewed
- **THEN** it matches the canonical Role table and the Permission list migrated into OpenSpec

### Requirement: Owner-Only Capabilities
The project SHALL treat `team.update`, `team.delete`, and subscription management including `subscription.update` as owner-only capabilities enforced through `teams.owner_id` identity checks, not as Permissions.

#### Scenario: Owner-only behavior is authorized
- **WHEN** code checks whether a user may update or delete a Team or manage a Subscription
- **THEN** it compares the Team owner identity to the user identity instead of checking a Permission or Role for those capabilities

### Requirement: Authorization Rules
All authorization checks SHALL use Permissions rather than Roles, ownership SHALL be checked through `teams.owner_id`, and team-scoped Permission checks SHALL run after middleware sets Spatie's permission team id from `auth()->user()->current_team_id`.

Routes without current-Team middleware, including global account and Operator routes, operate with `team_id = null` and only global Permissions match. Inviting a Member is gated by both `user.create` and the Team's seat cap exposed through `Feature::TeamMemberCap`.

#### Scenario: Authorization code is introduced
- **WHEN** a policy, gate, middleware, controller, or template needs an authorization decision
- **THEN** it calls a Permission check or an explicit owner identity check and does not branch on Role names

### Requirement: Billing and Feature Semantics
The Team SHALL be the Cashier billable entity, the absence of an active Subscription SHALL mean the Team is on the Free Tier, Tier-gated capabilities SHALL be resolved as Pennant Features scoped to the Team, and Features SHALL remain distinct from Permissions.

Tier member caps are non-owner Membership caps configured at `tiers.{tier}.member_cap` in `config/billing.php`; Free allows 0 and Pro allows 3. The cap is surfaced as integer-valued `Feature::TeamMemberCap`. Feature values surface separately from `auth.abilities` so frontend UX can distinguish role denial from upgrade prompts.

#### Scenario: Tier-gated behavior is evaluated
- **WHEN** a capability depends on both a user's role-scoped authorization and the Team's paid Tier
- **THEN** the policy may combine Permission and Feature results while the frontend can distinguish `auth.abilities` from Team Feature values

### Requirement: Over-cap Semantics
Over-cap SHALL mean a Team's non-owner Membership count exceeds its Tier member cap, voluntary subscription transitions SHALL NOT move a Team into Over-cap, and involuntary cancellation SHALL preserve Memberships while blocking team-management writes except cap-reducing removal.

#### Scenario: Team becomes over cap through payment failure
- **WHEN** a Team enters Over-cap because an involuntary cancellation drops it below its current non-owner Membership count
- **THEN** the Team preserves Memberships, blocks non-recovery writes, allows member removal, and recovers by reducing members or upgrading to a Tier with sufficient cap

### Requirement: Operator, Impersonation, and Activity Log Semantics
The Operator panel SHALL be the Filament UI at `/admin` for application staff, Impersonation SHALL switch an Operator into a target User's full session-derived state with mutation guards, and the Activity Log SHALL record Operator-initiated events through Spatie Activity Log under `log_name = 'admin'`.

The Operator panel SHALL share the Fortify session and be gated by the global `admin` Permission with no separate `/admin/login`. Impersonation SHALL stash the original Operator identity in the session, render a localized leave banner, refuse admin-on-admin impersonation, and refuse password, email, Subscription, and account deletion mutations while impersonating. Activity Log entries SHALL include impersonation start and stop, admin role grants and revokes, Subscription mutations, and Operator soft-delete actions, and SHALL NOT be surfaced to the impersonated User.

#### Scenario: Operator support action is designed
- **WHEN** an Operator panel action, Impersonation flow, or Activity Log entry is added or changed
- **THEN** it follows the OpenSpec meanings for Operator panel, Impersonation, and Activity Log and does not expose operator-internal audit history to impersonated Users

### Requirement: Resolved Ambiguities
The project SHALL preserve the resolved ambiguity that global `admin` and team-scoped `manager` are distinct, and that `user.create` in team scope means creating a Membership rather than creating a global User row.

#### Scenario: Ambiguous authorization terminology appears
- **WHEN** prose, code, tests, or UI copy refer to admin-like roles or user creation within a Team
- **THEN** the language distinguishes global Admin from team Manager and treats team-scoped `user.create` as Membership creation
