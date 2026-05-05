# Authorization checks use permissions, never roles

All policy, gate, middleware, controller, and template authorization checks are expressed as permission checks (`$user->can('user.update')`). We never branch on a role (`$user->hasRole('admin')`).

## Why

Role-to-permission mapping is a seeder/data concern. Adding a new role, splitting an existing one, or shifting which role grants which capability must never require touching auth-check code. Role-based branches scattered through the codebase are the standard reason RBAC systems calcify and become impossible to evolve.

## Consequences

- Single-permission roles (`Super admin` → `admin`, `Tester` → `test`) still get checked by their permission, not their role. This feels redundant but preserves the invariant.
- The `RoleName` enum exists for seeding and assignment only — it must not appear in any authorization branch.
- Reviewers should treat any `hasRole`/`hasAnyRole` call site in non-seeder code as a defect.
