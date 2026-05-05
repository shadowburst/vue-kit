# ADR 0009: File Grouping by Domain Noun

- **Status:** Accepted
- **Date:** May 2026

## Context

PHP classes under `app/` were originally laid out by *type* (`app/Data/`,
`app/Actions/`, `app/Policies/`, etc.) with most type folders flat — every
class for every model living side-by-side. As the codebase grows past a
handful of models, a flat layout makes it harder to answer "where is the
code for X?" without grepping. A second axis — *grouping by domain noun
within each type folder* — was needed.

This ADR records the seven decisions that define the convention. Each
involves a non-obvious trade-off worth keeping a written rationale for so
the layout doesn't drift back to flat (or worse, into a `Shared/` junk
drawer) the next time someone is unsure where a new file goes.

---

## Decision 1 — Folder Names Are Singular

### Choice Made

Per-noun subfolders use the singular form of the model: `app/Data/Team/`,
not `app/Data/Teams/`. The pre-existing `app/Actions/Teams/` folder is
renamed to `app/Actions/Team/` as part of the migration.

### Alternatives Considered

**Plural folders** matching the existing `Teams/` precedent and Laravel's
table-naming convention.
*Rejected:* `app/Data/Teams/TeamData.php` reads as "data classes about
many teams," which is wrong — `TeamData` describes one team. The folder
represents *the model*, not a collection of instances.

### Reasoning

Singular folders mirror the model class name 1-to-1
(`Team` ↔ `app/Data/Team/` ↔ `app/Policies/Team/`), so there is no
mental translation between path segments and class names. The pluralised
`Teams/` in the existing codebase was an early inconsistency, not a
deliberate convention.

### Consequences

- Migration renames `app/Actions/Teams/` → `app/Actions/Team/` and
  `tests/Feature/Teams/` → `tests/Feature/Team/`.
- Database table names remain plural (`teams`) — that is an Eloquent
  convention, unrelated to source-file layout.

---

## Decision 2 — Grouping Key Is a Domain Noun, Not Strictly an Eloquent Model

### Choice Made

A subfolder name may be any noun from the project's ubiquitous language
(see `CONTEXT.md`), regardless of whether the noun corresponds to a class
in `app/Models/`. Examples accepted as valid grouping keys today:

- `Team`, `User` — Eloquent models
- `Membership` — domain concept stored implicitly in `model_has_roles`
  (per ADR 0001), no `App\Models\Membership` class
- `Role`, `Permission` — Spatie models in the vendor namespace, not
  `app/Models`
- `Locale`, `Appearance` — i18n / UX domain concepts with no model

### Alternatives Considered

**Strict-model grouping** — a subfolder name must match a class in
`app/Models/`.
*Rejected:* would push `RoleName`, `PermissionName`, `AppLocale`, the
`Settings/*` controllers, and most middleware into a generic Shared
bucket. It would also create perverse pressure to invent thin model
classes (`Membership extends Model`) just to give files a home.

**Auto-derive allowed names from `app/Models/`** for an arch-test
allow-list.
*Rejected:* same flaw — domain nouns ≠ Eloquent models in this kit.
Membership, Role, Permission, Locale, and Appearance are all first-class
domain terms in `CONTEXT.md` without a corresponding model class. Any
derivation from `app/Models/` would reject exactly the cases the
convention is designed to embrace.

### Reasoning

The grouping should mirror the *domain language*, not the accident of
which concepts happen to have Eloquent rows. Developers searching for
"the locale stuff" think in domain terms, not model classes.
`CONTEXT.md` is the authoritative list of valid domain nouns and
already enumerates them.

### Consequences

- Adding a new domain noun (e.g. `Invoice`) requires adding the term to
  `CONTEXT.md` *and* using it as a folder name — the two stay in sync.
- The convention can only be enforced *structurally* (no class at the
  root of a type folder) — semantic correctness ("is this folder name a
  real noun?") is left to code review. See Decision 6.

---

## Decision 3 — Three Valid Bucket Kinds: Domain Noun, Integration, Cohesive Feature

### Choice Made

A subfolder name must fall into exactly one of three kinds:

1. **Domain noun** (preferred) — a term from `CONTEXT.md`'s ubiquitous
   language. Example: `Team/`, `Membership/`, `Locale/`.
2. **Integration boundary** — a third-party framework or package that
   defines its own contracts the application implements. Example:
   `Fortify/` (action classes Fortify dispatches), `Inertia/`
   (middleware that hooks Inertia's request lifecycle).
3. **Cohesive feature** — a recognisable user-facing surface area that
   groups multiple files where splitting would scatter cohesion.
   Example: `Settings/` (the settings page), `Dashboard/` (the
   post-login dashboard). Bar: ≥2 files OR strong forward-looking
   knowledge that more will land.

### Alternatives Considered

**Domain noun only — no integration or feature buckets.**
*Rejected:* would force `Fortify/CreateNewUser` to move to `User/` and
lose the "this is a Fortify framework hook, not a normal action"
signal. ADR 0004's arch carve-out for `App\Actions\Fortify` would have
to be rewritten as a per-class enumeration. Settings sub-pages
(`AppearanceController`, `LocaleController`, etc.) would scatter across
five different domain-noun folders even though developers think of them
as one settings surface.

**Domain noun + integration only — no feature buckets.**
*Rejected:* `Settings/` does not fit either of the other kinds, but is
clearly justified by cohesion. Disallowing the third kind would force
`Settings/` to either be split (loses cohesion) or to invent a fake
"Settings" domain noun (pollutes `CONTEXT.md`).

### Reasoning

The three kinds together cover every legitimate grouping observed in
the codebase. Locking the rule down to *exactly* these three —
preferentially domain noun, otherwise integration or cohesive feature —
keeps the convention tight while accommodating real cases that don't
fit the dominant pattern.

### Consequences

- Code review judges whether a proposed feature bucket meets the
  cohesion bar. The risk that "feature" is abused as a synonym for
  "miscellaneous" is real but mitigated by the absence of a generic
  catch-all (Decision 4).
- New integration buckets are expected as packages are added (e.g. a
  future `Sanctum/` or `Pulse/` folder) without renaming.

---

## Decision 4 — No `Shared/` Folder; Three Alternative Homes for Cross-Cutting Code

### Choice Made

There is no generic `Shared/`, `Common/`, `Misc/`, `Util/`, or
`Helpers/` subfolder under any type folder. Cross-cutting code finds a
home in one of three specific places instead:

1. **Reusable traits** → existing `app/Concerns/` (already established).
2. **Cross-model classes** → the *primary domain noun*, defined as the
   concept the class produces or targets. Example: a hypothetical
   `InviteUserToTeam` action lives in `app/Actions/Membership/` because
   what it creates is a Membership, not because it touches User and
   Team.
3. **Abstract base classes** → the type-folder root, as a documented
   sole exception. Today only `app/Http/Controllers/Controller.php`
   qualifies.

### Alternatives Considered

**A generic `Shared/` folder** under each type folder for anything that
doesn't fit a domain noun.
*Rejected:* every folder named `Shared/`, `Common/`, or `Misc/`
inevitably becomes a junk drawer. Once the path of least resistance
exists, "where does this go?" stops being a design conversation. The
specific risk: an `InviteUserToTeam` action gets dropped in `Shared/`
and the fact that **Membership** is the real domain answer never
surfaces.

**No escape hatch at all** — every class must fit a domain-noun /
integration / feature bucket.
*Rejected:* abstract bases like `Http/Controllers/Controller.php` have
nowhere natural to live (they parent every domain noun, so they can't
sit *inside* any one of them), and reusable traits already have a
home in `app/Concerns/`.

### Reasoning

"Shared" is also a poor name in its own right — every class is shared
by something. The three named alternatives each have a more honest
purpose. The friction of "the result-noun isn't obvious for this
class" at file-creation time is *the feature*: it forces the
conversation about which domain concept the class actually produces.

### Consequences

- The abstract `Controller.php` is the only file allowed at the root
  of `app/Http/Controllers/`, mirroring how ADR 0004 already exempts
  it from the `final` rule.
- `app/Concerns/` retains its current role and is exempt from the
  subgrouping convention (Decision 5).

---

## Decision 5 — Exempt Type Folders: `Models/`, `Providers/`, `Concerns/`

### Choice Made

Three type folders are exempt from the per-noun subgrouping rule:

- `app/Models/` — already keyed by model; subgrouping would produce
  `app/Models/Team/Team.php`, repeating the same word twice.
- `app/Providers/` — providers are service-bound, not model-bound.
  Class names are already self-describing, the set is small and stable.
- `app/Concerns/` — by Decision 4, this *is* the cross-cutting bucket;
  subgrouping would defeat the point.

Every other type folder under `app/` subgroups: `Actions/`, `Data/`,
`Enums/`, `Policies/`, `Http/Controllers/`, `Http/Requests/`,
`Http/Middleware/`.

### Alternatives Considered

**Subgroup `Http/Middleware/` only when it grows past N files.**
*Rejected:* a count-based threshold is harder to remember than "always
subgroup." Today's five middleware classes (Team, Locale, Appearance,
Inertia, plus future Fortify) all map cleanly to noun or integration
buckets, so consistency wins over file-count optimisation.

**Subgroup `Providers/` by integration** (e.g. `Providers/Fortify/`).
*Rejected:* the class name `FortifyServiceProvider` already encodes the
integration; a folder layer would duplicate that information without
adding navigation value.

### Reasoning

The exemption list is closed, not open. Other type folders that may be
added in future (e.g. `app/Listeners/`, `app/Jobs/`) default to
subgrouping unless an explicit case is made — which would require
revising this ADR.

### Consequences

- Adding a new type folder requires deciding upfront whether it
  subgroups; the default is yes.
- The `Http/Controllers/Controller.php` exception is *not* an
  exemption of the type folder — `Http/Controllers/` does subgroup.
  It is a single-file root exception (Decision 4).

---

## Decision 6 — Structural Enforcement Only via Pest Arch Test

### Choice Made

A Pest arch test under `tests/Arch/` asserts the *structural* property:
no PHP class file lives at the root of any non-exempt type folder, with
a documented exemption list (today: just
`app/Http/Controllers/Controller.php`). The semantic property — "is
this subfolder name a valid domain noun, integration, or feature?" —
is not enforced by automation and is left to code review.

### Alternatives Considered

**Structural + semantic allow-list** — hardcode the set of allowed
folder names (`['Team', 'User', 'Membership', 'Locale', 'Appearance',
'Fortify', 'Settings', 'Inertia', 'Dashboard']`) per type folder;
adding a new bucket fails the test until the list is updated.
*Rejected:* the maintenance overhead of editing the allow-list on
every domain-noun addition was deemed too high relative to the drift
risk. Code review is expected to catch invented buckets like `Misc/`.

**Derive the allow-list from `app/Models/`.**
*Rejected:* incompatible with Decision 2 — most valid domain nouns in
this kit (Membership, Role, Permission, Locale, Appearance) have no
Eloquent model.

**Code review only, no arch test.**
*Rejected:* the structural rule is mechanical and easy to enforce;
leaving it entirely to review burns reviewer attention on a check the
test suite can do for free.

### Reasoning

The structural rule is the load-bearing one — a class at the root of
`app/Data/` is a clear violation regardless of what subfolder it
*should* have gone into. The semantic rule is fuzzier and depends on
domain knowledge that an arch test cannot encode reliably. Splitting
the enforcement (test for structure, review for semantics) puts each
check where it works best.

### Consequences

- The arch test fails immediately on a class added at a type-folder
  root, catching the omission before CI completes.
- A class moved to a poorly-chosen subfolder (e.g. `Misc/`) passes the
  arch test and must be caught in PR review. This is the accepted
  cost of the looser enforcement.
- The exemption list lives in the arch test file alongside ADR 0004's
  existing rules — one file, easy to grep, easy to extend.

---

## Decision 7 — Filenames Retain the Noun Prefix

### Choice Made

Files keep their existing class-name-matching filenames even when the
folder already encodes the noun. `app/Data/Team/TeamData.php`, not
`app/Data/Team/Data.php`. `app/Policies/Team/TeamPolicy.php`, not
`app/Policies/Team/Policy.php`.

### Alternatives Considered

**Drop the noun prefix** since the folder carries it: `Team/Data.php`,
`Team/Policy.php`, `Team/Controller.php`, `User/Request.php`.
*Rejected* for three reasons:

1. **Import collisions.** A controller importing both `TeamData` and
   `UserData` becomes `use App\Data\Team\Data;` and
   `use App\Data\User\Data;` — the second import shadows the first.
   Working around that with `use ... as TeamData` reintroduces the
   prefix at every call site, with extra ceremony.
2. **IDE / editor ergonomics.** Tab titles, fuzzy file-finders, stack
   traces, and grep all become less useful when half the project's
   classes are named `Data` or `Policy`.
3. **ADR 0004 already mandates the suffix.** The `Data` and other
   suffix rules exist because the suffix carries weight at the call
   site (`new TeamData(...)`); the folder providing the same
   information at a different position does not remove the call-site
   benefit.

### Reasoning

Path redundancy (`Team/TeamData.php` says "Team" twice) is the lesser
evil. The path is read once, when navigating to the file; the class
name is read every time the class is imported, instantiated, or
referenced in a stack trace.

### Consequences

- File paths are mildly redundant. This is the explicit trade-off and
  the existing ADR 0004 suffix rules already encode it.
- The arch test from Decision 6 only checks folder structure; suffix
  rules from ADR 0004 continue to enforce filename shape.
