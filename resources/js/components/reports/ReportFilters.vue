<script setup>
import { reactive, watch } from 'vue';
import { todayIso } from '../../utils/format';

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

    return new Date(date.getTime() - date.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
}

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
                <input id="filter-from" v-model="filters.from" type="date" class="input" :max="filters.to || undefined" @change="apply()" />
            </div>
            <div>
                <label for="filter-to" class="label">To</label>
                <input id="filter-to" v-model="filters.to" type="date" class="input" :min="filters.from || undefined" @change="apply()" />
            </div>
            <div>
                <label for="filter-goal" class="label">Goal</label>
                <select id="filter-goal" v-model="filters.goal_id" class="input" @change="apply()">
                    <option value="">All goals</option>
                    <option v-for="goal in goals" :key="goal.id" :value="goal.id">{{ goal.name }}</option>
                </select>
            </div>
        </div>

        <div class="flex flex-wrap gap-1 rounded-lg bg-slate-100 p-1" role="group" aria-label="Date range presets">
            <button
                v-for="preset in presets"
                :key="preset.label"
                type="button"
                class="rounded-md px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-white hover:text-slate-900"
                @click="apply(preset.range())"
            >
                {{ preset.label }}
            </button>
        </div>
    </div>
</template>
