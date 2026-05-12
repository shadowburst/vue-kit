---
status: accepted
---

# Audit logging via Spatie Activity Log under `log_name = 'admin'`

Operator-initiated actions are recorded via `spatie/laravel-activitylog`, scoped to a single `log_name` value of `'admin'`. Three classes of events flow through this log: (1) impersonation start/stop (per ADR-0023), (2) `admin` role grant and revoke, and (3) Operator-initiated mutations to `User`, `Team`, and `Subscription` rows (soft-delete, restore, force-delete, change-owner, period-end cancel, resume, extend-trial). Model-level mutations use Spatie's `LogsActivity` trait with `getActivitylogOptions()` returning `useLogName('admin')` when the actor is an Operator. Action-level events (impersonation, role changes) are emitted explicitly via `activity('admin')->...->log(...)`. A read-only Filament Resource on Spatie's `Activity` model, scoped to `log_name = 'admin'`, surfaces the timeline.

## Considered options

- **Custom `AdminAction` model** with a polymorphic `target` and a JSON `metadata` column. Rejected. Building this from scratch reimplements every feature of `spatie/laravel-activitylog` (causer, subject, properties, batching, log scoping) at lower quality and with no community support. The package is Spatie-maintained, used elsewhere in the ecosystem, and integrates with Eloquent events.
- **Laravel's built-in audit via `Log::info()` calls** to a separate channel. Rejected. File-based logs are not queryable, not joinable to users, not paginatable in a Filament Resource, and rotate away. The whole point of the Operator panel is operator self-service for support questions; "grep the logs on the box" is the opposite of self-service.
- **Spatie Activity Log without a `log_name` scope** (default log). Rejected. The package's default log captures model-level activity for any model using `LogsActivity` — including potentially user-side activity in the future (e.g., a project that adds activity logging to user-facing actions). Mixing operator and user activity in one log makes the Operator panel's Resource awkward to filter and risks leaking user-side activity to the operator timeline by accident. A dedicated `log_name = 'admin'` is the cheap separation.
- **Spatie Activity Log with multiple `log_name` values** per event type (`'impersonation'`, `'role-grant'`, `'subscription'`). Rejected for now. Single-table with one log_name plus a `description` field is queryable enough; multiple logs splinter the timeline view in Filament and force every new event type to register a new log_name. Reconsider if event volume warrants it.

## Why

The dependency is justified on three counts. (1) The shape of "who did what to whom, when, with what properties" maps exactly onto Spatie's schema (`causer`, `subject`, `description`, `properties`, `created_at`). (2) The `LogsActivity` trait gives us model-mutation audit *automatically* — we don't write per-field diff code; the trait records `attributes` and `old` properties on update events. (3) The Activity model is plain Eloquent, which means a Filament Resource over it costs nothing — no custom adapter, no special queries.

Scoping to `log_name = 'admin'` is the small choice that pays off later. If a future feature adds user-facing activity logging (e.g., team activity feed), it lives under a different log_name and the Operator panel is unaffected. The cost today is one line in `getActivitylogOptions()` per logged model.

The `LogsActivity` trait is applied selectively: only on mutation events that are (a) Operator-initiated or (b) destructive on a user-facing model. Routine writes during normal product use are not logged — the volume is high, the value is low, and the model still has its `updated_at` for forensic purposes. The conditional uses Spatie's `tapActivity()` callback to set `log_name` based on whether `auth()->user()->can('admin')` at the moment of the write.

The audit log is not surfaced to the impersonated user (per ADR-0023's privacy posture). It is also not surfaced in the user-facing Inertia app at all — it is operator-internal.

## Consequences

- Add `spatie/laravel-activitylog` to `composer.json`. Per the project's CLAUDE.md, dependency adds require approval; this ADR is the record of that approval. The package's migration is published and run.
- `User`, `Team`, and the Cashier `Subscription` model use `LogsActivity`. `getActivitylogOptions()` configures `logFillable()` and `logOnlyDirty()`. A `tapActivity()` callback sets `log_name = 'admin'` when the causer has the `admin` permission, otherwise leaves it on the default log (which is currently unused but reserved for future user-facing activity).
- Action-level events (impersonation start/stop, admin grant/revoke) call `activity('admin')->causedBy($operator)->performedOn($target)->withProperties([...])->log('impersonation.start')` (or similar). The `description` argument to `log()` is a stable machine-readable identifier, not a sentence — UI-side translation reads from `lang/{locale}/admin.php`.
- A Filament Resource `App\Filament\Resources\ActivityResource` over `Spatie\Activitylog\Models\Activity`, scoped via `getEloquentQuery()` to `log_name = 'admin'`. Read-only — no create/edit/delete actions. Table columns: timestamp, causer (Operator), description, subject (polymorphic, displayed as a link to the underlying Resource where one exists). Properties shown in a detail panel.
- The Activity model is itself **not** soft-deletable, despite ADR-0021's project-wide soft-delete default. Audit rows are write-once; soft-delete on an audit log is a contradiction. This is the documented exception to that default.
- The `causer` is nullable. Bootstrap actions (the `DatabaseSeeder`-driven first admin grant) record `causer_id = null` and a `properties.actor = 'system'` field for clarity. The Activity Resource displays "system" for null causers.
- Per-record retention is not addressed by this ADR. If a future requirement demands a retention window (GDPR, storage cost), it adds a scheduled prune job; the current default keeps everything.
- Tests cover: each Operator action emits exactly one Activity row with the expected `log_name`, `causer`, `subject`, and `description`; the Activity Resource excludes non-`admin` log_name rows; the bootstrap-grant case records `causer_id = null` correctly.
