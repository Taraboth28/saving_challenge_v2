<script setup>
import { computed } from 'vue';
import AppIcon from './AppIcon.vue';

const props = defineProps({
    status: { type: String, required: true },
    overdue: { type: Boolean, default: false },
});

const badge = computed(() => {
    if (props.status === 'completed') {
        return { label: 'Completed', icon: 'check', classes: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' };
    }

    if (props.overdue) {
        return { label: 'Overdue', icon: 'alert', classes: 'bg-amber-50 text-amber-800 ring-amber-600/20' };
    }

    return { label: 'Active', icon: 'clock', classes: 'bg-sky-50 text-sky-700 ring-sky-600/20' };
});
</script>

<template>
    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset" :class="badge.classes">
        <AppIcon :name="badge.icon" :size="12" />
        {{ badge.label }}
    </span>
</template>
