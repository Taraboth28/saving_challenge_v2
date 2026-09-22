<script setup>
import { computed } from 'vue';

const props = defineProps({
    value: { type: Number, required: true },
    completed: { type: Boolean, default: false },
    size: { type: String, default: 'md' },
    label: { type: String, default: 'Progress' },
});

const width = computed(() => `${Math.min(100, Math.max(0, props.value))}%`);
</script>

<template>
    <div
        class="w-full overflow-hidden rounded-full bg-slate-100"
        :class="size === 'lg' ? 'h-3' : 'h-2'"
        role="progressbar"
        :aria-label="label"
        :aria-valuenow="value"
        aria-valuemin="0"
        aria-valuemax="100"
    >
        <div class="h-full rounded-full transition-[width] duration-500" :class="completed ? 'bg-emerald-600' : 'bg-emerald-500'" :style="{ width }" />
    </div>
</template>
