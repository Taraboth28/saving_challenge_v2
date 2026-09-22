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
    <!-- `relative` keeps the absolutely positioned sr-only label inside the scroll area. -->
    <div class="relative overflow-x-auto">
        <table class="table-base">
            <thead>
                <tr>
                    <th scope="col">Date</th>
                    <th v-if="showGoal" scope="col" class="hidden sm:table-cell">Goal</th>
                    <th scope="col" class="hidden sm:table-cell">Note</th>
                    <th scope="col" class="text-right">Amount</th>
                    <th v-if="editable" scope="col"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="transaction in transactions" :key="transaction.id" class="hover:bg-surface-muted">
                    <td class="w-full max-w-0 text-ink-soft sm:w-auto sm:max-w-none">
                        <span class="whitespace-nowrap">{{ formatDate(transaction.date) }}</span>
                        <!-- On phones the goal and note columns collapse into this cell. -->
                        <span class="mt-1 flex flex-col gap-1 sm:hidden">
                            <RouterLink
                                v-if="showGoal"
                                :to="{ name: 'goals.show', params: { id: transaction.goal_id } }"
                                class="block truncate font-medium text-ink hover:text-accent"
                                :title="transaction.goal_name"
                            >
                                {{ transaction.goal_name }}
                            </RouterLink>
                            <span class="flex min-w-0 items-center gap-2 text-xs text-ink-muted">
                                <span class="shrink-0 rounded bg-surface-muted px-1.5 py-0.5 text-[11px] font-medium text-ink-soft capitalize">{{ transaction.type }}</span>
                                <span class="truncate" :title="transaction.note || undefined">{{ transaction.note || '' }}</span>
                            </span>
                        </span>
                    </td>
                    <td v-if="showGoal" class="hidden max-w-48 truncate sm:table-cell">
                        <RouterLink :to="{ name: 'goals.show', params: { id: transaction.goal_id } }" class="font-medium text-ink hover:text-accent" :title="transaction.goal_name">
                            {{ transaction.goal_name }}
                        </RouterLink>
                    </td>
                    <td class="hidden w-full max-w-0 truncate text-ink-muted sm:table-cell" :title="transaction.note || undefined">
                        <span class="mr-2 inline-block rounded bg-surface-muted px-1.5 py-0.5 text-[11px] font-medium text-ink-soft capitalize">{{ transaction.type }}</span>
                        {{ transaction.note || '' }}
                    </td>
                    <td class="text-right whitespace-nowrap">
                        <AmountText :amount="transaction.amount" :type="transaction.type" />
                    </td>
                    <td v-if="editable" class="pr-2 text-right whitespace-nowrap">
                        <button type="button" class="btn btn-ghost" :aria-label="`Edit transaction from ${formatDate(transaction.date)}`" @click="$emit('edit', transaction)">
                            <AppIcon name="pencil" :size="16" />
                        </button>
                        <button
                            type="button"
                            class="btn btn-ghost hover:text-rose-600 dark:hover:text-rose-400"
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
