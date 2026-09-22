<script setup>
/**
 * Inline stroke icons (24×24). Add new icons to the `paths` map.
 */
const props = defineProps({
    name: { type: String, required: true },
    size: { type: [Number, String], default: 20 },
});

const paths = {
    dashboard: 'M3 13h8V3H3v10Zm0 8h8v-6H3v6Zm10 0h8V11h-8v10Zm0-18v6h8V3h-8Z',
    target: 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0-4a5 5 0 1 0 0-10 5 5 0 0 0 0 10Zm0-4a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z',
    report: 'M4 20V10m6 10V4m6 16v-8m4 8H2',
    plus: 'M12 5v14M5 12h14',
    edit: 'M4 20h4L19 9l-4-4L4 16v4Zm9-13 4 4',
    trash: 'M4 7h16M10 11v6m4-6v6M6 7l1 13h10l1-13M9 7V4h6v3',
    arrowLeft: 'M19 12H5m6-6-6 6 6 6',
    arrowUp: 'M12 19V5m-6 6 6-6 6 6',
    arrowDown: 'M12 5v14m6-6-6 6-6-6',
    check: 'm5 12 5 5L20 7',
    close: 'M6 6l12 12M18 6 6 18',
    calendar: 'M4 6h16v14H4V6Zm0 4h16M8 3v4m8-4v4',
    wallet: 'M3 7h16a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Zm0 0 12-4 2 4m0 7h.01',
    flag: 'M5 21V4h11l-2 4 2 4H5',
    clock: 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0-13v4l3 2',
    menu: 'M4 6h16M4 12h16M4 18h16',
    alert: 'M12 9v4m0 4h.01M10.3 3.9 2.4 18a2 2 0 0 0 1.7 3h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z',
};
</script>

<template>
    <svg
        :width="props.size"
        :height="props.size"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
    >
        <path :d="paths[props.name]" />
    </svg>
</template>
