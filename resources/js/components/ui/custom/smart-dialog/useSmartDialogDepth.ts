import { onBeforeUnmount, onMounted } from 'vue';

// Module-level counter — every mounted SmartDialogContent occupies one slot.
// Depth captured at setup is frozen for the instance's lifetime.
// LIFO close order is contractual; out-of-order close collides at the next open.
// See ADR-0017.
let openCount = 0;

export function useSmartDialogDepth() {
    const depth = openCount;
    const zIndex = 50 + depth;

    onMounted(() => {
        openCount += 1;
    });

    onBeforeUnmount(() => {
        openCount -= 1;
    });

    return { depth, zIndex };
}

export function __resetSmartDialogDepthForTests() {
    openCount = 0;
}
