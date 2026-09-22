<script setup>
import { computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { goalsApi } from '../api/goals';
import { reportsApi } from '../api/reports';
import BarChart from '../components/charts/BarChart.vue';
import { SERIES_COLORS } from '../components/charts/chartUtils';
import ReportFilters from '../components/reports/ReportFilters.vue';
import TransactionTable from '../components/transactions/TransactionTable.vue';
import PageHeader from '../components/ui/PageHeader.vue';
import ProgressBar from '../components/ui/ProgressBar.vue';
import StatCard from '../components/ui/StatCard.vue';
import StateMessage from '../components/ui/StateMessage.vue';
import StatusBadge from '../components/ui/StatusBadge.vue';
import { useAsyncData } from '../composables/useAsyncData';
import { onDataChanged } from '../composables/useDataChanged';
import { formatCurrency, formatDate, formatMonth, formatPercent, pluralize } from '../utils/format';

const route = useRoute();
const router = useRouter();

/** Filters live in the URL query so a filtered report can be bookmarked or shared. */
const filters = computed(() => ({
    from: route.query.from ?? '',
    to: route.query.to ?? '',
    goal_id: route.query.goal_id ? Number(route.query.goal_id) : '',
}));

function updateFilters(next) {
    const query = Object.fromEntries(Object.entries(next).filter(([, value]) => value !== '' && value !== null));
    router.replace({ query });
}

const { data: goals } = useAsyncData(() => goalsApi.list(), { initialData: [] });

const { data: report, loading, error, reload } = useAsyncData(
    async () => {
        const params = filters.value;
        const [history, byGoal, monthly] = await Promise.all([
            reportsApi.history(params),
            reportsApi.goals(params),
            reportsApi.monthly(params),
        ]);

        return { history, byGoal, monthly };
    },
    { immediate: false },
);

watch(
    () => route.name === 'reports' && JSON.stringify(filters.value),
    (key) => key && reload(),
    { immediate: true },
);

onDataChanged(() => reload());

const monthlyChart = computed(() =>
    (report.value?.monthly ?? []).map((month) => ({
        label: formatMonth(month.month, { short: true }),
        tooltipLabel: formatMonth(month.month),
        values: { deposits: month.deposits, withdrawals: month.withdrawals },
    })),
);

const monthlySeries = [
    { key: 'deposits', label: 'Deposits', color: SERIES_COLORS[0] },
    { key: 'withdrawals', label: 'Withdrawals', color: SERIES_COLORS[1] },
];

const periodLabel = computed(() => {
    const { from, to } = filters.value;

    if (!from && !to) {
        return 'All time';
    }

    return `${from ? formatDate(from) : 'Beginning'} – ${to ? formatDate(to) : 'Today'}`;
});
</script>

<template>
    <PageHeader title="Reports" :description="`Saving history and summaries · ${periodLabel}`" />

    <div class="flex flex-col gap-6">
        <ReportFilters :model-value="filters" :goals="goals" @update:model-value="updateFilters" />

        <StateMessage v-if="loading && !report" state="loading" />
        <StateMessage v-else-if="error" state="error" :message="error.message" @retry="reload" />

        <template v-else-if="report">
            <!-- Period totals -->
            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Period totals" :class="{ 'opacity-60': loading }">
                <StatCard label="Deposits" :value="formatCurrency(report.history.totals.deposits)" icon="circlePlus" />
                <StatCard label="Withdrawals" :value="formatCurrency(report.history.totals.withdrawals)" icon="circleMinus" />
                <StatCard label="Net saved" :value="formatCurrency(report.history.totals.net)" icon="wallet" />
                <StatCard label="Transactions" :value="report.history.totals.count" icon="receipt" />
            </section>

            <!-- Monthly summary -->
            <section class="card p-5">
                <h2 class="font-semibold text-ink">Monthly saving summary</h2>
                <p class="mb-4 text-xs text-ink-muted">Deposits and withdrawals per month</p>

                <template v-if="report.monthly.length">
                    <BarChart
                        :data="monthlyChart"
                        :series="monthlySeries"
                        :format-value="(value) => formatCurrency(value)"
                        :format-axis="(value) => formatCurrency(value, { compact: true })"
                        aria-label="Monthly deposits and withdrawals"
                    />
                    <details class="mt-4">
                        <summary class="cursor-pointer text-sm font-medium text-accent">Show table</summary>
                        <div class="mt-3 overflow-x-auto">
                            <table class="table-base">
                                <thead>
                                    <tr>
                                        <th scope="col">Month</th>
                                        <th scope="col" class="hidden text-right sm:table-cell">Deposits</th>
                                        <th scope="col" class="hidden text-right sm:table-cell">Withdrawals</th>
                                        <th scope="col" class="text-right">Net</th>
                                        <th scope="col" class="text-right"><span class="sm:hidden">Count</span><span class="hidden sm:inline">Transactions</span></th>
                                    </tr>
                                </thead>
                                <tbody class="tabular-nums">
                                    <tr v-for="month in report.monthly" :key="month.month">
                                        <th scope="row" class="px-4 py-3 text-left font-medium">{{ formatMonth(month.month) }}</th>
                                        <td class="hidden text-right sm:table-cell">{{ formatCurrency(month.deposits) }}</td>
                                        <td class="hidden text-right sm:table-cell">{{ formatCurrency(month.withdrawals) }}</td>
                                        <td class="text-right font-medium">
                                            {{ formatCurrency(month.net) }}
                                            <!-- Phones: deposits / withdrawals stack under the net amount -->
                                            <span class="block text-xs font-normal text-ink-muted sm:hidden">
                                                +{{ formatCurrency(month.deposits) }} / −{{ formatCurrency(month.withdrawals) }}
                                            </span>
                                        </td>
                                        <td class="text-right">{{ month.count }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </details>
                </template>
                <StateMessage v-else message="No transactions in this period." />
            </section>

            <!-- Summary by goal -->
            <section class="card">
                <div class="px-5 pt-5 pb-2">
                    <h2 class="font-semibold text-ink">Saving summary by goal</h2>
                    <p class="text-xs text-ink-muted">Net saved in the period, with overall progress</p>
                </div>
                <div v-if="report.byGoal.length" class="overflow-x-auto">
                    <table class="table-base">
                        <thead>
                            <tr>
                                <th scope="col">Goal</th>
                                <th scope="col" class="text-right">In period</th>
                                <th scope="col" class="hidden text-right sm:table-cell">Saved / target</th>
                                <th scope="col" class="hidden w-40 sm:table-cell">Progress</th>
                            </tr>
                        </thead>
                        <tbody class="tabular-nums">
                            <tr v-for="row in report.byGoal" :key="row.goal_id">
                                <td>
                                    <RouterLink :to="{ name: 'goals.show', params: { id: row.goal_id } }" class="font-medium text-ink hover:text-accent">
                                        {{ row.name }}
                                    </RouterLink>
                                    <div class="mt-1"><StatusBadge :status="row.status" /></div>
                                    <!-- Phones: progress and saved / target stack under the goal name -->
                                    <div class="mt-2 flex flex-col gap-1 sm:hidden">
                                        <div class="flex items-center gap-2">
                                            <ProgressBar :value="row.progress" :completed="row.status === 'completed'" :label="`${row.name} progress`" />
                                            <span class="text-xs text-ink-soft">{{ formatPercent(row.progress) }}</span>
                                        </div>
                                        <span class="text-xs text-ink-muted">{{ formatCurrency(row.saved_amount) }} / {{ formatCurrency(row.target_amount) }}</span>
                                    </div>
                                </td>
                                <td class="text-right whitespace-nowrap">
                                    <span class="font-medium">{{ formatCurrency(row.period.net) }}</span>
                                    <span class="block text-xs text-ink-muted">{{ pluralize(row.period.count, 'transaction') }}</span>
                                </td>
                                <td class="hidden text-right whitespace-nowrap text-ink-soft sm:table-cell">
                                    {{ formatCurrency(row.saved_amount) }} / {{ formatCurrency(row.target_amount) }}
                                </td>
                                <td class="hidden sm:table-cell">
                                    <div class="flex items-center gap-2">
                                        <ProgressBar :value="row.progress" :completed="row.status === 'completed'" :label="`${row.name} progress`" />
                                        <span class="w-12 text-right text-xs text-ink-soft">{{ formatPercent(row.progress) }}</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <StateMessage v-else message="No goals match this filter." />
            </section>

            <!-- History -->
            <section class="card">
                <div class="px-5 pt-5 pb-2">
                    <h2 class="font-semibold text-ink">Saving history</h2>
                    <p class="text-xs text-ink-muted">{{ pluralize(report.history.transactions.length, 'transaction') }}</p>
                </div>
                <TransactionTable v-if="report.history.transactions.length" :transactions="report.history.transactions" show-goal />
                <StateMessage v-else message="No transactions in this period." />
            </section>
        </template>
    </div>
</template>
