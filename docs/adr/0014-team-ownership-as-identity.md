# Team ownership as identity, not role

Ownership of a Team is recorded as `teams.owner_id` (FK → `users.id`) rather than as a Spatie role assignment. The creator's `id` is written to `owner_id` at team creation, and the three formerly Owner-only capabilities (`team.update`, `team.delete`, `subscription.update`) are enforced by identity checks (`$team->owner_id === $user->id`) inside the relevant policies instead of permission checks.

## Why

Encoding ownership as a role produced three frictions:

1. **Role-name branching in policies.** `TeamPolicy::delete` reached into `model_has_roles` to find Owner-role rows. ADR-0002 forbids role-shaped reasoning inside policies.
2. **Bookkeeping permissions.** `team.update`, `team.delete`, and `subscription.update` were held by Owner and nothing else — they did not partition the role-permission matrix, they only labelled the Owner row.
3. **Tangled membership and ownership.** "Owner" was simultaneously an identity fact and a capability bundle. Every ownership check had to reach the role tables.

## Consequences

- `teams.owner_id` is a non-nullable FK to `users.id` with `RESTRICT` on delete. A user cannot be deleted while they still own teams.
- `Role::Owner` is removed. The team creator receives the **Admin** role and is recorded as `owner_id`.
- `Permission::TeamUpdate`, `Permission::TeamDelete`, `Permission::SubscriptionUpdate` are removed from the enum and from the seeder. The permission table no longer contains these three rows.
- `User::ownedTeams()` is a plain `hasMany(Team::class, 'owner_id')` — no role-pivot join.
- `Team::owner()` is a `belongsTo(User::class, 'owner_id')`.
- `HasMembers::owners()` is removed; singular ownership lives on the model, not the trait.
- The personal-team invariant ("the last team you own cannot be deleted") is expressed as `$user->ownedTeams()->whereKeyNot($team)->exists()` — a pure identity query.

## Relationship to ADR-0002

ADR-0002 prohibits branching on role names inside authorization code. This decision does not violate that rule: `$team->owner_id === $user->id` is an identity-FK check, not a role-name check. The prohibition in ADR-0002 targets role-bundle branching (e.g., `hasRole('owner')`) — it does not extend to first-class model attributes that express identity.

## Ownership transfer

The schema enables transfer with a single column update (`teams.owner_id = new_owner_id`). The UX and policy for who may initiate a transfer are deferred to a later PRD.
