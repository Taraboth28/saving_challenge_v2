<script setup>
defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
});
</script>

<template>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
            <slot name="back" />
            <h1 class="text-2xl font-bold break-words tracking-tight text-ink sm:text-3xl">{{ title }}</h1>
            <p v-if="description" class="mt-1 text-sm text-ink-muted">{{ description }}</p>
        </div>
        <div v-if="$slots.actions" class="flex flex-wrap gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
