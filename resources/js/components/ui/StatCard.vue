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
        <div class="flex items-center justify-between text-sm text-ink-muted">
            <span>{{ label }}</span>
            <span v-if="icon" class="grid size-9 shrink-0 place-items-center rounded-xl bg-orange-500/10 text-accent">
                <AppIcon :name="icon" :size="20" />
            </span>
        </div>
        <p class="text-2xl font-semibold text-ink tabular-nums">{{ value }}</p>
        <p v-if="hint" class="text-xs text-ink-muted">{{ hint }}</p>
    </div>
</template>
