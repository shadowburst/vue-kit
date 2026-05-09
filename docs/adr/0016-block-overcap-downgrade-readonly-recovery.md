---
status: accepted; supersedes ADR-0013
---

# Block over-cap downgrade, no destructive prune; involuntary cancel goes read-only

The single invariant is **"a Team is never voluntarily transitioned into the Over-cap state."** Any subscription transition (cancel today, future tier-swap-down) is refused at the controller when the team's non-Owner Membership count would exceed the destination tier's cap. The only path into Over-cap is involuntary cancellation (payment-failure → Stripe `canceled`); on that path nothing is destroyed — the team enters read-only mode (writes blocked except `user.delete`) and the Owner recovers by fixing payment and either resubscribing to a tier whose cap accommodates the current count or removing members until under cap. ADR-0013's grace-period banner, period-end webhook prune, and `model_has_roles.created_at` column are removed.

## Considered options

- **The ADR-0013 design (period-end prune with grace-period banner; Owner consents implicitly by not resuming).** Rejected on revisit. The "hostile cancel = chargebacks" concern that originally pushed us toward prune doesn't apply to this product's segment, and the destructive consequence of cancel-then-forget is harsher than the alternative we now prefer. The implicit-consent UX (a banner the Owner may not see if they don't log in during grace) trades a minor friction (refusing cancel) for a major one (silently deleting members at period end).
- **Block-downgrade with destructive sweep on involuntary cancel** as a parallel rule. Rejected because the whole point of distinguishing voluntary from involuntary in ADR-0013 was that involuntary is *not* an Owner decision — punishing the team for a card-expiry by deleting members is the exact UX the prune was already steering away from. Read-only preserves member data without granting feature access.
- **Block-downgrade with hard subscription suspension on involuntary cancel** (members lose access entirely until billing fixed). Rejected because the team's collaboration data is *visible* to members in legitimate read-only mode, which makes the "fix your card" pressure clear without erecting a wall. Hidden-team UX is also disorienting and risks support load.
- **Block-downgrade + read-only on Over-cap** (chosen). Single invariant covering both paths into the Over-cap state. The voluntary path can't reach it; the involuntary path enters it but recovers without destruction. Read-only is derived live from `members.count(non-owner) > cap` — no separate state to flip and unflip.

## Why

The original chargeback worry was the load-bearing reason ADR-0013 went destructive. Removing it collapses three concerns into one: voluntary downgrades that would shrink the team are simply refused (Owner removes members first, explicitly), involuntary downgrades preserve everything until the Owner self-heals, and the in-between "team that paid less than what it has" state is a single derived predicate rather than a sequence of timed transitions and webhook side-effects.

The asymmetry — voluntary blocked vs. involuntary tolerated-but-locked — is coherent because both branches refuse to destroy member data without explicit Owner action. Voluntary cancel is "the Owner clicks cancel" and we ask them to make the destruction explicit (remove members first). Involuntary cancel is "Stripe gave up after dunning" and we ask the Owner to remediate (fix payment, then either upgrade or remove members) before granting feature access again. Neither path silently deletes anything.

Period-end (Cashier default) is kept for voluntary cancel: the Owner paid for the period and should get it. The edge case where an Owner cancels (allowed at 0 non-Owners), then invites during the grace window, then lands in read-only at period end is a deliberate choice the Owner made — the system stays consistent with the invariant by treating it as an entry into Over-cap that's automatically locked, not destroyed.

## Consequences

- `SubscriptionPolicy::update` is split into `cancel`, `resume`, and (future) `swap`. Each is owner-identity plus an action-specific predicate: `cancel` requires `member_count <= free_cap`; `resume` requires only owner identity; `swap($destinationTier)` requires `member_count <= cap_for($destinationTier)`. Per ADR-0014, owner identity is still an identity check and not a permission — the cap predicate is a state check layered on top.
- The cancel button surfaces through `auth.abilities.subscription.cancel` (boolean, ADR-0008's existing pattern). Disabled with copy "Remove N members to cancel" when false.
- `customer.subscription.deleted` webhook handling reverts to ADR-0008's behavior: purge Pennant cache. No prune step.
- The `model_has_roles.created_at` column added by ADR-0013 is removed (migration edited in place — pre-prod). The Spatie pivot returns to its intentionally implicit shape per ADR-0001. "Latest N" no longer needs a defined meaning.
- The `auth.subscription.grace_period` shared prop and the `subscriptionGracePeriodData` middleware method are removed. The grace-period banner component is removed.
- Two atoms are added to shared props for the read-only banner: `currentTeam.member_count` (non-Owner count) and `auth.subscription.active` (boolean). The frontend derives `over_cap = member_count > auth.features['team-member-cap']` and picks banner copy from `(over_cap, subscription.active)`.
- A `Team::isOverCap(): bool` method becomes the single source of truth for the predicate. Policies that block in read-only mode (`UserPolicy::update`, `TeamPolicy::update`, future content-write policies) consult it. `UserPolicy::delete` does *not* consult it — member removal is the cap-reducing escape hatch out of Over-cap.
- The Stripe Portal cancel-disabled config (ADR-0013) is kept: server-side enforcement of the cap predicate requires cancel to flow through our controller.
- Resubscribe after involuntary cancel does not auto-restore feature access if the new tier's cap is below the current member count. The team stays read-only until the Owner removes the excess (or upgrades higher). This is the same Over-cap rule applied to the resubscribe transition.
- Pricing remains unchanged (ADR-0007: flat-fee per team, no per-seat).
