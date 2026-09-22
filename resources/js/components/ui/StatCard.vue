<script setup>
import AppIcon from './AppIcon.vue';

defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    hint: { type: String, default: '' },
    icon: { type: String, default: '' },
});
</script>

<template>
    <div class="card flex flex-col gap-2 p-5">
        <div class="flex items-center justify-between text-sm text-slate-500">
            <span>{{ label }}</span>
            <AppIcon v-if="icon" :name="icon" :size="18" class="text-slate-400" />
        </div>
        <p class="text-2xl font-semibold text-slate-900 tabular-nums">{{ value }}</p>
        <p v-if="hint" class="text-xs text-slate-500">{{ hint }}</p>
    </div>
</template>
