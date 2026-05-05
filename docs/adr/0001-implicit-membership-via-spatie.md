# Implicit team membership via Spatie's `model_has_roles`

We use Spatie Permission's `model_has_roles` table as the single source of truth for team membership: a user is a member of a team if (and only if) they have at least one team-scoped role assigned with that `team_id`. We do not maintain a separate `team_user` pivot.

## Considered options

- **Explicit `team_user` pivot** with role assignments layered on top. Rejected because it creates two sources of truth that must be kept in sync (member without role, role without membership) and we have no current need for membership metadata (`joined_at`, `invited_by`, status).
- **Implicit via `model_has_roles`** (chosen). One source of truth; the rule "every team member has exactly one team-scoped role" is enforced in `CreateTeam` and the invitation flow.

## Consequences

- `User::teams()` and `Team::members()` go through `model_has_roles` rather than a dedicated pivot.
- If we later need membership metadata, we'll add a `team_user` pivot then and backfill from `model_has_roles`. Adding it preemptively pays the synchronization tax for nothing.
- Removing a member is a single `removeRole(...)` call scoped to the team, not a pivot detach.
