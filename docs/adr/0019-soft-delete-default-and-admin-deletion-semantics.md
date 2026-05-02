---
status: accepted
---

# Soft-delete by default for user-facing data; admin deletion requires ownership-transfer first

Every Eloquent model that represents user-facing data uses `SoftDeletes`. The Operator panel's "Delete" action is a soft delete; "Force delete" is a separate, confirmed action. Deleting a User who owns one or more Teams is refused — the operator must use the panel's "Change owner" action on each owned Team first. Deleting a Team cancels any active Stripe subscription via `Cashier::cancelNow()`, nullifies `users.current_team_id` for any user pointed at it, and only then soft-deletes the row. Force-delete on a Team requires zero memberships and no active subscription. Force-delete on a User requires zero owned teams and zero memberships.

## Considered options

- **Hard delete with cascade** — deleting a User cascades to teams they own, which cascades to memberships. Rejected. Cascading user-delete to teams violates ADR-0014's "ownership as identity" principle by hiding the ownership transfer behind a delete button. Memberships of *other* users disappear as collateral. Once gone, gone — Stripe customer history is also severed.
- **Hard delete with refusal on owned-teams** (no soft-delete; just refuse). Rejected as the default. Refusal is correct for the User-with-owned-teams case, but applying hard-delete elsewhere (Teams without members, soft state like Activity Log entries) loses the operational reversibility that real support work needs. Operators delete spam, mistakes, and edge cases — most of those are eventually "actually, restore that" requests.
- **Soft delete with cascade-on-soft-delete** — soft-deleting a User soft-deletes their owned Teams. Rejected. Cascading even softly conflates "this user is gone" with "their teams are gone," which is a product decision that should be explicit. Ownership-transfer makes the operator state the destination owner deliberately.
- **Soft delete + ownership-transfer precondition** (chosen). One default everywhere, with explicit preconditions for the cases that have invariants attached.

## Why

The product-side invariant "the last team a user owns cannot be deleted" exists because a Team must always have an owner (`teams.owner_id` is NOT NULL, and ADR-0014 makes ownership an identity check). The Operator panel can override the *user-facing* version of that rule, but it cannot violate the underlying invariant. Forcing ownership-transfer first turns "delete a user who owns teams" from an undefined cascade into a sequence of explicit operator decisions, each of which is loggable in the Activity Log.

Soft delete as the project-wide default does three things at once. It makes operator deletes recoverable without a database restore. It preserves Stripe customer references so refund/audit history survives. And it gives Filament Resources a uniform shape — the "Trashed" filter, the "Restore" action, and the "Force delete" guard all come for free from `SoftDeletes` + Filament's soft-delete-aware Resource base.

Force-delete is kept as a real action rather than removed entirely because compliance requests (GDPR erasure, legal hold release) are real and cannot be served by soft delete alone. Gating it behind preconditions (zero memberships, zero owned teams, no active subscription) means the operator cannot hard-delete into an invariant violation by accident.

## Consequences

- New migrations add `deleted_at` (nullable, indexed) to `users` and `teams`. Existing migrations are edited in place if pre-prod; otherwise a new migration is added.
- `User` and `Team` add `use SoftDeletes;` and the `$dates`/`casts` adjustments for `deleted_at`.
- Auth short-circuits on soft-deleted users. Fortify's user provider already excludes soft-deleted rows by default — verify in the test suite. A soft-deleted User cannot log in.
- `users.current_team_id` is set to `null` on Team soft-delete via the existing `TeamObserver` (which gains a `deleting` hook). The user's next request triggers their existing "no current team" flow.
- `TeamPolicy::delete` is unchanged at the product level — the user-facing "the last team a user owns cannot be deleted" rule still applies. The Operator panel calls a separate path (a Filament Action class under `App\Actions\Admin\`, per ADR-0009) that bypasses the policy with explicit operator-context authorization.
- A first-class **"Change owner"** action on the Team Resource is the prerequisite for `User::delete` when the user owns teams. The action enumerates eligible Memberships of the team and reassigns `teams.owner_id`. It does *not* automatically grant the `manager` role to the new owner — Owners are always also Managers, so a separate role-assignment step happens in the same action.
- Force-delete is implemented as a separate Filament Action with a confirmation modal that lists what is irrevocable. Available only when the soft-deleted record has zero blocking dependencies.
- Restore is the inverse of soft-delete. Restoring a Team does *not* restore its subscription — the operator must explicitly create a new Cashier subscription if needed. Restoring a User restores their memberships only if the membership rows themselves were not separately deleted.
- All four actions (soft-delete, restore, force-delete, change-owner) emit Spatie Activity Log entries with `log_name = 'admin'`, `causer` = operator, `subject` = target, `properties` = a description of the change.
- The schema-coverage test (ADR-0020) treats `deleted_at` as part of the standard allow-list; it doesn't need to appear in Filament forms.
- This is the project default going forward. New user-facing models without `SoftDeletes` will fail review unless they document why (e.g., write-once audit rows like `Activity` itself).
