<script setup>
import { computed, reactive, watch } from 'vue';
import { toIsoDate, todayIso } from '../../utils/format';
import BaseSelect from '../ui/BaseSelect.vue';
import DatePicker from '../ui/DatePicker.vue';

/**
 * Date-range + goal filter row. Emits `update:modelValue` with { from, to, goal_id }.
 */
const props = defineProps({
    modelValue: { type: Object, required: true },
    goals: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:modelValue']);

const filters = reactive({ ...props.modelValue });

watch(
    () => props.modelValue,
    (value) => Object.assign(filters, value),
);

function isoDaysAgo(days) {
    const date = new Date();
    date.setDate(date.getDate() - days);

    return toIsoDate(date);
}

const goalOptions = computed(() => [{ value: '', label: 'All goals' }, ...props.goals.map((goal) => ({ value: goal.id, label: goal.name }))]);

const presets = [
    { label: '30 days', range: () => ({ from: isoDaysAgo(29), to: todayIso() }) },
    { label: 'This year', range: () => ({ from: `${new Date().getFullYear()}-01-01`, to: todayIso() }) },
    { label: '12 months', range: () => ({ from: isoDaysAgo(364), to: todayIso() }) },
    { label: 'All time', range: () => ({ from: '', to: '' }) },
];

function apply(changes = {}) {
    Object.assign(filters, changes);
    emit('update:modelValue', { ...filters });
}
</script>

<template>
    <div class="card flex flex-col gap-4 p-4 lg:flex-row lg:items-end">
        <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-3">
            <div>
                <label for="filter-from" class="label">From</label>
                <DatePicker id="filter-from" :model-value="filters.from" :max="filters.to || todayIso()" placeholder="Beginning" clearable @update:model-value="(from) => apply({ from })" />
            </div>
            <div>
                <label for="filter-to" class="label">To</label>
                <DatePicker id="filter-to" :model-value="filters.to" :min="filters.from" placeholder="Today" clearable @update:model-value="(to) => apply({ to })" />
            </div>
            <div>
                <label for="filter-goal" class="label">Goal</label>
                <BaseSelect id="filter-goal" :model-value="filters.goal_id" :options="goalOptions" @update:model-value="(goal_id) => apply({ goal_id })" />
            </div>
        </div>

        <div class="flex flex-wrap gap-1 rounded-lg bg-surface-muted p-1" role="group" aria-label="Date range presets">
            <button
                v-for="preset in presets"
                :key="preset.label"
                type="button"
                class="rounded-md px-3 py-1.5 text-xs font-medium text-ink-soft transition hover:bg-surface hover:text-ink"
                @click="apply(preset.range())"
            >
                {{ preset.label }}
            </button>
        </div>
    </div>
</template>
