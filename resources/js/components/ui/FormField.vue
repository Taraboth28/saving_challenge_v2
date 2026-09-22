<script setup>
defineProps({
    label: { type: String, required: true },
    for: { type: String, required: true },
    error: { type: String, default: '' },
    hint: { type: String, default: '' },
});
</script>

<template>
    <div>
        <label :for="$props.for" class="label">{{ label }}</label>
        <slot />
        <p v-if="error" class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ error }}</p>
        <p v-else-if="hint" class="mt-1 text-xs text-ink-muted">{{ hint }}</p>
    </div>
</template>
