<script setup>
import { computed } from 'vue';
import { dashboardApi } from '../api/dashboard';
import BarChart from '../components/charts/BarChart.vue';
import DonutChart from '../components/charts/DonutChart.vue';
import { SERIES_COLORS } from '../components/charts/chartUtils';
import TransactionTable from '../components/transactions/TransactionTable.vue';
import AppIcon from '../components/ui/AppIcon.vue';
import PageHeader from '../components/ui/PageHeader.vue';
import ProgressBar from '../components/ui/ProgressBar.vue';
import StatCard from '../components/ui/StatCard.vue';
import StateMessage from '../components/ui/StateMessage.vue';
import { useAsyncData } from '../composables/useAsyncData';
import { formatCurrency, formatMonth, formatPercent } from '../utils/format';

const { data: overview, loading, error, reload } = useAsyncData(() => dashboardApi.overview());

const totals = computed(() => overview.value?.totals);

const monthlyChart = computed(() =>
    (overview.value?.monthly_savings ?? []).map((month) => ({
        label: formatMonth(month.month, { short: true }),
        tooltipLabel: formatMonth(month.month),
        values: { net: month.net },
    })),
);

const statusSegments = computed(() => [
    { key: 'active', label: 'Active', value: totals.value?.active_goals ?? 0, color: SERIES_COLORS[0] },
    { key: 'completed', label: 'Completed', value: totals.value?.completed_goals ?? 0, color: SERIES_COLORS[1] },
]);

const topGoals = computed(() => [...(overview.value?.goal_progress ?? [])].sort((a, b) => b.progress - a.progress).slice(0, 6));
</script>

<template>
    <PageHeader title="Dashboard" description="Your savings at a glance.">
        <template #actions>
            <RouterLink :to="{ name: 'goals.create' }" class="btn btn-primary">
                <AppIcon name="plus" :size="18" />
                New goal
            </RouterLink>
        </template>
    </PageHeader>

    <StateMessage v-if="loading && !overview" state="loading" />
    <StateMessage v-else-if="error" state="error" :message="error.message" @retry="reload" />

    <StateMessage
        v-else-if="totals && totals.goals_count === 0"
        title="Start your first saving challenge"
        message="Create a goal with a target amount, then log your savings to watch your progress grow."
        class="card"
    >
        <RouterLink :to="{ name: 'goals.create' }" class="btn btn-primary">Create a goal</RouterLink>
    </StateMessage>

    <div v-else-if="totals" class="flex flex-col gap-6">
        <!-- KPI tiles -->
        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Summary">
            <StatCard label="Total savings" :value="formatCurrency(totals.total_saved)" :hint="`${formatPercent(totals.overall_progress)} of all targets`" icon="wallet" />
            <StatCard label="Total target" :value="formatCurrency(totals.total_target)" :hint="`Across ${totals.goals_count} goals`" icon="flag" />
            <StatCard label="Remaining" :value="formatCurrency(totals.remaining)" hint="Still to save" icon="target" />
            <StatCard
                label="Goals"
                :value="`${totals.active_goals} active`"
                :hint="`${totals.completed_goals} completed`"
                icon="check"
            />
        </section>

        <!-- Charts -->
        <section class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="card p-5 lg:col-span-2">
                <h2 class="font-semibold text-slate-900">Net savings per month</h2>
                <p class="mb-4 text-xs text-slate-500">Deposits minus withdrawals, last {{ monthlyChart.length }} months</p>
                <BarChart
                    :data="monthlyChart"
                    :series="[{ key: 'net', label: 'Net saved', color: SERIES_COLORS[0] }]"
                    :format-value="(value) => formatCurrency(value)"
                    :format-axis="(value) => formatCurrency(value, { compact: true })"
                    aria-label="Net savings per month"
                />
            </div>

            <div class="card p-5">
                <h2 class="font-semibold text-slate-900">Goal status</h2>
                <p class="mb-4 text-xs text-slate-500">Completed vs. active goals</p>
                <DonutChart :segments="statusSegments" :center-value="formatPercent((totals.completed_goals / totals.goals_count) * 100)" center-label="completed" aria-label="Completed versus active goals" />
            </div>
        </section>

        <section class="grid grid-cols-1 gap-6 lg:grid-cols-5">
            <!-- Progress per goal -->
            <div class="card p-5 lg:col-span-2">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="font-semibold text-slate-900">Goal progress</h2>
                    <RouterLink :to="{ name: 'goals.index' }" class="text-sm font-medium text-emerald-700 hover:underline">View all</RouterLink>
                </div>
                <ul class="flex flex-col gap-4">
                    <li v-for="goal in topGoals" :key="goal.id">
                        <RouterLink :to="{ name: 'goals.show', params: { id: goal.id } }" class="group block">
                            <div class="mb-1.5 flex justify-between gap-2 text-sm">
                                <span class="truncate font-medium text-slate-800 group-hover:text-emerald-700">{{ goal.name }}</span>
                                <span class="text-slate-600 tabular-nums">{{ formatPercent(goal.progress) }}</span>
                            </div>
                            <ProgressBar :value="goal.progress" :completed="goal.status === 'completed'" :label="`${goal.name} progress`" />
                            <p class="mt-1 text-xs text-slate-500 tabular-nums">
                                {{ formatCurrency(goal.saved_amount) }} of {{ formatCurrency(goal.target_amount) }}
                            </p>
                        </RouterLink>
                    </li>
                </ul>
            </div>

            <!-- Recent activity -->
            <div class="card lg:col-span-3">
                <div class="flex items-center justify-between px-5 pt-5 pb-2">
                    <h2 class="font-semibold text-slate-900">Recent activity</h2>
                    <RouterLink :to="{ name: 'reports' }" class="text-sm font-medium text-emerald-700 hover:underline">Full history</RouterLink>
                </div>
                <TransactionTable v-if="overview.recent_activities.length" :transactions="overview.recent_activities" show-goal />
                <StateMessage v-else message="No savings logged yet. Open a goal to add your first saving." />
            </div>
        </section>
    </div>
</template>
