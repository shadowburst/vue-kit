# ADR 0015: Team-Scoped Role Named `manager`, Not `admin`

- **Status:** Accepted
- **Date:** May 2026

> **Note (May 2026):** Following this rename, the global role was further simplified from `super-admin` back to `admin`. The `super-` prefix only existed to disambiguate from the team-scoped `admin`; once the team-scoped role became `manager`, the prefix carried no signal. The role-name/permission-name overlap with `Permission::Admin` is accepted: they live in separate Spatie tables and are disambiguated by the API surface (`hasRole` vs `can`).

## Context

The role enum distinguishes a global role (gates the admin panel via `Permission::Admin`) from a team-scoped role that delegates member-management permissions (`user.*`, `team.view`, `subscription.view`). An earlier draft used the word "admin" for both; a transitional rename pushed the global role to `super-admin` to disambiguate.

That disambiguation does not hold in practice:

1. **Verbal collision.** `admin` and `super-admin` differ by one prefix. Sentences such as "admins cannot do X but super-admins can" read as typos. In code review, IDE search, and documentation, `admin` matches `super-admin` and vice versa.
2. **Semantic dilution.** Per ADR-0014, every team creator is recorded as `teams.owner_id` and additionally assigned the team-scoped role on team creation. Because every signed-up user owns at least one **Personal Team**, every user holds the team-scoped role somewhere. The word "admin" stops carrying signal — it just means "authenticated user with a team."

## Decision

Rename the team-scoped role from `admin` to `manager`. The enum case becomes `Role::Manager`, the persisted name becomes `'manager'`, and the translation key becomes `roles.manager`. The global role retains `Role::Admin` (`'admin'`) and the `Permission::Admin` permission is unchanged.

The role's permission bundle (`user.*`, `team.view`, `subscription.view`) does not change. Owner auto-assignment on team creation does not change — the team creator continues to receive the team-scoped role in addition to being recorded as `owner_id`.

## Alternatives Considered

**Keep `admin`.**
Rejected: the verbal collision with `super-admin` is real and the dilution is real. Disambiguation by prefix did not survive contact with prose.

**Rename to `maintainer` (GitHub-style).**
Rejected: "maintainer" connotes ongoing upkeep of an artifact (a repository, a package). Teams here have no artifact to maintain; the role manages people, not code.

**Rename to `editor`.**
Rejected: "editor" implies content-editing, which is not what the role does. It manages **Memberships** (invite, change role, remove), not content.

**Stop auto-assigning the role to owners (Design B).**
Deferred. Making `manager` a genuinely optional, owner-delegated role would better align with ADR-0014's "ownership is identity, not role" — but it requires every member-management policy to gain an `owner_id` short-circuit and re-touches a dozen+ test fixtures. Out of scope of this rename. Tracked as a follow-up question.

## Reasoning

"Manager" names what the role does — manage memberships within a team. It distinguishes cleanly along the four authorization primitives now in play:

- **Admin** — global role, app-wide capability (gates the admin panel via `Permission::Admin`)
- **Owner** — identity (per ADR-0014), per-team
- **Manager** — team-scoped role, delegated by owner for people-management
- **Member** — team-scoped role, default

`manager` does not appear elsewhere in the vocabulary, so it has no collision surface. The shared string `admin` between `Role::Admin` and `Permission::Admin` is accepted: they are orthogonal concepts evaluated through different methods and stored in different tables.

## Consequences

- The team-scoped enum case becomes `Role::Manager`; persisted role name becomes `'manager'`.
- The global role keeps the enum case `Role::Admin` and persisted name `'admin'`. `Permission::Admin` is unchanged.
- `lang/{en,fr}/roles.php` gains a `manager` key; the `admin` key is removed.
- Team creator continues to receive the team-scoped role on team creation (now `manager`). The semantic dilution on personal teams is reduced — "Manager" is more job-descriptive than "Admin" — but not eliminated. See deferred Design B.
- Pre-production rollout per project convention: migration files are not added; `RolePermissionSeeder` runs over the renamed enum and `migrate:fresh --seed` produces the correct state.
- Test fixtures across `tests/Feature/**` that reference the role string `'admin'` or `Role::Admin` are updated.
- CONTEXT.md role table and the flagged-ambiguities entry are updated to record the resolution.

## Relationship to ADR-0014

ADR-0014 established that ownership is identity (`teams.owner_id`), not a role. This ADR does not change that. Owners continue to be identified by the FK column and continue to additionally receive the team-scoped role on team creation. Removing that auto-assignment — to make `manager` purely opt-in — is a separate decision deferred from this ADR.
