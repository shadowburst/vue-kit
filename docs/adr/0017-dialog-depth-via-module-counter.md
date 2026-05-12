# ADR 0017: Dialog Depth via Module Counter, LIFO Close Contract

- **Status:** Accepted — supersedes ADR-0014
- **Date:** May 2026

## Context

`SmartDialogContent` is the kit's single source of dialog visuals
(reka `Dialog` at `md`+, `vaul-vue` `Drawer` below). Both portal to
`body` with overlays fixed at `z-50`. Several flows nest dialogs:

- `ConfirmDialog` opened from inside a settings `SmartDialog`
  (ADR-0014).
- `InertiaModal` (the kit's wrapper around `@inertiaui/modal-vue`'s
  `HeadlessModal`) is itself a `SmartDialog`, and its pages can open
  further `InertiaModal`s via `InertiaModalLink`, or a `ConfirmDialog`
  on top.

ADR-0014 solved the two-layer case with a hard-coded
`overlay-class="z-[60]"` on `ConfirmDialog`'s inner
`SmartDialogContent`. That doesn't generalize: a three-deep flow
(parent settings dialog → stacked `InertiaModal` → confirm) collides
because there is no rule that scales with nesting depth.

## Decision

**Every `SmartDialogContent` self-assigns a depth on mount via a
module-level counter, and renders overlay + content at
`zIndex: 50 + depth` via inline style.**

```ts
// SmartDialogContent.vue (module scope)
let openCount = 0;

// setup
const depth = openCount;
onMounted(() => { openCount += 1; });
onBeforeUnmount(() => { openCount -= 1; });
```

The depth is frozen at mount. `:style="{ zIndex: 50 + depth }"` is
bound on both the overlay and content elements.

**Inline style, not Tailwind classes.** Tailwind 4's JIT scanner only
sees literal class strings; `z-[${50 + depth}]` would silently
produce nothing. A static array (`['z-50','z-51',…]`) would work but
caps the supported depth at compile time.

**LIFO close order is contractual.** Dialogs must close in reverse
open order. With monotonic increment/decrement, an out-of-order close
desyncs the counter and the next dialog opens at a colliding
z-index. The kit accepts this constraint because nested dialogs in
practice always unmount innermost-first (closing a parent unmounts
its children's portal hosts via the parent's own teardown), and
authors do not surface "close parent before child" affordances.

## Alternatives Considered

**Provide/inject a parent-supplied `depth` prop.** Rejected: every
nest-aware component (`InertiaModal`, `ConfirmDialog`, future) would
have to call `provideDialogDepth(parentDepth + 1)`. Module-level
state in the single primitive that renders dialogs is cheaper and
keeps the rule in one file.

**Active-set tracking (`Set<number>`, pick `max + 1`).** Survives
out-of-order close, but adds reactivity and a `Math.max` for a case
that the kit's nesting flows do not produce. Documented LIFO is
sufficient and simpler.

**Monotonic counter that never decrements.** Avoids collision under
any close order, but z-index grows unboundedly across a session.
Functionally fine; aesthetically gross in DevTools.

## Consequences

- ADR-0014's `z-[60]` override on `ConfirmDialog` is removed; the
  same component now picks `z-51` automatically when opened from
  inside another dialog. ADR-0014 is marked superseded.
- `SmartDialogContent` now carries a tiny piece of cross-instance
  state. Tests must cover mount/unmount accounting because regressions
  are silent (visuals look fine until two dialogs actually stack).
- Out-of-order close is an unenforced contract. Code review for new
  flows that manually close parent dialogs from non-leaf positions
  must verify the inner dialog is closed first. If a real use case
  emerges, revisit with the active-set approach above.
- The depth mechanism is **internal to `SmartDialogContent`**. No
  prop, no inject, no consumer-facing API. This is deliberate: nested
  callers (`InertiaModal`, `ConfirmDialog`) get correct stacking for
  free, without each new caller having to remember to plumb a depth
  through.
