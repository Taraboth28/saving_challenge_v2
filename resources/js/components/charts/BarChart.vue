<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { niceTicks } from './chartUtils';

/**
 * Vertical (grouped) bar chart drawn in SVG.
 *
 * data:   [{ label: 'May', values: { deposits: 120, withdrawals: 20 } }, ...]
 * series: [{ key: 'deposits', label: 'Deposits', color: 'var(--color-series-1)' }, ...]
 */
const props = defineProps({
    data: { type: Array, required: true },
    series: { type: Array, required: true },
    height: { type: Number, default: 240 },
    formatValue: { type: Function, default: (value) => String(value) },
    formatAxis: { type: Function, default: null },
    ariaLabel: { type: String, default: 'Bar chart' },
});

const container = ref(null);
const width = ref(600);
const hoveredIndex = ref(null);
let observer;

onMounted(() => {
    observer = new ResizeObserver(([entry]) => (width.value = Math.max(240, entry.contentRect.width)));
    observer.observe(container.value);
});

onBeforeUnmount(() => observer?.disconnect());

const padding = { top: 12, right: 8, bottom: 28, left: 56 };

const plot = computed(() => ({
    width: width.value - padding.left - padding.right,
    height: props.height - padding.top - padding.bottom,
}));

const allValues = computed(() => props.data.flatMap((row) => props.series.map((serie) => row.values[serie.key] ?? 0)));
const ticks = computed(() => niceTicks(Math.min(...allValues.value, 0), Math.max(...allValues.value, 0)));
const domain = computed(() => [ticks.value[0], ticks.value[ticks.value.length - 1]]);

const y = (value) => padding.top + plot.value.height * (1 - (value - domain.value[0]) / (domain.value[1] - domain.value[0]));

const bandWidth = computed(() => plot.value.width / Math.max(1, props.data.length));

const bars = computed(() => {
    const gap = 2;
    const groupWidth = Math.min(bandWidth.value * 0.7, 28 * props.series.length);
    const barWidth = Math.max(2, (groupWidth - gap * (props.series.length - 1)) / props.series.length);

    return props.data.flatMap((row, rowIndex) =>
        props.series.map((serie, serieIndex) => {
            const value = row.values[serie.key] ?? 0;
            const top = Math.min(y(value), y(0));
            const height = Math.abs(y(value) - y(0));
            const x = padding.left + bandWidth.value * rowIndex + (bandWidth.value - groupWidth) / 2 + serieIndex * (barWidth + gap);

            return { key: `${rowIndex}-${serie.key}`, rowIndex, x, top, height, width: barWidth, value, color: serie.color };
        }),
    );
});

/** Path for a bar with 4px rounded corners on the data end only (anchored to the baseline). */
function barPath(bar) {
    const radius = Math.min(4, bar.width / 2, bar.height);
    const { x, top, width: w, height: h } = bar;

    if (h === 0) {
        return '';
    }

    if (bar.value >= 0) {
        return `M${x},${top + h}V${top + radius}Q${x},${top} ${x + radius},${top}H${x + w - radius}Q${x + w},${top} ${x + w},${top + radius}V${top + h}Z`;
    }

    return `M${x},${top}V${top + h - radius}Q${x},${top + h} ${x + radius},${top + h}H${x + w - radius}Q${x + w},${top + h} ${x + w},${top + h - radius}V${top}Z`;
}

const labelEvery = computed(() => Math.ceil(props.data.length / Math.max(1, Math.floor(plot.value.width / 56))));

const tooltip = computed(() => {
    if (hoveredIndex.value === null) {
        return null;
    }

    const row = props.data[hoveredIndex.value];
    const center = padding.left + bandWidth.value * (hoveredIndex.value + 0.5);

    return {
        row,
        left: Math.min(Math.max(center, 90), width.value - 90),
    };
});
</script>

<template>
    <div class="relative w-full min-w-0">
        <!-- The SVG is absolutely positioned so its pixel width never forces the layout wider than the container. -->
        <div ref="container" class="relative w-full" :style="{ height: `${height}px` }">
        <svg :width="width" :height="height" role="img" :aria-label="ariaLabel" class="absolute inset-0 overflow-visible" @mouseleave="hoveredIndex = null">
            <!-- Grid + y axis -->
            <g class="text-[11px]">
                <g v-for="tick in ticks" :key="tick">
                    <line :x1="padding.left" :x2="width - padding.right" :y1="y(tick)" :y2="y(tick)" :stroke="tick === 0 ? 'var(--color-line-strong)' : 'var(--color-line)'" stroke-width="1" />
                    <text :x="padding.left - 8" :y="y(tick)" text-anchor="end" dominant-baseline="middle" fill="var(--color-ink-muted)">
                        {{ (formatAxis ?? formatValue)(tick) }}
                    </text>
                </g>
            </g>

            <!-- Hover band -->
            <rect
                v-if="hoveredIndex !== null"
                :x="padding.left + bandWidth * hoveredIndex"
                :y="padding.top"
                :width="bandWidth"
                :height="plot.height"
                fill="var(--color-surface-muted)"
            />

            <!-- Bars -->
            <path v-for="bar in bars" :key="bar.key" :d="barPath(bar)" :fill="bar.color" />

            <!-- X labels -->
            <text
                v-for="(row, index) in data"
                v-show="index % labelEvery === 0"
                :key="row.label"
                :x="padding.left + bandWidth * (index + 0.5)"
                :y="height - 8"
                text-anchor="middle"
                class="text-[11px]"
                fill="var(--color-ink-muted)"
            >
                {{ row.label }}
            </text>

            <!-- Hit targets (full band height, larger than the marks) -->
            <rect
                v-for="(row, index) in data"
                :key="`hit-${row.label}`"
                :x="padding.left + bandWidth * index"
                :y="padding.top"
                :width="bandWidth"
                :height="plot.height"
                fill="transparent"
                @mouseenter="hoveredIndex = index"
                @focus="hoveredIndex = index"
                @blur="hoveredIndex = null"
            />
        </svg>
        </div>

        <div
            v-if="tooltip"
            class="pointer-events-none absolute top-0 z-10 min-w-40 -translate-x-1/2 rounded-lg border border-line bg-surface px-3 py-2 text-xs shadow-lg"
            :style="{ left: `${tooltip.left}px` }"
        >
            <p class="mb-1 font-semibold text-ink">{{ tooltip.row.tooltipLabel ?? tooltip.row.label }}</p>
            <p v-for="serie in series" :key="serie.key" class="flex items-center justify-between gap-4 text-ink-soft">
                <span class="flex items-center gap-1.5">
                    <span class="size-2 rounded-full" :style="{ background: serie.color }" />
                    {{ serie.label }}
                </span>
                <span class="font-medium text-ink tabular-nums">{{ formatValue(tooltip.row.values[serie.key] ?? 0) }}</span>
            </p>
        </div>

        <!-- Legend (only needed for 2+ series; a single series is named by the card title) -->
        <div v-if="series.length > 1" class="mt-3 flex flex-wrap gap-4 text-xs text-ink-soft">
            <span v-for="serie in series" :key="serie.key" class="flex items-center gap-1.5">
                <span class="size-2.5 rounded-sm" :style="{ background: serie.color }" />
                {{ serie.label }}
            </span>
        </div>

        <!-- Screen-reader table view -->
        <table class="sr-only">
            <caption>{{ ariaLabel }}</caption>
            <thead>
                <tr>
                    <th scope="col">Period</th>
                    <th v-for="serie in series" :key="serie.key" scope="col">{{ serie.label }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in data" :key="row.label">
                    <th scope="row">{{ row.tooltipLabel ?? row.label }}</th>
                    <td v-for="serie in series" :key="serie.key">{{ formatValue(row.values[serie.key] ?? 0) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
