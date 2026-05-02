---
status: accepted
---

# Operator panel via Filament; auth shares the Fortify session

The Filament-backed Operator panel is mounted at `/admin` with no panel-local login page. `AdminPanelProvider` does not call `->login()`; the panel relies on the existing Fortify session via the `web` guard. Access is gated by `User::canAccessPanel(Panel $panel): bool` returning `$this->can(Permission::Admin->value)`. Any User without the global `admin` permission gets a 403 at the panel boundary; admins land directly in `/admin` from any product page where they're already authenticated.

## Considered options

- **Panel-local login at `/admin/login`** (Filament's scaffold default; what `php artisan filament:install` writes). Rejected. Forks the auth surface — Fortify already owns login, password confirmation, two-factor, and the test conventions in ADR-0011. A second login page means a second 2FA wiring, a second password-reset story, and a second test base class. The blast-radius-isolation argument for a separate login is real but better solved additively (require `password.confirm` on the panel, layer on IP allowlist or SSO later) than by maintaining a parallel login.
- **Custom `admin` guard with its own user provider.** Rejected. The User model is shared — there is one `users` table, one set of credentials. A separate guard adds session and middleware complexity for no model-level isolation; the only real isolation it buys is "different cookie name," which is not a defense.
- **No panel — operator tasks via tinker / artisan only.** Rejected. The operator workload (impersonation, subscription support, audit access) is high-frequency enough that a UI pays for itself, and tinker is not safe to expose to non-engineering staff.

## Why

The `admin` Permission is global (`team_id = null`) and lives in the same Spatie permission table as every other capability check in the system. Treating panel access as one more `$user->can(...)` check keeps it inside ADR-0002's "permissions, never roles" rule and inside the team-context middleware (ADR-0003) — admins logging in are still subject to the same `setPermissionsTeamId()` machinery as anyone else, with team_id resolving to `null` for global permissions. Forking the auth pipeline would put the panel outside that consistency.

Two-factor matters specifically. `User` already uses Fortify's `TwoFactorAuthenticatable`, and Fortify's middleware enforces 2FA on the existing session. A panel-local login would either (a) skip 2FA, (b) re-implement it, or (c) require dual-factor flows. Sharing the session means 2FA on the product login is 2FA on the panel — one mechanism, audited once.

The decision is reversible. If a future requirement demands a hardened admin-only login (mTLS, hardware key, separate IdP), reintroducing `->login()` plus a custom auth route is a contained change. We are not painting ourselves into a corner.

## Consequences

- `AdminPanelProvider::panel()` does not call `->login()`. Unauthenticated requests to `/admin/*` redirect to the product login (Fortify's `/login`) and return to `/admin` post-auth via standard `intended()` behavior.
- `User::canAccessPanel(Panel $panel): bool` is implemented to call `$this->can(Permission::Admin->value)`. The signature is required by Filament's `FilamentUser` contract; the User model implements it.
- A 403 from `canAccessPanel()` does not log the user out of the product. They remain authenticated for the Inertia surface; only the `/admin` route is denied.
- `password.confirm` middleware is **not** applied to the panel by default. If a future ADR adds it, it goes on the panel's `authMiddleware` array — not on individual Resources.
- The panel inherits the existing session-cookie configuration. There is no separate `admin_session` cookie.
- Tests for the panel use the same Fortify-aware test base as other authenticated tests (ADR-0011). Filament-specific test utilities (`Livewire::test(EditUser::class)`) layer on top of an authenticated Pest test, not in place of one.
- The `admin` role is provisioned via `DatabaseSeeder` in dev (cold start) and via an in-panel "Grant admin" action thereafter. Self-revoke is refused.
