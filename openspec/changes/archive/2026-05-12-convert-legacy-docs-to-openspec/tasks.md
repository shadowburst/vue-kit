## 1. Source Inventory

- [x] 1.1 Confirm `CONTEXT.md` is the source for domain vocabulary, Role and Permission semantics, relationships, authorization rules, and flagged ambiguities.
- [x] 1.2 Inventory every file under `docs/adr/` and record its ADR number, slug, title, and status, including duplicate numbers and superseded entries.
- [x] 1.3 Identify active replacement decisions for superseded ADRs, especially ADR-0013 superseded by ADR-0016 and ADR-0014 confirm-dialog stacking superseded by ADR-0017 dialog depth.

## 2. Domain Knowledge Migration

- [x] 2.1 Create or update the canonical OpenSpec `domain-knowledge` spec from `CONTEXT.md`.
- [x] 2.2 Preserve exact meanings for Team, Personal Team, Membership, Role, Permission, Subscription, Tier, Feature, Operator panel, Impersonation, Activity Log, and Over-cap.
- [x] 2.3 Preserve the canonical Role table, Permission list, Team relationships, owner-only identity checks, and authorization rules.
- [x] 2.4 Preserve resolved ambiguity notes for global Admin versus team Manager and team-scoped `user.create` as Membership creation.

## 3. Architecture Decision Migration

- [x] 3.1 Create or update the canonical OpenSpec `architecture-decisions` spec from all current decisions in `docs/adr/*.md`.
- [x] 3.2 Group ADR-derived requirements by decision area: Membership and authorization, billing and Tier features, PHP architecture and testing, frontend boundaries, Spatie Data serialization, Operator panel, deletion, Impersonation, and Activity Log.
- [x] 3.3 Simplify ADR content by carrying forward active rules and concise rationale while omitting obsolete alternatives, superseded implementation details, and historical prose that no longer guides future work.
- [x] 3.4 Include ADR number plus slug or title where useful so each OpenSpec rule can be traced back during review without making ADR history the primary structure.
- [x] 3.5 Collapse superseded ADRs into the active replacement decisions instead of preserving them as standalone rules.

## 4. Review and Verification

- [x] 4.1 Compare `CONTEXT.md` against the migrated `domain-knowledge` spec and confirm no domain term, role, permission, relationship, authorization rule, or ambiguity was dropped.
- [x] 4.2 Compare `docs/adr/` against the migrated `architecture-decisions` spec and confirm every current decision is represented or intentionally collapsed into an active replacement.
- [x] 4.3 Run `openspec status --change "convert-legacy-docs-to-openspec"` and confirm the change is apply-ready.
- [x] 4.4 Run the OpenSpec validation command available in this repository, or document if no validation command is available.

## 5. Legacy Documentation Removal

- [x] 5.1 Remove `CONTEXT.md` after the `domain-knowledge` spec has been verified against it.
- [x] 5.2 Remove `docs/adr/` after the `architecture-decisions` spec has been verified against it.
- [x] 5.3 Note in the implementation summary that OpenSpec is now the single source of truth for domain and architecture documentation.
