<script setup>
import AppLayout from './components/layout/AppLayout.vue';
</script>

<template>
    <AppLayout>
        <RouterView v-slot="{ Component, route }">
            <Transition
                mode="out-in"
                enter-active-class="transition duration-200 ease-out motion-reduce:transition-none"
                enter-from-class="translate-y-1 opacity-0"
                leave-active-class="transition duration-100 ease-in motion-reduce:transition-none"
                leave-to-class="opacity-0"
            >
                <div :key="route.path">
                    <component :is="Component" />
                </div>
            </Transition>
        </RouterView>
    </AppLayout>
</template>
