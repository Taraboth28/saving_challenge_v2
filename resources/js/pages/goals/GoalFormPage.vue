<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { goalsApi } from '../../api/goals';
import GoalForm from '../../components/goals/GoalForm.vue';
import AppIcon from '../../components/ui/AppIcon.vue';
import PageHeader from '../../components/ui/PageHeader.vue';
import StateMessage from '../../components/ui/StateMessage.vue';
import { useAsyncData } from '../../composables/useAsyncData';

/**
 * Handles both /goals/new (no id) and /goals/:id/edit.
 */
const props = defineProps({
    id: { type: Number, default: null },
});

const router = useRouter();
const isEditing = computed(() => props.id !== null);

const { data, loading, error, reload } = useAsyncData(() => goalsApi.find(props.id), { immediate: isEditing.value });

const action = (values) => (isEditing.value ? goalsApi.update(props.id, values) : goalsApi.create(values));

function goBack() {
    router.push(isEditing.value ? { name: 'goals.show', params: { id: props.id } } : { name: 'goals.index' });
}

function onSaved(goal) {
    router.push({ name: 'goals.show', params: { id: goal.id } });
}
</script>

<template>
    <div class="mx-auto max-w-2xl">
        <PageHeader :title="isEditing ? 'Edit goal' : 'New savings goal'" :description="isEditing ? '' : 'Set a target amount and, optionally, a date to reach it.'">
            <template #back>
                <button type="button" class="mb-2 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-800" @click="goBack">
                    <AppIcon name="arrowLeft" :size="16" />
                    Back
                </button>
            </template>
        </PageHeader>

        <template v-if="isEditing">
            <StateMessage v-if="loading" state="loading" />
            <StateMessage v-else-if="error" state="error" :message="error.message" @retry="reload" />
            <GoalForm v-else-if="data" :goal="data.goal" :action="action" submit-label="Save changes" @saved="onSaved" @cancel="goBack" />
        </template>

        <GoalForm v-else :action="action" submit-label="Create goal" @saved="onSaved" @cancel="goBack" />
    </div>
</template>
