# Team, not User, is the Cashier billable

The `Team` model carries Cashier's `Billable` trait. Subscriptions belong to teams; `teams.stripe_id` is the Stripe Customer reference. A user's access tier is the tier of their **active** team (`users.current_team_id`), not a property of the user.

## Considered options

- **User-billable**. Each user owns a subscription that travels with them across teams. Rejected because it forces ad-hoc rules for every cross-team interaction ("the team is Pro but this Free user joined — do they see Pro features?") and makes "tier" two different concepts depending on who's asking. Also misaligns with the financial reality: in a multi-tenant team product, the team is the customer.
- **Team-billable** (chosen). One subscription per team. Every member of a Pro team sees Pro capabilities while acting in that team's context. The Owner is the financially responsible party.

## Why

Authorization in this codebase already pivots on the active team — `setPermissionsTeamId($team->id)` in middleware switches the permission lens per request. Tier-gating mirrors that exactly: the *same* team-context that decides "what permissions does this user have here?" also decides "what tier-gated features does this team have access to?". The two lenses align cleanly.

User-billable would create a permanent impedance mismatch: permission checks scoped to team context, tier checks scoped to user, and a constant need to reconcile the two when a Free user is in a Pro team or vice-versa.

## Consequences

- `Team` uses `Laravel\Cashier\Billable`. Cashier's published migrations (`vendor:publish --tag="cashier-migrations"`) add the `subscriptions` and `subscription_items` tables and the `stripe_id`, `pm_type`, `pm_last_four`, `trial_ends_at` (all nullable) columns to the billable model — adapted to point at `teams`.
- The Cashier subscription `name` stays `'default'` — one subscription per team. Tier swaps (e.g. Pro → a future Enterprise) are `swap($newPriceId)` on the same row, not new subscriptions.
- Customer creation is **lazy**: `teams.stripe_id` starts NULL and Cashier populates it on first checkout. Personal Teams auto-created at signup never touch Stripe unless the Owner upgrades. Signup remains a pure local transaction, with no Stripe failure mode on the critical path.
- `Free` is the absence of an active subscription, not a `$0` Stripe subscription. `Team::tier()` returns `SubscriptionTier::Free` when `subscribed('default')` is false. A canceled or lapsed Pro team falls back to Free with no extra logic.
- New permissions `subscription.view` and `subscription.update` are team-scoped (named to match the existing `team.view` / `team.update` pattern, with **Subscription** as the noun). Owner gets both; Admin gets `subscription.view`; Member gets neither. The Admin-can't-manage-subscriptions default protects the Owner from financial surprise.
- Deleting a team (via `team.delete`) cancels its Stripe subscription immediately, not at period end. The team is gone; the customer expects no further charges.
- The interval choice surfaces as a `SubscriptionInterval` enum (`Monthly`, `Yearly`), passed to the checkout endpoint and resolved against `SubscriptionTier::Pro->stripeMonthlyId()` / `stripeYearlyId()`. Both helpers read from `config/billing.php`, which holds the Stripe Price IDs.
- Pricing is flat-fee per team (no per-seat). Adding a Membership has no Stripe-side effect, and there is no `quantity` to keep in sync. If revenue model later demands per-seat, the migration is a one-time Price swap plus a quantity-sync action — not painful to defer.
