# ADR 0014: ConfirmDialog Stacks Above a Parent Dialog

- **Status:** Accepted
- **Date:** May 2026

## Context

`resources/js/components/ui/custom/confirm-dialog/ConfirmDialog.vue` is
an imperative confirmation prompt: callers obtain
`confirm({ title, description, footnote, callback, variant })` from
`injectConfirmDialogContext()` and trigger a confirm/cancel modal.

Many of the destructive flows that need it — delete team, leave team,
remove member, cancel subscription — are invoked from inside settings
screens that themselves render as `SmartDialog` content. The shadcn /
reka-ui `Dialog` primitive does not auto-stack: a second dialog opened
from inside a first one renders at the same z-index as its parent
(`z-50`), and the parent's content can bleed through the new overlay.

We need to decide whether `confirm()` is callable from inside another
dialog, or whether callers must close the parent first.

## Decision

**`ConfirmDialog` is supported inside an already-open dialog.**
Its overlay is rendered at `z-[60]`, one layer above the default
`z-50` used by `DialogContent` / `SmartDialogContent`. Call sites
inside a `SmartDialog` may invoke `confirm({ ... })` directly without
managing the parent dialog's open state.

The override lives on the inner `SmartDialogContent`:

```vue
<SmartDialogContent overlay-class="z-[60]">
```

## Alternatives Considered

**Close the parent dialog before showing the prompt.** Rejected: every
destructive call site would have to coordinate two pieces of state
(close parent, await DOM transition, then call `confirm()`), and the
cancel path would have to re-open the parent, losing scroll/focus
context. The mental model "confirm in place" is the better default for
settings flows.

**Render `ConfirmDialog` outside the dialog tree (e.g. a teleport at
app root) and rely on portal order.** Rejected: reka-ui `Dialog`
already portals, but its overlay z-index is fixed at `z-50` regardless
of mount point — without an explicit override, two portaled overlays
collide visually. Moving the mount point doesn't solve the stacking
problem; only the z-index hint does.

**Use shadcn's `AlertDialog` primitive instead of a custom layer.**
Rejected: `AlertDialog` is declarative (one instance per call site)
and would require every destructive button to wire its own
`open` ref, content, and handlers. The imperative
`confirm({ callback })` API is cheaper at every call site and is
the surface this kit standardises on.

## Consequences

- The kit assumes there is exactly one nesting level of dialogs.
  A *third* dialog opened from inside a `ConfirmDialog` would
  collide at `z-[60]` and require another bump. This is acceptable:
  three-deep dialog flows are themselves a UX smell to address at the
  flow level, not by adding more z-index layers.
- The `z-[60]` class is load-bearing — removing it silently breaks
  nested confirmations (the prompt opens but the parent dialog's
  scrim renders on top of it). Future class refactors must preserve
  it; this ADR is the canonical reason it exists.
- Only the *overlay* is bumped. `SmartDialogContent` itself inherits
  `z-50` from the underlying `DialogScrollContent`. Stacking still
  works because each Dialog instance portals to its own root and
  later DOM order wins at equal z — but consumers should not rely on
  the content z-index for layering decisions.
- Mounting point: `ConfirmDialog` must be provided high enough in
  the tree to be reachable from every consumer that opens a dialog.
  Wrapping it around the authenticated app layout (or the persistent
  shell) is the intended placement.
