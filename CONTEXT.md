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
A named bundle of **Permissions**. Either *global* (`team_id = null` — applies regardless of team context) or *team-scoped* (assigned per-team via Spatie's teams feature). Listed in the `App\Enums\Role\Role` enum.

**Permission**:
A single capability check expressed in dot notation (`user.view`, `team.update`). The unit on which all authorization decisions are made. Listed in the `App\Enums\Permission\Permission` enum.
_Avoid_: ability, capability, gate.

**Subscription**:
A Stripe-backed billing relationship between a **Team** and the application, managed via Cashier. The **Team** is the billable entity (not the User) — per ADR-0007 every Team carries the `Billable` trait and `teams.stripe_id` references the Stripe Customer. A Team with no active subscription is on the `Free` **Tier**.
_Avoid_: plan, account.

**Tier**:
The level of access a **Team** has, derived from its **Subscription** state. Listed in the `SubscriptionTier` enum (`Free`, `Pro`) and strictly ordered — `Pro` is a superset of `Free`. `Free` is represented by the absence of an active subscription, not a `$0` Stripe row.

**Feature**:
A tier-gated capability resolved at runtime via Laravel Pennant, scoped to the **Team**. Distinct from a **Permission**: a Permission answers "is this role allowed to do this?", a Feature answers "did this team pay for this?". Per ADR-0008 the two axes are evaluated independently and surface to the frontend as parallel shapes: the `AuthAbilitiesData` DTO (policy results) and the `Team::features` accessor (Pennant feature values). Defined feature names are listed in the `Feature` enum.
_Avoid_: flag, ability.

## Roles

| Role | Scope | Permissions |
|---|---|---|
| `super-admin` | global | `admin` |
| `tester` | global | `test` |
| `owner` | team | `user.*`, `team.*`, `subscription.*` |
| `admin` | team | `user.*`, `subscription.view` |
| `member` | team | `user.viewAny`, `user.view`, `team.view` |

`user.*` covers **Membership** management within the team (invite, view, change role, remove) — not editing of global User profile fields.

`team.*` excludes `team.create`: creating a team has no team context yet, so it is ungated and any authenticated user may create one. The creator becomes the **Owner** of the new team.

`subscription.*` covers viewing and managing the team's **Subscription**. Owner gets both; Admin gets only `subscription.view` (they can read the current plan but cannot rack up charges on the Owner's card); Member gets neither. Deleting a team via `team.delete` cancels the Stripe subscription immediately, not at period end.

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
subscription.view        # team — see current Subscription, invoices, upcoming charge
subscription.update      # team — start/swap/cancel Subscription, manage payment method
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
