## Context

`CONTEXT.md` currently defines the project language for Team, Personal Team, Membership, Role, Permission, Subscription, Tier, Feature, Operator panel, Impersonation, Activity Log, and Over-cap. It also records current Role and Permission semantics, relationships, authorization rules, and resolved ambiguities.

`docs/adr/` currently contains accepted architecture decisions spanning membership storage, authorization, team scoping, testing conventions, validation, controllers, billing, feature flags, file organization, frontend forms and route typing, request-scoped services, downgrade behavior, UI dialog stacking, ownership checks, naming, data transfer, validation limits, translations, Operator panel access, deletion semantics, Filament coverage, Impersonation, and Activity Log behavior.

OpenSpec is now the target workflow for proposed and implemented changes. The migration should make OpenSpec the canonical destination, simplify the ADR surface area, and remove the legacy files after their current meaning is captured.

## Goals / Non-Goals

**Goals:**

- Preserve all domain vocabulary and semantic rules from `CONTEXT.md` in OpenSpec requirements.
- Distill every current decision in `docs/adr/` into cleaner OpenSpec requirements with traceability to the original ADR number and title where it helps explain origin.
- Collapse superseded ADRs, duplicate ADR numbers, and historical implementation debates into the active rule they produced.
- Keep the migrated specs useful for future `/opsx-propose` and `/opsx-apply` work by organizing requirements around capabilities rather than copying files verbatim.
- Include validation tasks that compare OpenSpec output against the legacy source files.
- Remove `CONTEXT.md` and `docs/adr/` after the OpenSpec specs cover their current useful content.

**Non-Goals:**

- Change application behavior, database schema, dependencies, runtime configuration, or tests.
- Re-litigate accepted ADRs or introduce new architecture decisions beyond consolidation and cleanup.
- Preserve historical ADR prose, superseded rationale, or rejected alternatives verbatim when they do not help future implementation.

## Decisions

- Create two specs: `domain-knowledge` and `architecture-decisions`.
  - Rationale: `CONTEXT.md` and `docs/adr/` serve different purposes. Keeping them separate preserves that distinction while avoiding one oversized spec.
  - Alternative considered: one `project-documentation` spec. Rejected because vocabulary/domain rules and decision history would be harder to review and maintain independently.

- Model migrated content as normative requirements, not archival prose.
  - Rationale: OpenSpec specs should define expected project behavior and constraints. Requirements with scenarios are easier to validate during future changes than pasted Markdown sections.
  - Alternative considered: copy legacy documents into OpenSpec unchanged. Rejected because it would create a new location but not a spec useful for change validation.

- Simplify ADR content during migration.
  - Rationale: ADRs contain historical debates, duplicate numbers, and superseded decisions. OpenSpec should carry forward the current architectural contract, not every intermediate argument.
  - Alternative considered: preserve every ADR as a one-to-one OpenSpec requirement. Rejected because it would keep the legacy complexity under a new directory.

- Keep ADR traceability where it clarifies origin, but do not let it dominate the new specs.
  - Rationale: Future maintainers need to map an OpenSpec requirement back to the legacy ADR that introduced it, especially when investigating why a pattern exists.
  - Alternative considered: omit ADR references after migration. Rejected because it loses historical decision context during the conversion.

- Remove legacy files after migration.
  - Rationale: Keeping `CONTEXT.md`, `docs/adr/`, and OpenSpec specs would create duplicate sources of truth immediately after the conversion. The implementation should compare against the legacy files, then remove them once OpenSpec is complete.
  - Alternative considered: keep legacy files as historical archives. Rejected because the goal is a cleaner documentation system, not parallel documentation.

## Risks / Trade-offs

- [Risk] Simplification may accidentally change the meaning of a current decision. → Mitigation: preserve active rules exactly, compare each legacy source against the new specs before deletion, and keep ADR identifiers where they clarify origin.
- [Risk] The `architecture-decisions` spec may become broad. → Mitigation: group requirements by decision area and keep each requirement focused on the current rule rather than historical alternatives.
- [Risk] Removing legacy files may lose useful rationale. → Mitigation: retain concise rationale only where it changes future implementation choices; omit obsolete debates and superseded details.
- [Risk] Some ADR numbers are duplicated across unrelated decisions. → Mitigation: reference both number and slug/title during migration, then consolidate by topic in OpenSpec.
