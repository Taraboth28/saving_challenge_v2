<script setup>
import { computed, watch } from 'vue';
import { transactionsApi } from '../../api/transactions';
import { useForm } from '../../composables/useForm';
import { todayIso } from '../../utils/format';
import BaseModal from '../ui/BaseModal.vue';
import FormField from '../ui/FormField.vue';

/**
 * Add or edit a saving transaction. Pass `transaction` to edit, or `goalId` to preselect a goal.
 * When `goals` is given, the user can pick the goal.
 */
const props = defineProps({
    open: { type: Boolean, default: false },
    transaction: { type: Object, default: null },
    goalId: { type: Number, default: null },
    goals: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'saved']);

const blank = () => ({
    goal_id: props.transaction?.goal_id ?? props.goalId ?? props.goals[0]?.id ?? '',
    type: props.transaction?.type ?? 'deposit',
    amount: props.transaction?.amount ?? '',
    date: props.transaction?.date ?? todayIso(),
    note: props.transaction?.note ?? '',
});

const form = useForm(blank());
const isEditing = computed(() => props.transaction !== null);

watch(
    () => props.open,
    (open) => open && form.reset(blank()),
);

async function submit() {
    try {
        const payload = { ...form.values, goal_id: Number(form.values.goal_id), note: form.values.note || null };
        const saved = await form.submit(() =>
            isEditing.value ? transactionsApi.update(props.transaction.id, payload) : transactionsApi.create(payload),
        );
        emit('saved', saved);
        emit('close');
    } catch {
        // Errors are shown inline by the form.
    }
}
</script>

<template>
    <BaseModal :open="open" :title="isEditing ? 'Edit transaction' : 'Add saving'" @close="emit('close')">
        <form class="flex flex-col gap-4" novalidate @submit.prevent="submit">
            <p v-if="form.message.value" class="rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700" role="alert">{{ form.message.value }}</p>

            <FormField v-if="goals.length" label="Goal" for="tx-goal" :error="form.error('goal_id')">
                <select id="tx-goal" v-model="form.values.goal_id" class="input">
                    <option v-for="goal in goals" :key="goal.id" :value="goal.id">{{ goal.name }}</option>
                </select>
            </FormField>

            <fieldset>
                <legend class="label">Type</legend>
                <div class="grid grid-cols-2 gap-2">
                    <label
                        v-for="option in [
                            { value: 'deposit', label: 'Deposit' },
                            { value: 'withdrawal', label: 'Withdrawal' },
                        ]"
                        :key="option.value"
                        class="flex cursor-pointer items-center justify-center rounded-lg border px-3 py-2 text-sm font-medium transition has-focus-visible:outline-2 has-focus-visible:outline-emerald-600"
                        :class="form.values.type === option.value ? 'border-emerald-600 bg-emerald-50 text-emerald-700' : 'border-slate-300 text-slate-600 hover:bg-slate-50'"
                    >
                        <input v-model="form.values.type" type="radio" name="tx-type" :value="option.value" class="sr-only" />
                        {{ option.label }}
                    </label>
                </div>
                <p v-if="form.error('type')" class="mt-1 text-sm text-rose-600">{{ form.error('type') }}</p>
            </fieldset>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField label="Amount" for="tx-amount" :error="form.error('amount')">
                    <input id="tx-amount" v-model="form.values.amount" class="input tabular-nums" type="number" min="0.01" step="0.01" inputmode="decimal" placeholder="0.00" required />
                </FormField>

                <FormField label="Date" for="tx-date" :error="form.error('date')">
                    <input id="tx-date" v-model="form.values.date" class="input" type="date" :max="todayIso()" required />
                </FormField>
            </div>

            <FormField label="Note" for="tx-note" :error="form.error('note')" hint="Optional">
                <input id="tx-note" v-model="form.values.note" class="input" type="text" maxlength="255" placeholder="e.g. Salary bonus" />
            </FormField>

            <div class="mt-2 flex justify-end gap-2">
                <button type="button" class="btn btn-secondary" @click="emit('close')">Cancel</button>
                <button type="submit" class="btn btn-primary" :disabled="form.processing.value">
                    {{ form.processing.value ? 'Saving…' : isEditing ? 'Save changes' : 'Add saving' }}
                </button>
            </div>
        </form>
    </BaseModal>
</template>
