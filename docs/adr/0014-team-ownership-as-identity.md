# ADR 0014: Team Ownership as Identity, Not Role

- **Status:** Accepted
- **Date:** May 2026

## Context

Historically, team ownership was encoded as a Spatie role (`Role::Owner`). Three permissions (`team.update`, `team.delete`, `subscription.update`) were attached exclusively to that role, so policies reached into `model_has_roles` to verify ownership. This tangled identity (who created the team) with capability (what they may do) and violated ADR-0002 by forcing role-name branching inside policy code.

The personal-team rule — "the last team a user owns cannot be deleted" — is the canonical example of why identity matters distinct from a role. It requires identifying *which* teams the user owns, an identity question with no natural expression as a permission check.

## Decision

Record team ownership as a first-class FK column: `teams.owner_id` (non-nullable, `RESTRICT` on delete, references `users.id`). Enforce the three formerly-Owner-only capabilities via identity checks (`$team->owner_id === $user->id`) inside the relevant policies instead of permission checks. Remove `Role::Owner`, `Permission::TeamUpdate`, `Permission::TeamDelete`, and `Permission::SubscriptionUpdate`.

## Alternatives Considered

**Keep `Role::Owner` as-is.**
Rejected: role-name branching in policies violates ADR-0002. Every ownership check must reach `model_has_roles`; the personal-team rule requires an extra role-table join to answer a pure identity question.

**Hybrid: add `owner_id` FK but retain `Role::Owner` in parallel.**
Rejected: two sources of truth for the same fact. Keeping the role requires both columns to stay in sync (e.g., on ownership transfer) and does not remove the ADR-0002 violation from policies, since the role would still be used for permission checks.

## Reasoning

`teams.owner_id` is a fact about who the team was created by — it is identity, not authorization-by-role. The three capabilities guarded by that identity are exclusive to the owner by definition; attaching them to a role layer added indirection without adding flexibility. Moving to a plain FK column eliminates the role-pivot join, removes the ADR-0002 violation, and makes the personal-team rule a direct `ownedTeams()` query.

## Consequences

- `teams.owner_id` is a non-nullable FK to `users.id` with `RESTRICT` on delete. A user cannot be deleted while they still own teams.
- `Role::Owner` is removed. The team creator receives the **Admin** role and is recorded as `owner_id`.
- `Permission::TeamUpdate`, `Permission::TeamDelete`, `Permission::SubscriptionUpdate` are removed from the enum and from the seeder. The permission table no longer contains these three rows.
- `User::ownedTeams()` is a plain `hasMany(Team::class, 'owner_id')` — no role-pivot join.
- `Team::owner()` is a `belongsTo(User::class, 'owner_id')`.
- `HasMembers::owners()` is removed; singular ownership lives on the model, not the trait.
- The personal-team rule ("the last team you own cannot be deleted") is expressed as `$user->ownedTeams()->whereKeyNot($team)->exists()` — a pure identity query.
- `Role::Admin` includes `Permission::TeamView` so the team creator (assigned Admin) can read the settings they can edit. Member also holds `team.view`. `TeamPolicy::view` remains a plain permission check — no identity override needed.

### Amendment to ADR-0002

ADR-0002 prohibits branching on role names inside authorization code. This decision carves out a third authorization primitive alongside permissions and features: **identity-FK checks** (`$resource->owner_id === $user->id`). These are not role-bundle branching — they check a first-class model attribute, not a role table. The prohibition in ADR-0002 targets `hasRole('owner')` style checks; it does not extend to FK comparisons on model columns.

### Relationship to ADR-0001

ADR-0001's implicit-membership rule (a user is a team member if they hold a team-scoped role via `model_has_roles`) still holds for the four remaining roles (`super-admin`, `tester`, `admin`, `member`). Removing `Role::Owner` does not change the membership model — ownership and membership are now distinct facts, each with their own storage. A user may own a team without being a member (ownership transfer use-case, deferred) and a member may not be the owner.
