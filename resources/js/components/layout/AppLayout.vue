<script setup>
import { ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { appConfig } from '../../config';
import AppIcon from '../ui/AppIcon.vue';
import { navigation } from './navigation';

const route = useRoute();
const menuOpen = ref(false);

const isActive = (item) => String(route.name ?? '').startsWith(item.match);

watch(() => route.fullPath, () => (menuOpen.value = false));
</script>

<template>
    <div class="min-h-screen lg:flex">
        <!-- Mobile top bar -->
        <header class="sticky top-0 z-30 flex items-center justify-between border-b border-slate-200 bg-white px-4 py-3 lg:hidden">
            <RouterLink :to="{ name: 'dashboard' }" class="flex items-center gap-2 font-semibold">
                <span class="grid size-8 place-items-center rounded-lg bg-emerald-600 text-white"><AppIcon name="wallet" :size="18" /></span>
                {{ appConfig.name }}
            </RouterLink>
            <button type="button" class="btn btn-ghost" :aria-expanded="menuOpen" aria-label="Toggle menu" @click="menuOpen = !menuOpen">
                <AppIcon :name="menuOpen ? 'close' : 'menu'" />
            </button>
        </header>

        <!-- Sidebar -->
        <aside
            class="fixed inset-x-0 top-[57px] z-20 border-b border-slate-200 bg-white p-4 lg:sticky lg:top-0 lg:flex lg:h-screen lg:w-64 lg:shrink-0 lg:flex-col lg:border-r lg:border-b-0"
            :class="menuOpen ? 'block' : 'hidden lg:flex'"
        >
            <RouterLink :to="{ name: 'dashboard' }" class="mb-8 hidden items-center gap-2 px-2 text-lg font-semibold lg:flex">
                <span class="grid size-9 place-items-center rounded-lg bg-emerald-600 text-white"><AppIcon name="wallet" /></span>
                {{ appConfig.name }}
            </RouterLink>

            <nav class="flex flex-col gap-1">
                <RouterLink
                    v-for="item in navigation"
                    :key="item.label"
                    :to="item.to"
                    class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition"
                    :class="isActive(item) ? 'bg-emerald-50 text-emerald-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                >
                    <AppIcon :name="item.icon" />
                    {{ item.label }}
                </RouterLink>
            </nav>

            <RouterLink :to="{ name: 'goals.create' }" class="btn btn-primary mt-6 w-full lg:mt-auto">
                <AppIcon name="plus" :size="18" />
                New goal
            </RouterLink>
        </aside>

        <main class="mx-auto w-full max-w-6xl min-w-0 flex-1 px-4 py-6 sm:px-6 lg:px-10 lg:py-10">
            <slot />
        </main>
    </div>
</template>
