## Why

The project currently keeps canonical domain context in `CONTEXT.md` and architecture decisions in `docs/adr/`, while new work is expected to flow through OpenSpec. Converting the legacy documentation into cleaner OpenSpec specs gives future changes a single specification surface for project vocabulary, domain rules, and architectural constraints, without carrying forward the full historical ADR sprawl.

## What Changes

- Add OpenSpec specs that preserve the canonical project vocabulary and domain rules currently documented in `CONTEXT.md`.
- Add OpenSpec specs that consolidate and simplify the accepted architecture decisions currently documented in `docs/adr/`.
- Collapse superseded ADR chains and duplicate ADR numbers into the current active rules while preserving enough traceability to understand where the rules came from.
- Remove `CONTEXT.md` and `docs/adr/` after their useful content has been migrated into OpenSpec.
- Define a migration approach that makes OpenSpec the canonical home for ongoing domain and architecture documentation.
- Do not change application behavior, production code, database schema, dependencies, or runtime configuration.
- Non-goal: preserving legacy ADR prose verbatim; the result should be cleaner than the source while retaining current decision meaning.

## Capabilities

### New Capabilities

- `domain-knowledge`: Canonical project vocabulary, role and permission semantics, relationships, authorization rules, and flagged ambiguities migrated from `CONTEXT.md`.
- `architecture-decisions`: Current architectural rules distilled from `docs/adr/`, with superseded history collapsed and traceability to the original ADR identifiers where useful.

### Modified Capabilities

- None.

## Impact

- Affects documentation under `openspec/specs/` and the change artifacts under `openspec/changes/convert-legacy-docs-to-openspec/`.
- Uses `CONTEXT.md` and all files under `docs/adr/` as source material, then removes those legacy files as part of implementation.
- No application APIs, frontend routes, backend behavior, migrations, Composer dependencies, pnpm dependencies, or tests are expected to change.
