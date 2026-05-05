# vue-kit

A Laravel + Inertia + Vue starter kit with multi-tenant teams, role-based authorization via Spatie Permission, and i18n.

## Language

**Team**:
A shared workspace. Every authenticated user owns at least one **Team**. The user's *active* team is tracked by `users.current_team_id`; team-scoped pages render against that team without a URL prefix.
_Avoid_: workspace, organization, account.

**Personal Team**:
A **Team** auto-created on signup with the user as **Owner** and a default name from the `team` translation file. Not a separate type — recognized only by the rule "the last team a user owns cannot be deleted."

**Membership**:
The fact that a **User** belongs to a **Team** with a specific team-scoped **Role**. Stored implicitly as a row in Spatie's `model_has_roles` table — there is no separate `team_user` pivot.
_Avoid_: team-user, team membership pivot.

**Role**:
A named bundle of **Permissions**. Either *global* (`team_id = null` — applies regardless of team context) or *team-scoped* (assigned per-team via Spatie's teams feature). Listed in the `RoleName` enum.

**Permission**:
A single capability check expressed in dot notation (`user.view`, `team.update`). The unit on which all authorization decisions are made. Listed in the `PermissionName` enum.
_Avoid_: ability, capability, gate.

## Roles

| Role | Scope | Permissions |
|---|---|---|
| `super-admin` | global | `admin` |
| `tester` | global | `test` |
| `owner` | team | `user.*`, `team.*` |
| `admin` | team | `user.*` |
| `member` | team | `user.viewAny`, `user.view`, `team.view` |

`user.*` covers **Membership** management within the team (invite, view, change role, remove) — not editing of global User profile fields.

`team.*` excludes `team.create`: creating a team has no team context yet, so it is ungated and any authenticated user may create one. The creator becomes the **Owner** of the new team.

## Permissions

```
admin                    # global — gates the admin panel
test                     # global — gates feature-test access
user.viewAny             # team — list members
user.view                # team — view a member
user.create              # team — invite/add a member
user.update              # team — change a member's role
user.delete              # team — remove a member
team.view                # team — view team settings
team.update              # team — edit team settings
team.delete              # team — delete the team
```

## Relationships

- A **User** has many **Memberships**; through memberships, many **Teams**.
- A **Team** has many **Memberships**; through memberships, many **Users**.
- Each **Membership** carries exactly one team-scoped **Role**.
- A **User** may also have any number of *global* **Roles** (Super admin, Tester) — these grant `team_id = null` permissions only.

## Authorization rules

- All authorization checks must use **Permissions**, never **Roles**. Policies call `$user->can('permission.name', ...)`.
- Team context is set per request from `auth()->user()->current_team_id` via middleware that calls `setPermissionsTeamId($team->id)`. Routes that don't apply this middleware (e.g. global account/admin routes) operate with `team_id = null` and only global permissions match.

## Flagged ambiguities

- "Admin" was used to mean both the global Super admin role and a team-scoped role — resolved: the global one is **Super admin** (`super-admin`), the team-scoped one is **Admin** (`admin`).
- "user.create scoped to a team" sounded like creating a User row — resolved: it means creating a **Membership**.
