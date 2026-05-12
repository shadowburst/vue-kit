---
status: accepted
---

# Impersonation semantics: full-state, no admin-on-admin, mutation guards via middleware

When an Operator (User with `admin` permission) impersonates a target User, the request resolves with the target's full session-derived state: `auth()->user()` is the target, `current_team_id` is the target's, Spatie permissions and Pennant features resolve against the target's team. The original Operator identity is stashed in the session as the *impersonator* and exposed to the application via a helper. A globally-rendered, localized banner (in every product page and the Operator panel) shows "Impersonating {name}" with a "Leave impersonation" affordance. Admin-impersonates-admin is refused — only non-admins can be targeted. Routes that mutate password, email, or subscription state refuse during impersonation via a single middleware (`RefuseDuringImpersonation`). Every start and stop is logged to the Spatie Activity Log under `log_name = 'admin'`.

## Considered options

- **Partial-state impersonation** — switch only `auth()->user()` but keep the Operator's `current_team_id` and global permissions. Rejected. The whole point of impersonation is to *reproduce the target's reality* for support diagnosis. If the team context is wrong, the operator sees a different page than the user is reporting; if global permissions are the operator's, every gated UI looks accessible. The bug becomes invisible.
- **Full-state without mutation guards** — switch everything, trust operators not to click the wrong button. Rejected. Subscription mutations charge cards. Password changes lock users out. Email changes break account recovery. These are not "operators are careful" mistakes — they are "operators are tired at 3am" mistakes, and the cost is real customer harm. A middleware that refuses these routes during impersonation is a few lines that prevent unbounded damage.
- **Allow admin-on-admin impersonation.** Rejected. It opens an audit-laundering path — Alice impersonates Bob (also an admin) to perform a destructive action that the Activity Log attributes to Bob. Refusing it is a one-line authorization callback on the Filament action; the loss of capability is negligible because admins can ask each other to act directly.
- **No impersonation; operators read state via Resources only.** Rejected. Many user-reported bugs are layout/visibility issues that depend on team context, tier-derived features, or per-user state that isn't visible in a Resource view. Impersonation is the difference between "I can see the same thing the user is seeing" and "I have to guess from the database."

## Why

ADR-0003 (current_team_id over URL scoping) makes team context derive from `auth()->user()->current_team_id` via middleware. Full-state impersonation is therefore *automatically correct* for team context — there is no special case to write; we just don't override it. The same holds for ADR-0008 (Pennant features per team): the target's features resolve naturally against the target's team. This ADR is mostly the choice to *not* fight that machinery.

ADR-0014 (ownership as identity) means subscription mutations and other owner-only operations are gated by `teams.owner_id === auth()->user()->id`. Under impersonation, this check passes if the target *is* the owner — which is exactly when subscription mutation is most dangerous (the operator could cancel the customer's plan as them). The mutation-guard middleware is the second line of defense beneath ADR-0014; the identity check still applies, but the middleware refuses regardless.

Localization of the banner is non-negotiable. The product is i18n-aware (`lang/`), and an English-only "You are impersonating X" banner shipped to a non-English user is a bug. The banner copy lives in `lang/{locale}/admin.php`.

The Activity Log captures start and stop, not every action taken in between. Per-action logging is too noisy and partially redundant with model-level activity (Spatie's `LogsActivity` trait on User/Team/Subscription captures the writes). The Operator-level "what session was open" is what needs explicit logging; the per-action audit falls out of model logging.

## Consequences

- Add `lab404/laravel-impersonate` (the framework-level package; existing Filament wrappers are abandoned or v3-only). The Filament action invokes the package directly.
- A Filament row action on the User Resource, "Impersonate," is gated by `fn (User $record): bool => $record->cannot(Permission::Admin->value)`. Visible only when the operator can impersonate the row.
- Session shape: `impersonator_id` is stored in the session. A helper (e.g., `Auth::impersonator()` or a service in `App\Services\Impersonation`) returns the Operator's User model for banner rendering and middleware checks. The helper returns `null` when not impersonating.
- A `RefuseDuringImpersonation` middleware applies to: Fortify password-update route, Fortify email-update route, the project's subscription-mutation routes (cancel, resume, swap, payment-method update), and the account-deletion route. Refusal returns a 403 with a localized message.
- The banner is rendered via Inertia layout props (`useLayoutProps` per Inertia v3) so it appears on every authenticated page. A separate Filament render hook ensures the banner appears in the Operator panel as well, since the panel is Blade, not Inertia.
- "Leave impersonation" hits a single endpoint (`POST /impersonate/leave`) that restores the Operator's session and redirects to wherever the leave action was triggered from.
- Activity Log entries: `activity('admin')->causedBy($operator)->performedOn($target)->withProperties(['ip' => ..., 'user_agent' => ...])->log('impersonation.start')` on enter, `'impersonation.stop'` on leave. The log entries are not surfaced to the impersonated user — they are operator-internal.
- The Activity Resource in Filament filters to `log_name = 'admin'` and includes impersonation events, role grants/revokes, and Subscription mutations in a single timeline.
- Tests cover: impersonation grants target's permissions and team context; mutation-guard middleware refuses each protected route; admin-on-admin impersonation is refused at the Filament action; the banner renders in the target's locale.
