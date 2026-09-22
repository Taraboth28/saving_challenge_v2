<script setup>
import BaseModal from './BaseModal.vue';

defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, default: 'Are you sure?' },
    message: { type: String, default: '' },
    confirmLabel: { type: String, default: 'Delete' },
    processing: { type: Boolean, default: false },
});

defineEmits(['confirm', 'cancel']);
</script>

<template>
    <BaseModal :open="open" :title="title" @close="$emit('cancel')">
        <p class="text-sm text-ink-soft">{{ message }}</p>
        <div class="mt-6 flex justify-end gap-2">
            <button type="button" class="btn btn-secondary" :disabled="processing" @click="$emit('cancel')">Cancel</button>
            <button type="button" class="btn btn-danger" :disabled="processing" @click="$emit('confirm')">
                {{ processing ? 'Deleting…' : confirmLabel }}
            </button>
        </div>
    </BaseModal>
</template>
