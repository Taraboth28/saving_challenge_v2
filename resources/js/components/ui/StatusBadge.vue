<script setup>
import { computed } from 'vue';
import AppIcon from './AppIcon.vue';

const props = defineProps({
    status: { type: String, required: true },
    overdue: { type: Boolean, default: false },
});

const badge = computed(() => {
    if (props.status === 'completed') {
        return { label: 'Completed', icon: 'circleCheck', classes: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-400/30' };
    }

    if (props.overdue) {
        return { label: 'Overdue', icon: 'calendarX', classes: 'bg-amber-50 text-amber-800 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-400/30' };
    }

    return { label: 'Active', icon: 'trendingUp', classes: 'bg-sky-50 text-sky-700 ring-sky-600/20 dark:bg-sky-500/10 dark:text-sky-300 dark:ring-sky-400/30' };
});
</script>

<template>
    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset" :class="badge.classes">
        <AppIcon :name="badge.icon" :size="12" />
        {{ badge.label }}
    </span>
</template>
