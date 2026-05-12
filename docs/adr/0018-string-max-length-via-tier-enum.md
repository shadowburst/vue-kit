# String max lengths come from a 3-tier enum, not literal numbers

- **Status:** Accepted
- **Date:** May 2026

## Context

Validation `max:` constraints on string fields in `app/Data/**Request` classes were inconsistent — every callsite reflexively wrote `'max:255'` (matching Laravel's default `string()` column width) regardless of whether the field was a name, email, slug, or description. The default was load-bearing-by-accident: a future contributor could relax it, tighten it, or copy-paste it without ever being prompted to think about the cap.

## Decision

A `App\Enums\Validation\StringMaxLength` enum exposes three tiers — `Short = 100`, `Medium = 255`, `Long = 2000` — and every `max:` rule on a string field in `app/Data/**` references a case (`'max:' . StringMaxLength::Short->value`) rather than a literal integer. A Pest arch test in `tests/Arch/DataTest.php` rejects literal `max:NN` rules on string fields under `app/Data/**`, forcing the author to pick a tier. The enum is auto-exposed to TypeScript via Spatie's transformer (per ADR-0017 D9) and Wayfinder's enum runtime modules, so frontend inputs apply the same cap as `maxlength` without duplicating the number.

Current assignments: `name → Short`, `email → Medium`, `password → Short`, `team name → Short`.

## Considered alternatives

- **Semantic per-field constants** (`PERSON_NAME = 100`, `EMAIL = 254`, `TEAM_NAME = 100`). Rejected: ontology grows unbounded — every new feature adds a constant and each is debated separately. Generic tiers cap the design space at three choices.
- **Aligning DB column widths to the enum** via `$table->string('name', StringMaxLength::Short->value)`. Rejected: doubles the surface for a property — drift between validation and the DB shape — that the runtime guards in practice. Revisit when the codebase has a column that would benefit from a true `varchar(100)` boundary.
- **RFC-anchored `Email = 254` case.** Rejected: opens the door back to semantic per-field constants. `Medium = 255` covers RFC 5321's 254-char limit with a 1-char slack that has no practical consequence.
- **Literal `72` for passwords** (bcrypt's effective input limit). Rejected: couples validation to the hashing algorithm. `Short = 100` is comfortably past 72 while staying within the tier system.

## Consequences

- `app/Enums/Validation/StringMaxLength.php` is the single source for string caps shared by backend validation and frontend `maxlength`.
- New `max:` constraints on string fields must reference a tier case; the arch test fails the build otherwise.
- A fourth tier (e.g. `Tiny`, `Huge`) is added only when a real callsite forces the number to be argued for.
- The enum is int-backed because the tier value *is* the validation length and the generated TypeScript type (`100 | 255 | 2000`) drops into `maxlength` attributes without remapping. This is the worked example that triggered ADR-0004 D5's revision on 2026-05-12 from string-backed-only to any-enum.
