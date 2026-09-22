import { onBeforeUnmount, ref, watch } from 'vue';

/**
 * App-wide "savings data changed" signal, so a save made in one place (e.g. the
 * navigation's Add saving dialog) refreshes whatever page is currently open.
 */
const version = ref(0);

export function notifyDataChanged() {
    version.value++;
}

/** Run `callback` whenever data changes elsewhere; stops automatically with the component. */
export function onDataChanged(callback) {
    const stop = watch(version, () => callback());

    onBeforeUnmount(stop);
}
