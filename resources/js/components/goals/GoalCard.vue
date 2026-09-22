<script setup>
import { describeDaysLeft, formatCurrency, formatPercent } from '../../utils/format';
import ProgressBar from '../ui/ProgressBar.vue';
import StatusBadge from '../ui/StatusBadge.vue';

defineProps({
    goal: { type: Object, required: true },
});
</script>

<template>
    <RouterLink
        :to="{ name: 'goals.show', params: { id: goal.id } }"
        class="card group flex flex-col gap-4 p-5 transition hover:border-emerald-300 hover:shadow-md focus-visible:outline-2 focus-visible:outline-emerald-600"
    >
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h3 class="truncate font-semibold text-slate-900 group-hover:text-emerald-700">{{ goal.name }}</h3>
                <p class="mt-0.5 text-xs text-slate-500">{{ describeDaysLeft(goal) }}</p>
            </div>
            <StatusBadge :status="goal.status" :overdue="goal.is_overdue" />
        </div>

        <div>
            <div class="mb-2 flex items-baseline justify-between">
                <span class="text-xl font-semibold text-slate-900 tabular-nums">{{ formatCurrency(goal.saved_amount) }}</span>
                <span class="text-sm font-medium text-slate-600 tabular-nums">{{ formatPercent(goal.progress) }}</span>
            </div>
            <ProgressBar :value="goal.progress" :completed="goal.status === 'completed'" :label="`${goal.name} progress`" />
        </div>

        <dl class="grid grid-cols-2 gap-2 text-xs">
            <div>
                <dt class="text-slate-500">Target</dt>
                <dd class="font-medium text-slate-800 tabular-nums">{{ formatCurrency(goal.target_amount) }}</dd>
            </div>
            <div class="text-right">
                <dt class="text-slate-500">Remaining</dt>
                <dd class="font-medium text-slate-800 tabular-nums">{{ formatCurrency(goal.remaining_amount) }}</dd>
            </div>
        </dl>
    </RouterLink>
</template>
