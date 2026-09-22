<script setup>
import { useForm } from '../../composables/useForm';
import { todayIso } from '../../utils/format';
import FormField from '../ui/FormField.vue';

/**
 * Create / edit form for a goal. The parent passes an async `action`
 * (e.g. goalsApi.create) and receives the saved goal via `saved`.
 */
const props = defineProps({
    goal: { type: Object, default: null },
    action: { type: Function, required: true },
    submitLabel: { type: String, default: 'Save goal' },
});

const emit = defineEmits(['saved', 'cancel']);

const form = useForm({
    name: props.goal?.name ?? '',
    description: props.goal?.description ?? '',
    target_amount: props.goal?.target_amount ?? '',
    target_date: props.goal?.target_date ?? '',
});

async function submit() {
    try {
        const saved = await form.submit((values) =>
            props.action({
                ...values,
                description: values.description || null,
                target_date: values.target_date || null,
            }),
        );
        emit('saved', saved);
    } catch {
        // Errors are shown inline by the form.
    }
}
</script>

<template>
    <form class="card flex flex-col gap-5 p-6" novalidate @submit.prevent="submit">
        <p v-if="form.message.value" class="rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700" role="alert">{{ form.message.value }}</p>

        <FormField label="Goal name" for="name" :error="form.error('name')">
            <input id="name" v-model="form.values.name" class="input" type="text" maxlength="120" placeholder="e.g. Emergency fund" required autofocus />
        </FormField>

        <FormField label="Description" for="description" :error="form.error('description')" hint="Optional — what are you saving for?">
            <textarea id="description" v-model="form.values.description" class="input" rows="3" maxlength="1000" />
        </FormField>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <FormField label="Target amount" for="target_amount" :error="form.error('target_amount')">
                <input id="target_amount" v-model="form.values.target_amount" class="input tabular-nums" type="number" min="0.01" step="0.01" inputmode="decimal" placeholder="0.00" required />
            </FormField>

            <FormField label="Target date" for="target_date" :error="form.error('target_date')" hint="Optional deadline">
                <input id="target_date" v-model="form.values.target_date" class="input" type="date" :min="goal ? undefined : todayIso()" />
            </FormField>
        </div>

        <div class="flex justify-end gap-2 border-t border-slate-100 pt-5">
            <button type="button" class="btn btn-secondary" @click="emit('cancel')">Cancel</button>
            <button type="submit" class="btn btn-primary" :disabled="form.processing.value">
                {{ form.processing.value ? 'Saving…' : submitLabel }}
            </button>
        </div>
    </form>
</template>
