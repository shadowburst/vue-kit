---
name: mago-code-quality
description: >-
    Validates, formats, lints, and analyzes PHP code using Mago — scoped strictly to files the
    user has modified or staged in git. Activates automatically after creating or editing PHP
    files, before commits, or when explicitly validating code. Supports auto-fixing formatting
    and lint issues, with detailed static analysis reporting.
---

# Mago Code Quality

## When to Apply

**ALWAYS activate this skill when:**

- Creating or editing any PHP file (`.php`, including `.blade.php`)
- Completing a code change that involves PHP
- User explicitly requests code validation, formatting, linting, or analysis
- Before committing PHP code changes
- Reviewing or refactoring PHP code

## Scope: Modified/Staged Files Only (CRITICAL)

**This skill MUST only target files the user has actually changed.** Mago's commands without a path argument operate on every source file in `mago.toml` — running them that way during normal edits will reformat and lint the entire codebase, producing huge unrelated diffs.

**The rules:**

- ❌ NEVER run `mago format`, `mago lint`, or `mago analyze` without a scoping flag or explicit file list.
- ✅ ALWAYS use one of these scoping mechanisms:
    - `--staged` — files staged for commit
    - Explicit file paths — the files you just edited
    - The `MAGO_FILES` pattern below — both staged and unstaged modifications

**Default scoping pattern** (use this when not targeting a specific file you just edited):

```bash
# Collect modified + staged PHP files relative to HEAD, NUL-delimited for safety
MAGO_FILES=$(git diff --name-only -z --diff-filter=ACMR HEAD -- '*.php' '*.blade.php')

# Bail out if nothing changed (don't fall back to whole-codebase)
if [ -z "$MAGO_FILES" ]; then
    echo "No modified PHP files — skipping Mago."
else
    printf '%s' "$MAGO_FILES" | xargs -0 vendor/bin/sail bin mago format
fi
```

The empty-check is mandatory: `xargs` with no input would invoke `mago format` with zero paths, which falls back to scanning the whole codebase.

**Full-codebase runs are allowed only when the user explicitly asks for one** (e.g. "run mago on the whole project", "format the entire codebase"). Otherwise, stay scoped.

## What is Mago?

Mago is an extremely fast PHP linter, formatter, and static analyzer written in Rust. It provides:

- **Formatter**: Automatically formats code to match style guidelines (AUTO-FIX)
- **Linter**: Identifies code issues with customizable rules (AUTO-FIX available)
- **Static Analyzer**: Deep analysis to catch type errors and potential bugs (AUTO-FIX available)

## Command Overview

| Command   | Purpose             | Auto-Fix Available  | Reporting Only Mode |
| --------- | ------------------- | ------------------- | ------------------- |
| `format`  | Fix code formatting | ✅ Default behavior | `--check` flag      |
| `lint`    | Check code quality  | ✅ Use `--fix` flag | Default behavior    |
| `analyze` | Static analysis     | ✅ Use `--fix` flag | Default behavior    |

## Format Command

**Primary Purpose**: Automatically format PHP code to match configured style preferences.

### Key Behaviors

- **DEFAULT**: Auto-fixes formatting (modifies files)
- **Reporting Only**: Use `--check` flag (no modifications)

### Common Usage

```bash
# Auto-fix formatting (DEFAULT - modifies files)
vendor/bin/sail bin mago format

# Check formatting without changes (reporting only)
vendor/bin/sail bin mago format --check

# Format specific files
vendor/bin/sail bin mago format app/Models/User.php

# Preview changes without applying (dry run)
vendor/bin/sail bin mago format --dry-run

# Format only staged git files
vendor/bin/sail bin mago format --staged
```

### Important Flags

- **NO FLAG** - Auto-fixes formatting (writes to files)
- `--check` - Reporting only, exits with code 0 if formatted, 1 if not (ideal for CI)
- `--dry-run` - Shows diff without modifying files
- `--staged` - Format files staged in git (for pre-commit hooks)

## Lint Command

**Primary Purpose**: Check PHP code against linting rules to identify style violations, code smells, and potential bugs.

### Key Behaviors

- **DEFAULT**: Reporting only (does NOT modify files)
- **Auto-fix**: Use `--fix` flag (modifies files)

### Common Usage

```bash
# Report linting issues (DEFAULT - no modifications)
vendor/bin/sail bin mago lint

# Auto-fix linting issues (modifies files)
vendor/bin/sail bin mago lint --fix

# Show only fixable issues (reporting)
vendor/bin/sail bin mago lint --fixable-only

# Preview fixes without applying
vendor/bin/sail bin mago lint --fix --dry-run

# Auto-fix and format after
vendor/bin/sail bin mago lint --fix --format-after-fix

# Lint specific files
vendor/bin/sail bin mago lint app/Http/Controllers/UserController.php

# Lint only staged git files
vendor/bin/sail bin mago lint --staged
```

### Important Flags

- **NO FLAG** - Reporting only (does NOT modify files)
- `--fix` - Apply automatic fixes (modifies files)
- `--fixable-only` - Filter output to show only auto-fixable issues
- `--dry-run` - Preview fixes without writing (requires `--fix`)
- `--unsafe` - Apply unsafe fixes (requires `--fix`, use with caution)
- `--potentially-unsafe` - Apply potentially unsafe fixes (requires `--fix`)
- `--format-after-fix` - Format files after fixing (requires `--fix`)
- `--staged` - Lint files staged in git

### Advanced Lint Options

```bash
# Explain a specific rule
vendor/bin/sail bin mago lint --explain no-empty

# List all enabled rules
vendor/bin/sail bin mago lint --list-rules

# Run only specific rules
vendor/bin/sail bin mago lint --only no-empty,prefer-while-loop

# Ignore baseline (report all issues)
vendor/bin/sail bin mago lint --ignore-baseline

# Generate new baseline
vendor/bin/sail bin mago lint --baseline baseline-lint.toml --generate-baseline
```

## Analyze Command

**Primary Purpose**: Perform deep static analysis including type checking, control flow analysis, and logical error detection.

### Key Behaviors

- **DEFAULT**: Reporting only (does NOT modify files)
- **Auto-fix**: Use `--fix` flag (modifies files)

### Common Usage

```bash
# Report analysis issues (DEFAULT - no modifications)
vendor/bin/sail bin mago analyze

# Auto-fix analysis issues (modifies files)
vendor/bin/sail bin mago analyze --fix

# Show only fixable issues (reporting)
vendor/bin/sail bin mago analyze --fixable-only

# Preview fixes without applying
vendor/bin/sail bin mago analyze --fix --dry-run

# Auto-fix and format after
vendor/bin/sail bin mago analyze --fix --format-after-fix

# Analyze specific files
vendor/bin/sail bin mago analyze app/Models/User.php

# Analyze only staged git files
vendor/bin/sail bin mago analyze --staged
```

### Important Flags

- **NO FLAG** - Reporting only (does NOT modify files)
- `--fix` - Apply automatic fixes (modifies files)
- `--fixable-only` - Filter output to show only auto-fixable issues
- `--dry-run` - Preview fixes without writing (requires `--fix`)
- `--unsafe` - Apply unsafe fixes (requires `--fix`, use with caution)
- `--potentially-unsafe` - Apply potentially unsafe fixes (requires `--fix`)
- `--format-after-fix` - Format files after fixing (requires `--fix`)
- `--staged` - Analyze files staged in git
- `--watch` - Continuous analysis (experimental)

## Configuration

Mago is configured in `mago.toml` at the project root:

- **Source paths**: `app/`, `database/factories/`, `database/seeders/`, `tests/`
- **PHP version**: 8.4.0
- **Formatter preset**: Pint (Laravel's formatter)
- **Linter baseline**: `baseline-lint.toml` (existing issues to ignore)
- **Analyzer baseline**: `baseline-analyze.toml` (existing issues to ignore)
- **Integrations**: Laravel and Pest

### Baselines

Baselines contain existing issues that are temporarily ignored. Do NOT add new issues to baselines without explicit user approval. Always fix new issues instead.

## Recommended Workflow After Code Changes

When you create or edit PHP files, follow this workflow. **Always scope to the files you changed** — either pass them explicitly or use the `MAGO_FILES` pattern from the Scope section.

If you know exactly which files you just edited, prefer passing them by name:

```bash
vendor/bin/sail bin mago format app/Models/Property.php app/Http/Controllers/PropertyController.php
```

Otherwise, derive the list from git:

```bash
MAGO_FILES=$(git diff --name-only -z --diff-filter=ACMR HEAD -- '*.php' '*.blade.php')
[ -z "$MAGO_FILES" ] && echo "No modified PHP files." && exit 0
```

### 1. **Format Code** (Auto-fix)

```bash
printf '%s' "$MAGO_FILES" | xargs -0 vendor/bin/sail bin mago format
```

- ✅ Automatically fixes formatting issues
- ✅ Modifies files in place
- Should produce no errors if successful
- If errors occur, report them to the user

### 2. **Lint Code** (Auto-fix)

```bash
printf '%s' "$MAGO_FILES" | xargs -0 vendor/bin/sail bin mago lint --fix
```

- ✅ Auto-fixes linting issues where possible
- ✅ Modifies files in place
- Reports any issues that cannot be auto-fixed
- If unfixable issues remain, report them with file:line references

**Alternative**: Check first, then fix if needed:

```bash
printf '%s' "$MAGO_FILES" | xargs -0 vendor/bin/sail bin mago lint
printf '%s' "$MAGO_FILES" | xargs -0 vendor/bin/sail bin mago lint --fix
```

### 3. **Analyze Code** (Check first, fix if auto-fixable)

```bash
printf '%s' "$MAGO_FILES" | xargs -0 vendor/bin/sail bin mago analyze
printf '%s' "$MAGO_FILES" | xargs -0 vendor/bin/sail bin mago analyze --fix
```

- Most analysis issues are **informational only** (no auto-fix)
- Some issues may have auto-fixes available
- Critical issues (type errors, undefined variables) should be fixed manually if no auto-fix available

### 4. **Report Results**

After running all three commands, provide a summary:

```
✓ Code formatted successfully
✓ Linting passed (X issues auto-fixed)
✓ Static analysis complete (Y issues found, Z auto-fixed)

Remaining issues to address:
- app/Models/User.php:45 - [issue description]
- app/Http/Controllers/UserController.php:102 - [issue description]
```

## Error Handling Strategy

### Format Errors

If `mago format` fails:

1. Report the error with file:line reference
2. Attempt to fix the underlying issue (syntax errors, parse errors)
3. Run format again after fixing

### Lint Errors

If `mago lint --fix` reports unfixable issues:

1. Report each issue with file:line reference
2. Attempt to fix the issues manually following the error messages
3. Run `mago lint --fix` again to verify fixes
4. If unable to fix, ask user for guidance

### Analysis Warnings

If `mago analyze` reports issues:

1. First try auto-fixing: `mago analyze --fix`
2. Report issues that cannot be auto-fixed with file:line references
3. Assess severity (errors vs warnings vs notes)
4. Fix critical issues manually (type errors, undefined variables)
5. Report non-critical warnings for user review
6. If uncertain about fix, ask user for guidance

## Best Practices

### DO:

- ✅ Run `mago format` after every PHP file change (auto-fixes) — **scoped to changed files**
- ✅ Run `mago lint --fix` to auto-fix linting issues — **scoped to changed files**
- ✅ Try `mago analyze --fix` first before manual fixes — **scoped to changed files**
- ✅ Pass the specific file paths you edited as arguments whenever possible
- ✅ Use `--staged` or the `MAGO_FILES` pattern when you don't have an explicit list
- ✅ Report all unfixable issues with clear file:line references
- ✅ Fix critical issues (type errors, syntax errors) immediately
- ✅ Use `--dry-run` to preview changes before applying
- ✅ Run full validation before considering a task complete

### DON'T:

- ❌ Run `mago format` / `lint` / `analyze` with no path argument and no `--staged` flag — that scans the whole codebase and produces unrelated diffs
- ❌ Pipe an empty file list into `xargs` without an emptiness check — that also falls back to the whole codebase
- ❌ Run Mago across the whole project unless the user explicitly asked for it
- ❌ Skip Mago validation after PHP changes
- ❌ Use `mago lint` without `--fix` as default (wastes time)
- ❌ Add new issues to baselines without user approval
- ❌ Ignore static analysis errors (especially type errors)
- ❌ Use `--check` flag on format unless explicitly validating
- ❌ Apply `--unsafe` fixes without understanding the implications
- ❌ Run Mago on non-PHP files (JavaScript, TypeScript, etc.)

## Integration with Other Tools

### Pint (Laravel's Formatter)

Mago's formatter uses the Pint preset for Laravel compatibility. Prefer Mago since it provides formatting + linting + analysis in one tool.

### Pest (Testing)

After fixing code quality issues, always run relevant tests:

```bash
vendor/bin/sail artisan test --compact --filter=UserTest
```

### Version Control

Run Mago before committing — always scoped to staged files:

```bash
vendor/bin/sail bin mago format --staged && vendor/bin/sail bin mago lint --staged --fix && vendor/bin/sail bin mago analyze --staged
```

The `--staged` flag is also the right choice for pre-commit hooks.

## Examples

### Example 1: After Creating a New Model

```bash
# You just created app/Models/Property.php

# 1. Format
vendor/bin/sail bin mago format app/Models/Property.php
# ✓ Formatted successfully

# 2. Lint with auto-fix
vendor/bin/sail bin mago lint --fix app/Models/Property.php
# ✓ Fixed 2 issues (unused import, missing return type)

# 3. Analyze with auto-fix attempt
vendor/bin/sail bin mago analyze --fix app/Models/Property.php
# ✓ No issues found

# Report to user:
✓ Property model formatted and validated
✓ Auto-fixed 2 linting issues
✓ No remaining issues
```

### Example 2: After Editing a Controller with Unfixable Issues

```bash
# You edited app/Http/Controllers/V3/PropertyController.php

# 1. Format
vendor/bin/sail bin mago format app/Http/Controllers/V3/PropertyController.php
# ✓ Formatted successfully

# 2. Lint with auto-fix
vendor/bin/sail bin mago lint --fix app/Http/Controllers/V3/PropertyController.php
# ⚠ Fixed 1 issue automatically
# ⚠ Found 1 unfixable issue:
#   - Line 45: Variable $query is defined but never used

# 3. Fix the unfixable issue manually
# (Remove the unused variable)

# 4. Run lint again
vendor/bin/sail bin mago lint --fix app/Http/Controllers/V3/PropertyController.php
# ✓ No issues found

# 5. Analyze with auto-fix attempt
vendor/bin/sail bin mago analyze --fix app/Http/Controllers/V3/PropertyController.php
# ✓ No issues found

# Report to user:
✓ PropertyController formatted, linted, and analyzed
✓ Auto-fixed 1 linting issue
✓ Manually fixed 1 unused variable
✓ No remaining issues
```

### Example 3: Full Project Validation (only on explicit user request)

Only run unscoped Mago commands when the user explicitly asks for a whole-codebase pass (e.g. "run mago on everything", "format the entire project"). Confirm first if there's any ambiguity, since the resulting diff can be huge.

```bash
# User explicitly requested validation of all PHP code

# 1. Format all files
vendor/bin/sail bin mago format
# ✓ Formatted 152 files

# 2. Lint with auto-fix
vendor/bin/sail bin mago lint --fix
# ✓ Fixed 8 issues automatically
# ✓ No unfixable issues

# 3. Analyze with auto-fix attempt
vendor/bin/sail bin mago analyze --fix
# ✓ Fixed 2 issues automatically
# ℹ Found 3 warnings (see baseline-analyze.toml)

# Report to user:
✓ Formatted 152 files
✓ Auto-fixed 8 linting issues
✓ Auto-fixed 2 analysis issues
ℹ 3 analysis warnings exist in baseline (pre-existing issues)
✓ All code passes quality checks
```

### Example 4: Using Dry Run to Preview Changes

```bash
# Check what lint would fix before applying
vendor/bin/sail bin mago lint --fixable-only
# Shows: 5 fixable issues found

# Preview the fixes without applying
vendor/bin/sail bin mago lint --fix --dry-run
# Shows diff of changes that would be made

# Apply the fixes
vendor/bin/sail bin mago lint --fix
# ✓ Fixed 5 issues
```

## Common Issues

### Issue: "Cannot find mago binary"

**Solution**: Ensure running through Sail: `vendor/bin/sail bin mago` (not just `mago`)

### Issue: "Permission denied"

**Solution**: Mago runs in Docker via Sail, permissions should be automatic

### Issue: "Parse error in file"

**Solution**: Fix syntax errors first before running Mago validation

### Issue: "Too many analysis warnings"

**Solution**: Focus on new code, existing baseline issues are tracked separately in `baseline-analyze.toml`

### Issue: "Lint reports issues but --fix doesn't fix them"

**Solution**: Not all issues are auto-fixable. Use `mago lint --fixable-only` to see which ones can be fixed automatically. Fix others manually.

## Quick Reference

> ⚠️ Every command below is scoped to specific files, `--staged`, or a derived `$MAGO_FILES` list. Do not strip the scoping unless the user explicitly asked for a whole-codebase run.

```bash
# === MOST COMMON WORKFLOW (scoped to modified + staged files) ===
MAGO_FILES=$(git diff --name-only -z --diff-filter=ACMR HEAD -- '*.php' '*.blade.php')
[ -z "$MAGO_FILES" ] && echo "No modified PHP files." || {
    printf '%s' "$MAGO_FILES" | xargs -0 vendor/bin/sail bin mago format && \
    printf '%s' "$MAGO_FILES" | xargs -0 vendor/bin/sail bin mago lint --fix && \
    printf '%s' "$MAGO_FILES" | xargs -0 vendor/bin/sail bin mago analyze
}

# === TARGET SPECIFIC FILES (preferred when you know what you edited) ===
vendor/bin/sail bin mago format app/Models/User.php
vendor/bin/sail bin mago lint --fix app/Http/Controllers/UserController.php
vendor/bin/sail bin mago analyze app/Models/User.php

# === GIT STAGED FILES ===
vendor/bin/sail bin mago format --staged
vendor/bin/sail bin mago lint --staged --fix
vendor/bin/sail bin mago analyze --staged

# === FORMAT FLAGS ===
vendor/bin/sail bin mago format <paths>            # Auto-fix formatting (modifies files)
vendor/bin/sail bin mago format <paths> --check    # Report only (no modifications)
vendor/bin/sail bin mago format <paths> --dry-run  # Preview changes

# === LINT FLAGS ===
vendor/bin/sail bin mago lint <paths>                  # Report only (no modifications)
vendor/bin/sail bin mago lint <paths> --fix            # Auto-fix issues (modifies files)
vendor/bin/sail bin mago lint <paths> --fixable-only   # Show only auto-fixable issues
vendor/bin/sail bin mago lint <paths> --fix --dry-run  # Preview fixes
vendor/bin/sail bin mago lint --list-rules             # Show all enabled rules (no scope needed)
vendor/bin/sail bin mago lint --explain no-empty       # Explain specific rule (no scope needed)

# === ANALYZE FLAGS ===
vendor/bin/sail bin mago analyze <paths>                  # Report only (no modifications)
vendor/bin/sail bin mago analyze <paths> --fix            # Auto-fix issues (modifies files)
vendor/bin/sail bin mago analyze <paths> --fixable-only   # Show only auto-fixable issues
vendor/bin/sail bin mago analyze <paths> --fix --dry-run  # Preview fixes
```

## Skill Activation Triggers

This skill should activate when the user's request or your work involves:

- Keywords: "format", "lint", "analyze", "validate", "check code quality", "mago"
- Creating files: "create model", "add controller", "new migration", "write class"
- Editing files: "update", "modify", "change", "refactor", "fix"
- Code review: "review code", "check for issues", "validate code"
- Before commits: "ready to commit", "prepare commit", "pre-commit"
- Any PHP file work in general

## Related Skills

- `spatie-laravel-php-standards` - Coding standards (run before Mago for compliance)
- `laravel-best-practices` - Laravel patterns (apply when writing code)
- `pest-testing` - Run tests after fixing Mago issues

## Summary: Report vs Auto-Fix

| Command        | Default Behavior | Auto-Fix Flag | Reporting Only Flag |
| -------------- | ---------------- | ------------- | ------------------- |
| `mago format`  | ✅ Auto-fixes    | N/A (default) | `--check`           |
| `mago lint`    | 📊 Reports only  | `--fix`       | N/A (default)       |
| `mago analyze` | 📊 Reports only  | `--fix`       | N/A (default)       |

**Key Takeaway**: Format auto-fixes by default. Lint and analyze report by default, use `--fix` to auto-fix.
