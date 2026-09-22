<script setup>
import { computed, ref } from 'vue';

/**
 * Donut chart for a small part-to-whole comparison (≤ 3 segments).
 *
 * segments: [{ key: 'active', label: 'Active', value: 3, color: 'var(--color-series-1)' }, ...]
 */
const props = defineProps({
    segments: { type: Array, required: true },
    centerLabel: { type: String, default: '' },
    centerValue: { type: String, default: '' },
    formatValue: { type: Function, default: (value) => String(value) },
    ariaLabel: { type: String, default: 'Donut chart' },
});

const size = 180;
const stroke = 26;
const radius = (size - stroke) / 2;
const circumference = 2 * Math.PI * radius;
const hoveredKey = ref(null);

const total = computed(() => props.segments.reduce((sum, segment) => sum + segment.value, 0));

const arcs = computed(() => {
    let offset = 0;
    // 2px surface gap between segments (only when more than one is visible).
    const gap = props.segments.filter((segment) => segment.value > 0).length > 1 ? 2 : 0;

    return props.segments.map((segment) => {
        const length = total.value > 0 ? (segment.value / total.value) * circumference : 0;
        const arc = {
            ...segment,
            dash: `${Math.max(0, length - gap)} ${circumference}`,
            offset: -offset,
            share: total.value > 0 ? Math.round((segment.value / total.value) * 100) : 0,
        };
        offset += length;

        return arc;
    });
});

const hovered = computed(() => arcs.value.find((arc) => arc.key === hoveredKey.value));
</script>

<template>
    <div class="@container">
    <div class="flex flex-col items-center gap-5 @md:flex-row @md:justify-center">
        <div class="relative shrink-0">
            <svg :width="size" :height="size" :viewBox="`0 0 ${size} ${size}`" role="img" :aria-label="ariaLabel" class="-rotate-90">
                <circle :cx="size / 2" :cy="size / 2" :r="radius" fill="none" stroke="#f1f5f9" :stroke-width="stroke" />
                <circle
                    v-for="arc in arcs"
                    :key="arc.key"
                    :cx="size / 2"
                    :cy="size / 2"
                    :r="radius"
                    fill="none"
                    :stroke="arc.color"
                    :stroke-width="hoveredKey === arc.key ? stroke + 4 : stroke"
                    :stroke-dasharray="arc.dash"
                    :stroke-dashoffset="arc.offset"
                    class="cursor-pointer transition-[stroke-width]"
                    @mouseenter="hoveredKey = arc.key"
                    @mouseleave="hoveredKey = null"
                />
            </svg>
            <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center text-center">
                <span class="text-2xl font-semibold text-slate-900 tabular-nums">{{ hovered ? formatValue(hovered.value) : centerValue }}</span>
                <span class="text-xs text-slate-500">{{ hovered ? `${hovered.label} · ${hovered.share}%` : centerLabel }}</span>
            </div>
        </div>

        <!-- Legend doubles as direct labels -->
        <ul class="flex w-full max-w-60 flex-col gap-2 text-sm">
            <li
                v-for="arc in arcs"
                :key="arc.key"
                class="flex items-center gap-3"
                @mouseenter="hoveredKey = arc.key"
                @mouseleave="hoveredKey = null"
            >
                <span class="size-3 rounded-sm" :style="{ background: arc.color }" />
                <span class="text-slate-600">{{ arc.label }}</span>
                <span class="ml-auto pl-4 font-medium text-slate-900 tabular-nums">{{ formatValue(arc.value) }}</span>
                <span class="w-10 text-right text-xs text-slate-500 tabular-nums">{{ arc.share }}%</span>
            </li>
        </ul>
    </div>
    </div>
</template>
