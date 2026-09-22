<script setup>
import { computed, nextTick, ref } from 'vue';
import { usePopover } from '../../composables/usePopover';
import AppIcon from './AppIcon.vue';

/**
 * Themed replacement for <select> (the native option list can't follow the app's colors).
 * Implements the ARIA "select-only combobox" pattern: focus stays on the button while
 * arrow keys move the highlighted option.
 *
 * <BaseSelect id="goal" v-model="goalId" :options="[{ value: 1, label: 'Laptop' }]" />
 */
const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    options: { type: Array, required: true },
    id: { type: String, required: true },
    placeholder: { type: String, default: 'Select…' },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const { open, trigger, panel, placement, style, show, close } = usePopover();
const activeIndex = ref(-1);

const listId = computed(() => `${props.id}-listbox`);
const selectedIndex = computed(() => props.options.findIndex((option) => option.value === props.modelValue));
const selectedOption = computed(() => props.options[selectedIndex.value] ?? null);

function scrollActiveIntoView() {
    nextTick(() => panel.value?.querySelector('[data-active="true"]')?.scrollIntoView({ block: 'nearest' }));
}

async function openList() {
    activeIndex.value = Math.max(0, selectedIndex.value);
    await show();
    scrollActiveIntoView();
}

function choose(option) {
    emit('update:modelValue', option.value);
    close({ focusTrigger: true });
}

function highlight(index) {
    activeIndex.value = Math.min(Math.max(index, 0), props.options.length - 1);
    scrollActiveIntoView();
}

/** Jump to the next option starting with the typed letter. */
function typeAhead(key) {
    const start = activeIndex.value + 1;
    const ordered = [...props.options.slice(start), ...props.options.slice(0, start)];
    const match = ordered.find((option) => option.label.toLowerCase().startsWith(key.toLowerCase()));

    if (match) {
        highlight(props.options.indexOf(match));
    }
}

function onKeydown(event) {
    if (!open.value) {
        if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(event.key)) {
            event.preventDefault();
            openList();
        }

        return;
    }

    const actions = {
        ArrowDown: () => highlight(activeIndex.value + 1),
        ArrowUp: () => highlight(activeIndex.value - 1),
        Home: () => highlight(0),
        End: () => highlight(props.options.length - 1),
        Enter: () => props.options[activeIndex.value] && choose(props.options[activeIndex.value]),
        ' ': () => props.options[activeIndex.value] && choose(props.options[activeIndex.value]),
        Escape: () => close({ focusTrigger: true }),
    };

    if (actions[event.key]) {
        event.preventDefault();
        // Keep Escape from also closing a surrounding modal.
        event.stopPropagation();
        actions[event.key]();
    } else if (event.key === 'Tab') {
        close();
    } else if (event.key.length === 1) {
        typeAhead(event.key);
    }
}
</script>

<template>
    <button
        :id="id"
        ref="trigger"
        type="button"
        role="combobox"
        class="input flex items-center justify-between gap-2 text-left disabled:cursor-not-allowed disabled:opacity-60"
        :class="{ 'border-orange-500 ring-2 ring-orange-500/30': open }"
        aria-haspopup="listbox"
        :aria-expanded="open"
        :aria-controls="listId"
        :aria-activedescendant="open && activeIndex >= 0 ? `${listId}-${activeIndex}` : undefined"
        :disabled="disabled"
        @click="open ? close() : openList()"
        @keydown="onKeydown"
    >
        <span class="truncate" :class="selectedOption ? 'text-ink' : 'text-ink-muted'">{{ selectedOption?.label ?? placeholder }}</span>
        <AppIcon name="chevronDown" :size="16" class="shrink-0 text-ink-muted transition-transform duration-200" :class="{ 'rotate-180': open }" />
    </button>

    <Teleport to="body">
        <Transition
            enter-from-class="scale-95 opacity-0"
            enter-active-class="transition duration-150 ease-out"
            leave-active-class="transition duration-100 ease-in"
            leave-to-class="scale-95 opacity-0"
        >
            <ul
                v-if="open"
                :id="listId"
                ref="panel"
                role="listbox"
                :style="style"
                class="z-[60] max-h-64 overflow-y-auto rounded-xl border border-line bg-surface p-1 shadow-xl shadow-black/10 dark:shadow-black/40"
                :class="placement === 'top' ? 'origin-bottom' : 'origin-top'"
                @mousedown.prevent
            >
                <li
                    v-for="(option, index) in options"
                    :id="`${listId}-${index}`"
                    :key="String(option.value)"
                    role="option"
                    :aria-selected="option.value === modelValue"
                    :data-active="index === activeIndex"
                    class="flex cursor-pointer items-center justify-between gap-2 rounded-lg px-3 py-2 text-sm"
                    :class="[
                        index === activeIndex ? 'bg-surface-muted' : '',
                        option.value === modelValue ? 'font-semibold text-accent' : 'text-ink-soft',
                    ]"
                    @mousemove="activeIndex = index"
                    @click="choose(option)"
                >
                    <span class="truncate">{{ option.label }}</span>
                    <AppIcon v-if="option.value === modelValue" name="check" :size="16" class="shrink-0" />
                </li>
            </ul>
        </Transition>
    </Teleport>
</template>
