<script setup>
import { computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { goalsApi } from '../api/goals';
import { reportsApi } from '../api/reports';
import BarChart from '../components/charts/BarChart.vue';
import DonutChart from '../components/charts/DonutChart.vue';
import { SERIES_COLORS } from '../components/charts/chartUtils';
import ReportFilters from '../components/reports/ReportFilters.vue';
import TransactionTable from '../components/transactions/TransactionTable.vue';
import PageHeader from '../components/ui/PageHeader.vue';
import ProgressBar from '../components/ui/ProgressBar.vue';
import StatCard from '../components/ui/StatCard.vue';
import StateMessage from '../components/ui/StateMessage.vue';
import StatusBadge from '../components/ui/StatusBadge.vue';
import { useAsyncData } from '../composables/useAsyncData';
import { formatCurrency, formatDate, formatMonth, formatPercent } from '../utils/format';

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
        const [history, byGoal, monthly, status] = await Promise.all([
            reportsApi.history(params),
            reportsApi.goals(params),
            reportsApi.monthly(params),
            reportsApi.status(params),
        ]);

        return { history, byGoal, monthly, status };
    },
    { immediate: false },
);

watch(
    () => route.name === 'reports' && JSON.stringify(filters.value),
    (key) => key && reload(),
    { immediate: true },
);

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

const statusSegments = computed(() => [
    { key: 'active', label: 'Active', value: report.value?.status.active.count ?? 0, color: SERIES_COLORS[0] },
    { key: 'completed', label: 'Completed', value: report.value?.status.completed.count ?? 0, color: SERIES_COLORS[1] },
]);

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
                <StatCard label="Deposits" :value="formatCurrency(report.history.totals.deposits)" icon="arrowUp" />
                <StatCard label="Withdrawals" :value="formatCurrency(report.history.totals.withdrawals)" icon="arrowDown" />
                <StatCard label="Net saved" :value="formatCurrency(report.history.totals.net)" icon="wallet" />
                <StatCard label="Transactions" :value="report.history.totals.count" icon="clock" />
            </section>

            <!-- Monthly summary -->
            <section class="card p-5">
                <h2 class="font-semibold text-slate-900">Monthly saving summary</h2>
                <p class="mb-4 text-xs text-slate-500">Deposits and withdrawals per month</p>

                <template v-if="report.monthly.length">
                    <BarChart
                        :data="monthlyChart"
                        :series="monthlySeries"
                        :format-value="(value) => formatCurrency(value)"
                        :format-axis="(value) => formatCurrency(value, { compact: true })"
                        aria-label="Monthly deposits and withdrawals"
                    />
                    <details class="mt-4">
                        <summary class="cursor-pointer text-sm font-medium text-emerald-700">Show table</summary>
                        <div class="mt-3 overflow-x-auto">
                            <table class="table-base">
                                <thead>
                                    <tr>
                                        <th scope="col">Month</th>
                                        <th scope="col" class="text-right">Deposits</th>
                                        <th scope="col" class="text-right">Withdrawals</th>
                                        <th scope="col" class="text-right">Net</th>
                                        <th scope="col" class="text-right">Transactions</th>
                                    </tr>
                                </thead>
                                <tbody class="tabular-nums">
                                    <tr v-for="month in report.monthly" :key="month.month">
                                        <th scope="row" class="px-4 py-3 text-left font-medium">{{ formatMonth(month.month) }}</th>
                                        <td class="text-right">{{ formatCurrency(month.deposits) }}</td>
                                        <td class="text-right">{{ formatCurrency(month.withdrawals) }}</td>
                                        <td class="text-right font-medium">{{ formatCurrency(month.net) }}</td>
                                        <td class="text-right">{{ month.count }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </details>
                </template>
                <StateMessage v-else message="No transactions in this period." />
            </section>

            <section class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Summary by goal -->
                <div class="card lg:col-span-2">
                    <div class="px-5 pt-5 pb-2">
                        <h2 class="font-semibold text-slate-900">Saving summary by goal</h2>
                        <p class="text-xs text-slate-500">Net saved in the period, with overall progress</p>
                    </div>
                    <div v-if="report.byGoal.length" class="overflow-x-auto">
                        <table class="table-base">
                            <thead>
                                <tr>
                                    <th scope="col">Goal</th>
                                    <th scope="col" class="text-right">In period</th>
                                    <th scope="col" class="text-right">Saved / target</th>
                                    <th scope="col" class="w-40">Progress</th>
                                </tr>
                            </thead>
                            <tbody class="tabular-nums">
                                <tr v-for="row in report.byGoal" :key="row.goal_id">
                                    <td>
                                        <RouterLink :to="{ name: 'goals.show', params: { id: row.goal_id } }" class="font-medium text-slate-800 hover:text-emerald-700">
                                            {{ row.name }}
                                        </RouterLink>
                                        <div class="mt-1"><StatusBadge :status="row.status" /></div>
                                    </td>
                                    <td class="text-right whitespace-nowrap">
                                        <span class="font-medium">{{ formatCurrency(row.period.net) }}</span>
                                        <span class="block text-xs text-slate-500">{{ row.period.count }} transactions</span>
                                    </td>
                                    <td class="text-right whitespace-nowrap text-slate-600">
                                        {{ formatCurrency(row.saved_amount) }} / {{ formatCurrency(row.target_amount) }}
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <ProgressBar :value="row.progress" :completed="row.status === 'completed'" :label="`${row.name} progress`" />
                                            <span class="w-12 text-right text-xs text-slate-600">{{ formatPercent(row.progress) }}</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <StateMessage v-else message="No goals match this filter." />
                </div>

                <!-- Completed vs active -->
                <div class="card p-5">
                    <h2 class="font-semibold text-slate-900">Completed vs. active</h2>
                    <p class="mb-4 text-xs text-slate-500">Current status of goals</p>
                    <DonutChart
                        :segments="statusSegments"
                        :center-value="formatPercent(report.status.completion_rate)"
                        center-label="completion rate"
                        aria-label="Completed versus active goals"
                    />
                    <div class="mt-5 border-t border-slate-100 pt-4">
                        <h3 class="mb-2 text-sm font-medium text-slate-700">Completed in this period</h3>
                        <ul v-if="report.status.completed_in_period.length" class="flex flex-col gap-2 text-sm">
                            <li v-for="goal in report.status.completed_in_period" :key="goal.id" class="flex justify-between gap-2">
                                <RouterLink :to="{ name: 'goals.show', params: { id: goal.id } }" class="truncate text-slate-800 hover:text-emerald-700">{{ goal.name }}</RouterLink>
                                <span class="shrink-0 text-slate-500">{{ formatDate(goal.completed_at) }}</span>
                            </li>
                        </ul>
                        <p v-else class="text-sm text-slate-500">None yet.</p>
                    </div>
                </div>
            </section>

            <!-- History -->
            <section class="card">
                <div class="px-5 pt-5 pb-2">
                    <h2 class="font-semibold text-slate-900">Saving history</h2>
                    <p class="text-xs text-slate-500">{{ report.history.transactions.length }} transactions</p>
                </div>
                <TransactionTable v-if="report.history.transactions.length" :transactions="report.history.transactions" show-goal />
                <StateMessage v-else message="No transactions in this period." />
            </section>
        </template>
    </div>
</template>
