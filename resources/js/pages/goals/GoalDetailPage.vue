<script setup>
import { computed, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { goalsApi } from '../../api/goals';
import { transactionsApi } from '../../api/transactions';
import TransactionFormModal from '../../components/transactions/TransactionFormModal.vue';
import TransactionTable from '../../components/transactions/TransactionTable.vue';
import AppIcon from '../../components/ui/AppIcon.vue';
import ConfirmDialog from '../../components/ui/ConfirmDialog.vue';
import PageHeader from '../../components/ui/PageHeader.vue';
import ProgressBar from '../../components/ui/ProgressBar.vue';
import StateMessage from '../../components/ui/StateMessage.vue';
import StatusBadge from '../../components/ui/StatusBadge.vue';
import { useAsyncData } from '../../composables/useAsyncData';
import { onDataChanged } from '../../composables/useDataChanged';
import { useSavingsPulse } from '../../composables/useSavingsPulse';
import { describeDaysLeft, formatCurrency, formatDate, formatPercent } from '../../utils/format';

const props = defineProps({
    id: { type: Number, required: true },
});

const router = useRouter();
const { data, loading, error, reload } = useAsyncData(() => goalsApi.find(props.id));

watch(() => props.id, reload);
onDataChanged(reload);

const { refresh: refreshPulse } = useSavingsPulse();

/** Reload the goal and the navigation's live savings progress after a change. */
async function onTransactionsChanged() {
    await Promise.all([reload(), refreshPulse()]);
}

const goal = computed(() => data.value?.goal);
const transactions = computed(() => data.value?.transactions ?? []);

// Transaction modal
const transactionModalOpen = ref(false);
const editingTransaction = ref(null);

function openTransactionModal(transaction = null) {
    editingTransaction.value = transaction;
    transactionModalOpen.value = true;
}

// Delete confirmation (goal or transaction)
const pendingDelete = ref(null);
const deleting = ref(false);
const deleteError = ref('');

async function confirmDelete() {
    deleting.value = true;
    deleteError.value = '';

    try {
        if (pendingDelete.value.kind === 'goal') {
            await goalsApi.remove(props.id);
            router.push({ name: 'goals.index' });
        } else {
            await transactionsApi.remove(pendingDelete.value.transaction.id);
            await onTransactionsChanged();
        }
        pendingDelete.value = null;
    } catch (caught) {
        deleteError.value = caught.message;
    } finally {
        deleting.value = false;
    }
}

/** Amount per day needed to hit the target on time. */
const dailyNeeded = computed(() => {
    if (!goal.value || goal.value.status === 'completed' || !goal.value.days_left || goal.value.days_left <= 0) {
        return null;
    }

    return goal.value.remaining_amount / goal.value.days_left;
});
</script>

<template>
    <StateMessage v-if="loading && !goal" state="loading" />
    <StateMessage
        v-else-if="error"
        state="error"
        :title="error.status === 404 ? 'Goal not found' : ''"
        :message="error.status === 404 ? 'This goal may have been deleted.' : error.message"
        @retry="reload"
    />

    <template v-else-if="goal">
        <PageHeader :title="goal.name" :description="goal.description || ''">
            <template #back>
                <RouterLink :to="{ name: 'goals.index' }" class="mb-2 inline-flex items-center gap-1 text-sm text-ink-muted hover:text-ink">
                    <AppIcon name="arrowLeft" :size="16" />
                    All goals
                </RouterLink>
            </template>
            <template #actions>
                <button type="button" class="btn btn-primary" @click="openTransactionModal()">
                    <AppIcon name="plus" :size="18" />
                    Add saving
                </button>
                <RouterLink :to="{ name: 'goals.edit', params: { id: goal.id } }" class="btn btn-secondary">
                    <AppIcon name="pencil" :size="16" />
                    Edit
                </RouterLink>
                <button type="button" class="btn btn-secondary hover:text-rose-600 dark:hover:text-rose-400" @click="pendingDelete = { kind: 'goal' }">
                    <AppIcon name="trash" :size="16" />
                    Delete
                </button>
            </template>
        </PageHeader>

        <div class="flex flex-col gap-6">
            <!-- Progress summary -->
            <section class="card p-6">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-sm text-ink-muted">Saved so far</p>
                        <p class="text-3xl font-semibold text-ink tabular-nums">
                            {{ formatCurrency(goal.saved_amount) }}
                            <span class="text-base font-normal text-ink-muted">of {{ formatCurrency(goal.target_amount) }}</span>
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <StatusBadge :status="goal.status" :overdue="goal.is_overdue" />
                        <span class="text-2xl font-semibold text-accent tabular-nums">{{ formatPercent(goal.progress) }}</span>
                    </div>
                </div>

                <ProgressBar :value="goal.progress" :completed="goal.status === 'completed'" size="lg" :label="`${goal.name} progress`" />

                <dl class="mt-6 grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                    <div>
                        <dt class="text-ink-muted">Remaining</dt>
                        <dd class="font-semibold text-ink tabular-nums">{{ formatCurrency(goal.remaining_amount) }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-muted">Target date</dt>
                        <dd class="font-semibold text-ink">{{ formatDate(goal.target_date) }}</dd>
                        <dd class="text-xs" :class="goal.is_overdue ? 'text-amber-700 dark:text-amber-400' : 'text-ink-muted'">{{ describeDaysLeft(goal) }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-muted">Transactions</dt>
                        <dd class="font-semibold text-ink tabular-nums">{{ goal.transactions_count }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-muted">{{ dailyNeeded ? 'Needed per day' : 'Last saving' }}</dt>
                        <dd class="font-semibold text-ink tabular-nums">
                            {{ dailyNeeded ? formatCurrency(dailyNeeded) : formatDate(goal.last_activity_at) }}
                        </dd>
                    </div>
                </dl>
            </section>

            <!-- History -->
            <section class="card">
                <div class="flex items-center justify-between px-5 pt-5 pb-2">
                    <h2 class="font-semibold text-ink">Saving history</h2>
                </div>
                <TransactionTable
                    v-if="transactions.length"
                    :transactions="transactions"
                    editable
                    @edit="openTransactionModal"
                    @delete="(transaction) => (pendingDelete = { kind: 'transaction', transaction })"
                />
                <StateMessage v-else message="No savings yet. Add your first saving to start tracking progress.">
                    <button type="button" class="btn btn-primary" @click="openTransactionModal()">Add saving</button>
                </StateMessage>
            </section>
        </div>

        <TransactionFormModal
            :open="transactionModalOpen"
            :transaction="editingTransaction"
            :goal-id="goal.id"
            @close="transactionModalOpen = false"
            @saved="onTransactionsChanged"
        />

        <ConfirmDialog
            :open="pendingDelete !== null"
            :title="pendingDelete?.kind === 'goal' ? 'Delete this goal?' : 'Delete this transaction?'"
            :message="
                deleteError ||
                (pendingDelete?.kind === 'goal'
                    ? `“${goal.name}” and all ${goal.transactions_count} of its transactions will be permanently deleted.`
                    : 'This transaction will be permanently removed and the goal progress recalculated.')
            "
            :processing="deleting"
            @confirm="confirmDelete"
            @cancel="(pendingDelete = null), (deleteError = '')"
        />
    </template>
</template>
