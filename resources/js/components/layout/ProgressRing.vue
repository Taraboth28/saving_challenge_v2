<script setup>
import { computed } from 'vue';

/** Small circular progress indicator for dark surfaces. */
const props = defineProps({
    value: { type: Number, default: 0 },
    size: { type: Number, default: 22 },
});

const stroke = 3;
const radius = computed(() => (props.size - stroke) / 2);
const circumference = computed(() => 2 * Math.PI * radius.value);
const offset = computed(() => circumference.value * (1 - Math.min(100, Math.max(0, props.value)) / 100));
</script>

<template>
    <svg :width="size" :height="size" :viewBox="`0 0 ${size} ${size}`" class="-rotate-90" aria-hidden="true">
        <circle :cx="size / 2" :cy="size / 2" :r="radius" fill="none" stroke="rgb(255 255 255 / 0.15)" :stroke-width="stroke" />
        <circle
            :cx="size / 2"
            :cy="size / 2"
            :r="radius"
            fill="none"
            stroke="var(--color-orange-500)"
            :stroke-width="stroke"
            stroke-linecap="round"
            :stroke-dasharray="circumference"
            :stroke-dashoffset="offset"
            class="transition-[stroke-dashoffset] duration-700"
        />
    </svg>
</template>
