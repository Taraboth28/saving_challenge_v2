<script setup>
import { onBeforeUnmount, watch } from 'vue';
import AppIcon from './AppIcon.vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, required: true },
});

const emit = defineEmits(['close']);

function onKeydown(event) {
    if (event.key === 'Escape') {
        emit('close');
    }
}

watch(
    () => props.open,
    (open) => {
        document.body.style.overflow = open ? 'hidden' : '';
        window[open ? 'addEventListener' : 'removeEventListener']('keydown', onKeydown);
    },
);

onBeforeUnmount(() => {
    document.body.style.overflow = '';
    window.removeEventListener('keydown', onKeydown);
});
</script>

<template>
    <Teleport to="body">
        <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" enter-active-class="transition" leave-active-class="transition">
            <div v-if="open" class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/40 p-4 sm:items-center" @click.self="emit('close')">
                <div class="card max-h-[90vh] w-full max-w-lg overflow-y-auto" role="dialog" aria-modal="true" :aria-label="title">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <h2 class="text-lg font-semibold text-slate-900">{{ title }}</h2>
                        <button type="button" class="btn btn-ghost" aria-label="Close" @click="emit('close')">
                            <AppIcon name="close" :size="18" />
                        </button>
                    </div>
                    <div class="px-5 py-4">
                        <slot />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
