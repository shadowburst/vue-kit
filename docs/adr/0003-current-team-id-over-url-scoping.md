# Team scope from `users.current_team_id`, not URL prefix

Team-scoped pages render against `auth()->user()->current_team_id`. There is no `/t/{team}` URL prefix. A `SetCurrentTeam` middleware on the auth group resolves the active team and calls Spatie's `setPermissionsTeamId`. Switching teams is a `PUT /current-team` endpoint that updates the column.

## Considered options

- **URL-based scoping** (`/t/{team}/...` with implicit route-model binding). Deep-linkable, no hidden state, multiple tabs in different teams work naturally. Rejected because it forces a slug strategy with reserved-word handling and pushes team context into every route definition.
- **`current_team_id` on the user** (chosen, Jetstream-style). Single active team per session; team-scoped pages are identical URLs across teams.

## Consequences

- Multiple tabs cannot view different teams simultaneously — switching one tab switches them all on next request.
- `current_team_id` can go stale; `SetCurrentTeam` middleware self-heals to the user's first available team and member-removal/team-deletion flows cascade-clear it.
- The slug column on `Team` is kept (via Spatie Sluggable) for non-routing uses (team switcher UI, future invitation links), even though it isn't load-bearing for the auth flow.
- Reverting to URL-based scoping later requires reworking every team-aware route, the middleware, and the team-switching UX — non-trivial but bounded.
