<script setup>
import { computed, nextTick, ref } from 'vue';
import { usePopover } from '../../composables/usePopover';
import { appConfig } from '../../config';
import { formatDate, parseDate, toIsoDate, todayIso } from '../../utils/format';
import AppIcon from './AppIcon.vue';

/**
 * Themed replacement for <input type="date"> (the native calendar can't follow the app's colors).
 * Works with YYYY-MM-DD strings; '' means no date.
 *
 * Keyboard: arrows move by day / week, PageUp / PageDown by month, Home / End to week start / end,
 * Enter or Space picks the focused day, Escape closes.
 *
 * <DatePicker id="date" v-model="date" :max="todayIso()" />
 */
const props = defineProps({
    modelValue: { type: String, default: '' },
    id: { type: String, required: true },
    min: { type: String, default: '' },
    max: { type: String, default: '' },
    placeholder: { type: String, default: 'Select a date' },
    clearable: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const { open, trigger, panel, placement, style, show, close } = usePopover({ minWidth: 288 });

const viewMonth = ref(new Date());
const focusedIso = ref('');
const today = todayIso();

/** Locale's first day of the week (0 = Sunday), falling back to Sunday. */
const weekStart = (() => {
    try {
        const locale = new Intl.Locale(appConfig.locale);
        const firstDay = (locale.getWeekInfo?.() ?? locale.weekInfo)?.firstDay;

        return firstDay ? firstDay % 7 : 0;
    } catch {
        return 0;
    }
})();

const weekdays = Array.from({ length: 7 }, (_, index) => {
    const date = new Date(2024, 0, 7 + ((weekStart + index) % 7)); // 2024-01-07 is a Sunday

    return {
        short: date.toLocaleDateString(appConfig.locale, { weekday: 'narrow' }),
        long: date.toLocaleDateString(appConfig.locale, { weekday: 'long' }),
    };
});

const monthLabel = computed(() => viewMonth.value.toLocaleDateString(appConfig.locale, { month: 'long', year: 'numeric' }));

// Always a real boolean: Vue treats an empty string on `disabled` as true.
const isDisabled = (iso) => Boolean((props.min && iso < props.min) || (props.max && iso > props.max));

/** Six weeks covering the viewed month. */
const days = computed(() => {
    const first = new Date(viewMonth.value.getFullYear(), viewMonth.value.getMonth(), 1);
    const start = new Date(first);
    start.setDate(1 - ((first.getDay() - weekStart + 7) % 7));

    return Array.from({ length: 42 }, (_, index) => {
        const date = new Date(start);
        date.setDate(start.getDate() + index);
        const iso = toIsoDate(date);

        return {
            iso,
            day: date.getDate(),
            inMonth: date.getMonth() === first.getMonth(),
            isToday: iso === today,
            isSelected: iso === props.modelValue,
            disabled: isDisabled(iso),
            label: date.toLocaleDateString(appConfig.locale, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }),
        };
    });
});

const canGoBack = computed(() => !props.min || toIsoDate(new Date(viewMonth.value.getFullYear(), viewMonth.value.getMonth(), 0)) >= props.min);
const canGoForward = computed(() => !props.max || toIsoDate(new Date(viewMonth.value.getFullYear(), viewMonth.value.getMonth() + 1, 1)) <= props.max);

function clampToRange(iso) {
    if (props.min && iso < props.min) {
        return props.min;
    }

    return props.max && iso > props.max ? props.max : iso;
}

function showMonthOf(iso) {
    const date = parseDate(iso);
    viewMonth.value = new Date(date.getFullYear(), date.getMonth(), 1);
}

function focusDay() {
    nextTick(() => panel.value?.querySelector(`[data-iso="${focusedIso.value}"]`)?.focus());
}

async function openCalendar() {
    focusedIso.value = props.modelValue || clampToRange(today);
    showMonthOf(focusedIso.value);
    await show();
    focusDay();
}

function select(iso) {
    if (isDisabled(iso)) {
        return;
    }

    emit('update:modelValue', iso);
    close({ focusTrigger: true });
}

function clear() {
    emit('update:modelValue', '');
    close({ focusTrigger: true });
}

function moveFocus(days) {
    const date = parseDate(focusedIso.value);
    date.setDate(date.getDate() + days);
    focusedIso.value = clampToRange(toIsoDate(date));
    showMonthOf(focusedIso.value);
    focusDay();
}

function shiftMonth(delta) {
    const date = parseDate(focusedIso.value || toIsoDate(viewMonth.value));
    const target = new Date(date.getFullYear(), date.getMonth() + delta, 1);
    // Keep the day of month where possible (e.g. Jan 31 → Feb 28).
    target.setDate(Math.min(date.getDate(), new Date(target.getFullYear(), target.getMonth() + 1, 0).getDate()));
    focusedIso.value = clampToRange(toIsoDate(target));
    viewMonth.value = new Date(target.getFullYear(), target.getMonth(), 1);
    focusDay();
}

function onGridKeydown(event) {
    const offsetInWeek = (parseDate(focusedIso.value).getDay() - weekStart + 7) % 7;
    const actions = {
        ArrowLeft: () => moveFocus(-1),
        ArrowRight: () => moveFocus(1),
        ArrowUp: () => moveFocus(-7),
        ArrowDown: () => moveFocus(7),
        PageUp: () => shiftMonth(-1),
        PageDown: () => shiftMonth(1),
        Home: () => moveFocus(-offsetInWeek),
        End: () => moveFocus(6 - offsetInWeek),
        Enter: () => select(focusedIso.value),
        ' ': () => select(focusedIso.value),
    };

    if (actions[event.key]) {
        event.preventDefault();
        actions[event.key]();
    }
}

function onPanelKeydown(event) {
    if (event.key === 'Escape') {
        event.preventDefault();
        // Keep Escape from also closing a surrounding modal.
        event.stopPropagation();
        close({ focusTrigger: true });
    }
}

/** Close when keyboard focus leaves for another element (outside clicks are handled by usePopover). */
function onPanelFocusOut(event) {
    const next = event.relatedTarget;

    if (next && !panel.value?.contains(next) && next !== trigger.value) {
        close();
    }
}

function onTriggerKeydown(event) {
    if (event.key === 'ArrowDown' && !open.value) {
        event.preventDefault();
        openCalendar();
    }
}
</script>

<template>
    <button
        :id="id"
        ref="trigger"
        type="button"
        class="input flex items-center justify-between gap-2 text-left"
        :class="{ 'border-orange-500 ring-2 ring-orange-500/30': open }"
        aria-haspopup="dialog"
        :aria-expanded="open"
        @click="open ? close() : openCalendar()"
        @keydown="onTriggerKeydown"
    >
        <span class="truncate" :class="modelValue ? 'text-ink' : 'text-ink-muted'">{{ modelValue ? formatDate(modelValue) : placeholder }}</span>
        <AppIcon name="calendar" :size="16" class="shrink-0 text-ink-muted" />
    </button>

    <Teleport to="body">
        <Transition
            enter-from-class="scale-95 opacity-0"
            enter-active-class="transition duration-150 ease-out"
            leave-active-class="transition duration-100 ease-in"
            leave-to-class="scale-95 opacity-0"
        >
            <div
                v-if="open"
                ref="panel"
                role="dialog"
                aria-modal="false"
                :aria-label="`Choose date, ${monthLabel}`"
                :style="style"
                class="z-[60] rounded-2xl border border-line bg-surface p-3 shadow-xl shadow-black/10 dark:shadow-black/40"
                :class="placement === 'top' ? 'origin-bottom' : 'origin-top'"
                @keydown="onPanelKeydown"
                @focusout="onPanelFocusOut"
            >
                <!-- Month navigation -->
                <div class="mb-2 flex items-center justify-between">
                    <button type="button" class="btn btn-ghost size-8 p-0" :disabled="!canGoBack" aria-label="Previous month" @click="shiftMonth(-1)">
                        <AppIcon name="chevronLeft" :size="18" />
                    </button>
                    <p :key="monthLabel" class="animate-pop text-sm font-semibold text-ink" aria-live="polite">{{ monthLabel }}</p>
                    <button type="button" class="btn btn-ghost size-8 p-0" :disabled="!canGoForward" aria-label="Next month" @click="shiftMonth(1)">
                        <AppIcon name="chevronRight" :size="18" />
                    </button>
                </div>

                <!-- Calendar grid -->
                <div class="grid grid-cols-7 gap-1 text-center" role="grid" :aria-label="monthLabel" @keydown="onGridKeydown">
                    <div v-for="weekday in weekdays" :key="weekday.long" role="columnheader" :aria-label="weekday.long" class="py-1 text-[11px] font-medium text-ink-muted uppercase">
                        {{ weekday.short }}
                    </div>
                    <button
                        v-for="cell in days"
                        :key="cell.iso"
                        type="button"
                        role="gridcell"
                        :data-iso="cell.iso"
                        :tabindex="cell.iso === focusedIso ? 0 : -1"
                        :aria-label="cell.label"
                        :aria-selected="cell.isSelected"
                        :aria-current="cell.isToday ? 'date' : undefined"
                        :disabled="cell.disabled"
                        class="relative grid aspect-square place-items-center rounded-full text-sm tabular-nums transition-colors focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-orange-500 disabled:cursor-not-allowed disabled:opacity-30"
                        :class="[
                            cell.isSelected
                                ? 'bg-orange-500 font-semibold text-neutral-950'
                                : cell.inMonth
                                  ? 'text-ink hover:bg-surface-muted'
                                  : 'text-ink-muted/60 hover:bg-surface-muted',
                            cell.isToday && !cell.isSelected ? 'font-semibold text-accent ring-1 ring-orange-500/50 ring-inset' : '',
                        ]"
                        @click="select(cell.iso)"
                        @focus="focusedIso = cell.iso"
                    >
                        {{ cell.day }}
                    </button>
                </div>

                <!-- Shortcuts -->
                <div class="mt-3 flex items-center justify-between border-t border-line pt-3">
                    <button v-if="clearable && modelValue" type="button" class="btn btn-ghost px-3 py-1.5 text-xs" @click="clear">Clear</button>
                    <span v-else />
                    <button type="button" class="btn btn-secondary px-3 py-1.5 text-xs" :disabled="isDisabled(today)" @click="select(today)">Today</button>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
