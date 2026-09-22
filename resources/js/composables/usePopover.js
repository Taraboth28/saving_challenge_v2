import { nextTick, onBeforeUnmount, ref, watch } from 'vue';

const GAP = 6;
const VIEWPORT_MARGIN = 8;

/**
 * Floating panel anchored to a trigger element (used by BaseSelect and DatePicker).
 *
 * The panel is meant to be teleported to <body> and positioned with `style` (position: fixed),
 * so it is never clipped by scrolling containers such as modals. It opens below the trigger,
 * or above when there is not enough room, and closes on outside press.
 */
export function usePopover({ minWidth = 0 } = {}) {
    const open = ref(false);
    const trigger = ref(null);
    const panel = ref(null);
    const placement = ref('bottom');
    const style = ref({ position: 'fixed', visibility: 'hidden' });

    function position() {
        if (!trigger.value || !panel.value) {
            return;
        }

        const rect = trigger.value.getBoundingClientRect();
        const width = Math.min(Math.max(rect.width, minWidth), window.innerWidth - VIEWPORT_MARGIN * 2);
        // Apply the final width before measuring: the panel's height depends on it.
        panel.value.style.width = `${width}px`;
        const panelHeight = panel.value.offsetHeight;
        const spaceBelow = window.innerHeight - rect.bottom;
        const openAbove = spaceBelow < panelHeight + GAP + VIEWPORT_MARGIN && rect.top > spaceBelow;
        const preferredTop = openAbove ? rect.top - panelHeight - GAP : rect.bottom + GAP;

        placement.value = openAbove ? 'top' : 'bottom';
        style.value = {
            position: 'fixed',
            // Stay on screen even when neither side has enough room.
            top: `${Math.max(VIEWPORT_MARGIN, Math.min(preferredTop, window.innerHeight - panelHeight - VIEWPORT_MARGIN))}px`,
            left: `${Math.min(Math.max(VIEWPORT_MARGIN, rect.left), window.innerWidth - width - VIEWPORT_MARGIN)}px`,
            width: `${width}px`,
        };
    }

    function onPointerDown(event) {
        if (!trigger.value?.contains(event.target) && !panel.value?.contains(event.target)) {
            close();
        }
    }

    async function show() {
        style.value = { position: 'fixed', visibility: 'hidden' };
        open.value = true;
        await nextTick();
        position();
    }

    function close({ focusTrigger = false } = {}) {
        if (!open.value) {
            return;
        }

        open.value = false;

        if (focusTrigger) {
            trigger.value?.focus();
        }
    }

    function toggleListeners(enabled) {
        const method = enabled ? 'addEventListener' : 'removeEventListener';

        document[method]('pointerdown', onPointerDown, true);
        window[method]('resize', position);
        window[method]('scroll', position, true);
    }

    watch(open, toggleListeners);
    onBeforeUnmount(() => toggleListeners(false));

    return { open, trigger, panel, placement, style, show, close, position };
}
