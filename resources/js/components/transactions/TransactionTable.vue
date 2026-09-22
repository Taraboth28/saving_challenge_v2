<script setup>
import { formatDate } from '../../utils/format';
import AmountText from '../ui/AmountText.vue';
import AppIcon from '../ui/AppIcon.vue';

/**
 * Transaction history table. Set `showGoal` to add a goal column and
 * `editable` to show edit / delete actions (emitted to the parent).
 */
defineProps({
    transactions: { type: Array, required: true },
    showGoal: { type: Boolean, default: false },
    editable: { type: Boolean, default: false },
});

defineEmits(['edit', 'delete']);
</script>

<template>
    <div class="overflow-x-auto">
        <table class="table-base">
            <thead>
                <tr>
                    <th scope="col">Date</th>
                    <th v-if="showGoal" scope="col">Goal</th>
                    <th scope="col">Note</th>
                    <th scope="col" class="text-right">Amount</th>
                    <th v-if="editable" scope="col"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="transaction in transactions" :key="transaction.id" class="hover:bg-slate-50">
                    <td class="whitespace-nowrap text-slate-600">{{ formatDate(transaction.date) }}</td>
                    <td v-if="showGoal" class="whitespace-nowrap">
                        <RouterLink :to="{ name: 'goals.show', params: { id: transaction.goal_id } }" class="font-medium text-slate-800 hover:text-emerald-700">
                            {{ transaction.goal_name }}
                        </RouterLink>
                    </td>
                    <td class="max-w-xs truncate text-slate-500">
                        <span class="mr-2 inline-block rounded bg-slate-100 px-1.5 py-0.5 text-[11px] font-medium text-slate-600 capitalize">{{ transaction.type }}</span>
                        {{ transaction.note || '' }}
                    </td>
                    <td class="text-right whitespace-nowrap">
                        <AmountText :amount="transaction.amount" :type="transaction.type" />
                    </td>
                    <td v-if="editable" class="text-right whitespace-nowrap">
                        <button type="button" class="btn btn-ghost" :aria-label="`Edit transaction from ${formatDate(transaction.date)}`" @click="$emit('edit', transaction)">
                            <AppIcon name="edit" :size="16" />
                        </button>
                        <button
                            type="button"
                            class="btn btn-ghost hover:text-rose-600"
                            :aria-label="`Delete transaction from ${formatDate(transaction.date)}`"
                            @click="$emit('delete', transaction)"
                        >
                            <AppIcon name="trash" :size="16" />
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
