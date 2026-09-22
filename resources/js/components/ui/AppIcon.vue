<script setup>
import { computed } from 'vue';

/**
 * Inline stroke icons on a 24×24 grid (rounded 2px lines, Lucide-style).
 * Each icon is a list of path strokes. Name icons by what they depict and
 * pick them by what the label means (e.g. "Remaining" → hourglass).
 */
const props = defineProps({
    name: { type: String, required: true },
    size: { type: [Number, String], default: 20 },
});

const CIRCLE = 'M22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z';
const CALENDAR = ['M8 2v4', 'M16 2v4', 'M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z', 'M3 10h18'];

const icons = {
    // Brand & navigation
    piggyBank: [
        'M19 5c-1.5 0-2.8 1.4-3 2-3.5-1.5-11-.3-11 5 0 1.8 0 3 2 4.5V20h4v-2h3v2h4v-4c1-.5 1.7-1 2-2h2v-4h-2c0-1-.5-1.5-1-2V5Z',
        'M2 9v1a2 2 0 0 0 2 2h1',
        'M16 11h.01',
    ],
    layoutDashboard: [
        'M4 3h5a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z',
        'M15 3h5a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-5a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z',
        'M15 12h5a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1h-5a1 1 0 0 1-1-1v-7a1 1 0 0 1 1-1Z',
        'M4 16h5a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1Z',
    ],
    target: ['M20.9 10.2A9 9 0 1 1 13.8 3.1', 'M16.9 11.3a5 5 0 1 1-4.2-4.2', 'M12 12l7-7', 'M19 2v3h3'],
    chartColumn: ['M3 3v16a2 2 0 0 0 2 2h16', 'M8 17v-4', 'M13 17V8', 'M18 17v-7'],
    menu: ['M4 6h16', 'M4 12h16', 'M4 18h16'],
    close: ['M18 6 6 18', 'm6 6 12 12'],
    sun: [
        'M16 12a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z',
        'M12 2v2',
        'M12 20v2',
        'm4.93 4.93 1.41 1.41',
        'm17.66 17.66 1.41 1.41',
        'M2 12h2',
        'M20 12h2',
        'm6.34 17.66-1.41 1.41',
        'm19.07 4.93-1.41 1.41',
    ],
    moon: ['M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z'],

    // Money & figures
    wallet: [
        'M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1',
        'M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4',
    ],
    flag: ['M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1Z', 'M4 22v-7'],
    hourglass: [
        'M5 22h14',
        'M5 2h14',
        'M17 22v-4.17a2 2 0 0 0-.59-1.42L12 12l-4.41 4.41A2 2 0 0 0 7 17.83V22',
        'M7 2v4.17a2 2 0 0 0 .59 1.42L12 12l4.41-4.41A2 2 0 0 0 17 6.17V2',
    ],
    listChecks: ['m3 17 2 2 4-4', 'm3 7 2 2 4-4', 'M13 6h8', 'M13 12h8', 'M13 18h8'],
    receipt: [
        'M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z',
        'M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8',
        'M12 17.5v-11',
    ],
    circlePlus: [CIRCLE, 'M8 12h8', 'M12 8v8'],
    circleMinus: [CIRCLE, 'M8 12h8'],

    // Status
    circleCheck: [CIRCLE, 'm9 12 2 2 4-4'],
    trendingUp: ['m22 7-8.5 8.5-5-5L2 17', 'M16 7h6v6'],
    calendarX: [...CALENDAR, 'm14 14-4 4', 'm10 14 4 4'],
    alertTriangle: ['m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3', 'M12 9v4', 'M12 17h.01'],
    inbox: [
        'M22 12h-6l-2 3h-4l-2-3H2',
        'M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11Z',
    ],

    // Actions & controls
    plus: ['M5 12h14', 'M12 5v14'],
    pencil: [
        'M21.17 6.81a2.83 2.83 0 0 0-4-4L3.84 16.17a2 2 0 0 0-.5.83l-1.32 4.35a.5.5 0 0 0 .62.62l4.35-1.32a2 2 0 0 0 .83-.5Z',
        'm15 5 4 4',
    ],
    trash: ['M3 6h18', 'M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6', 'M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2', 'M10 11v6', 'M14 11v6'],
    check: ['M20 6 9 17l-5-5'],
    calendar: CALENDAR,
    arrowLeft: ['m12 19-7-7 7-7', 'M19 12H5'],
    chevronDown: ['m6 9 6 6 6-6'],
    chevronLeft: ['m15 18-6-6 6-6'],
    chevronRight: ['m9 18 6-6-6-6'],
};

const strokes = computed(() => {
    if (!icons[props.name] && import.meta.env.DEV) {
        console.warn(`[AppIcon] Unknown icon "${props.name}".`);
    }

    return icons[props.name] ?? [];
});
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
        focusable="false"
    >
        <path v-for="(d, index) in strokes" :key="index" :d="d" />
    </svg>
</template>
