<script setup>
import { computed, ref } from 'vue';
import { goalsApi } from '../../api/goals';
import GoalCard from '../../components/goals/GoalCard.vue';
import AppIcon from '../../components/ui/AppIcon.vue';
import PageHeader from '../../components/ui/PageHeader.vue';
import StateMessage from '../../components/ui/StateMessage.vue';
import { useAsyncData } from '../../composables/useAsyncData';

const { data: goals, loading, error, reload } = useAsyncData(() => goalsApi.list(), { initialData: [] });

const statusFilter = ref('all');
const search = ref('');

const tabs = computed(() => [
    { value: 'all', label: 'All', count: goals.value.length },
    { value: 'active', label: 'Active', count: goals.value.filter((goal) => goal.status === 'active').length },
    { value: 'completed', label: 'Completed', count: goals.value.filter((goal) => goal.status === 'completed').length },
]);

const visibleGoals = computed(() => {
    const term = search.value.trim().toLowerCase();

    return goals.value.filter(
        (goal) => (statusFilter.value === 'all' || goal.status === statusFilter.value) && (!term || goal.name.toLowerCase().includes(term)),
    );
});
</script>

<template>
    <PageHeader title="Savings goals" description="Create goals, set targets and track how close you are.">
        <template #actions>
            <RouterLink :to="{ name: 'goals.create' }" class="btn btn-primary">
                <AppIcon name="plus" :size="18" />
                New goal
            </RouterLink>
        </template>
    </PageHeader>

    <div v-if="goals.length" class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex gap-1 rounded-lg bg-slate-100 p-1" role="tablist" aria-label="Filter by status">
            <button
                v-for="tab in tabs"
                :key="tab.value"
                type="button"
                role="tab"
                :aria-selected="statusFilter === tab.value"
                class="rounded-md px-3 py-1.5 text-sm font-medium transition"
                :class="statusFilter === tab.value ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                @click="statusFilter = tab.value"
            >
                {{ tab.label }} <span class="text-slate-400 tabular-nums">{{ tab.count }}</span>
            </button>
        </div>
        <input v-model="search" type="search" class="input sm:max-w-xs" placeholder="Search goals…" aria-label="Search goals" />
    </div>

    <StateMessage v-if="loading && !goals.length" state="loading" />
    <StateMessage v-else-if="error" state="error" :message="error.message" @retry="reload" />
    <StateMessage v-else-if="!goals.length" class="card" title="No goals yet" message="Create your first savings goal to get started.">
        <RouterLink :to="{ name: 'goals.create' }" class="btn btn-primary">Create a goal</RouterLink>
    </StateMessage>
    <StateMessage v-else-if="!visibleGoals.length" class="card" title="No matching goals" message="Try another filter or search term." />

    <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <GoalCard v-for="goal in visibleGoals" :key="goal.id" :goal="goal" />
    </div>
</template>
