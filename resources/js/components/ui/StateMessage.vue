<script setup>
import AppIcon from './AppIcon.vue';

/**
 * Loading, error and empty placeholders in one component.
 */
defineProps({
    state: { type: String, default: 'empty', validator: (value) => ['loading', 'error', 'empty'].includes(value) },
    title: { type: String, default: '' },
    message: { type: String, default: '' },
});

defineEmits(['retry']);
</script>

<template>
    <div class="flex flex-col items-center justify-center gap-3 px-6 py-12 text-center" :role="state === 'error' ? 'alert' : undefined">
        <div v-if="state === 'loading'" class="size-8 animate-spin rounded-full border-2 border-line border-t-orange-600" aria-label="Loading" />
        <span v-else class="grid size-12 place-items-center rounded-full" :class="state === 'error' ? 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400' : 'bg-surface-muted text-ink-muted'">
            <AppIcon :name="state === 'error' ? 'alertTriangle' : 'inbox'" />
        </span>

        <p v-if="title || state !== 'loading'" class="font-medium text-ink">
            {{ title || (state === 'error' ? 'Something went wrong' : 'Nothing here yet') }}
        </p>
        <p v-if="message" class="max-w-sm text-sm text-ink-muted">{{ message }}</p>

        <button v-if="state === 'error'" type="button" class="btn btn-secondary" @click="$emit('retry')">Try again</button>
        <slot />
    </div>
</template>
