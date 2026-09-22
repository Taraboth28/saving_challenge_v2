<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { goalsApi } from '../../api/goals';
import { notifyDataChanged } from '../../composables/useDataChanged';
import { useSavingsPulse } from '../../composables/useSavingsPulse';
import { useTheme } from '../../composables/useTheme';
import { formatCurrency, formatPercent } from '../../utils/format';
import TransactionFormModal from '../transactions/TransactionFormModal.vue';
import AppIcon from '../ui/AppIcon.vue';
import { navigation } from './navigation';
import ProgressRing from './ProgressRing.vue';

/**
 * Floating black "Dynamic Island" navigation.
 *
 * Desktop: expanded at the top of the page or while hovered / focused; once the page scrolls
 * it morphs into a compact pill showing the current page and overall savings progress.
 * Mobile: always the compact pill; tapping the menu grows the island into a panel of links.
 *
 * The active page is marked by a single orange indicator that slides between links
 * (measured from the active link), rather than a highlight rebuilt on every click.
 */
const route = useRoute();
const { totals, refresh } = useSavingsPulse();
const { isDark, toggleTheme } = useTheme();

const scrolled = ref(false);
const hovered = ref(false);
const focused = ref(false);
const menuOpen = ref(false);

const expanded = computed(() => !scrolled.value || hovered.value || focused.value);
const isActive = (item) => String(route.name ?? '').startsWith(item.match);
const pageTitle = computed(() => route.meta.title ?? '');
const progress = computed(() => totals.value?.overall_progress ?? 0);
const progressLabel = computed(() =>
    totals.value
        ? `Overall savings progress: ${formatPercent(progress.value)} — ${formatCurrency(totals.value.total_saved)} saved of ${formatCurrency(totals.value.total_target)}`
        : 'Overall savings progress',
);

// "Add saving" dialog, available from every page.
const savingModalOpen = ref(false);
const savingGoals = ref([]);
const openingSaving = ref(false);
/** On a goal's page, preselect that goal. */
const currentGoalId = computed(() => (route.name === 'goals.show' ? Number(route.params.id) : null));

async function openAddSaving() {
    menuOpen.value = false;
    openingSaving.value = true;

    try {
        savingGoals.value = await goalsApi.list();
    } catch {
        savingGoals.value = [];
    } finally {
        openingSaving.value = false;
    }

    savingModalOpen.value = true;
}

function onSavingAdded() {
    refresh();
    notifyDataChanged();
}

const linkRefs = ref([]);
const indicator = ref({ left: 0, top: 0, width: 0, height: 0, visible: false, animate: false });

/** Move the sliding indicator under the active desktop link. */
function updateIndicator() {
    const link = linkRefs.value[navigation.findIndex(isActive)]?.$el;

    if (!link || link.offsetWidth === 0) {
        indicator.value = { ...indicator.value, visible: false, animate: false };

        return;
    }

    indicator.value = {
        left: link.offsetLeft,
        top: link.offsetTop,
        width: link.offsetWidth,
        height: link.offsetHeight,
        visible: true,
        // Slide only between two visible positions; appear in place the first time.
        animate: indicator.value.visible,
    };
}

function onScroll() {
    scrolled.value = window.scrollY > 24;
}

function onFocusOut(event) {
    if (!event.currentTarget.contains(event.relatedTarget)) {
        focused.value = false;
    }
}

onMounted(() => {
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', updateIndicator);
    updateIndicator();
    // Poppins changes link widths once it has loaded.
    document.fonts?.ready.then(updateIndicator);
});

onBeforeUnmount(() => {
    window.removeEventListener('scroll', onScroll);
    window.removeEventListener('resize', updateIndicator);
});

watch(() => route.name, () => nextTick(updateIndicator));

watch(
    () => route.fullPath,
    () => {
        menuOpen.value = false;
        savingModalOpen.value = false;
        refresh();
    },
    { immediate: true },
);
</script>

<template>
    <header class="pointer-events-none fixed inset-x-0 top-3 z-40 flex justify-center px-3 sm:top-4">
        <!-- Mobile backdrop: tap outside to close the expanded island -->
        <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" enter-active-class="transition duration-300" leave-active-class="transition duration-300">
            <div v-if="menuOpen" class="pointer-events-auto fixed inset-0 -z-10 bg-neutral-950/30 backdrop-blur-[2px] md:hidden" aria-hidden="true" @click="menuOpen = false" />
        </Transition>

        <div
            class="animate-island-in pointer-events-auto w-full max-w-sm overflow-hidden rounded-[28px] bg-neutral-950 text-white shadow-[0_12px_40px_-12px_rgb(0_0_0/0.6)] ring-1 ring-white/10 transition-shadow md:w-auto md:max-w-none dark:ring-white/20"
            @mouseenter="hovered = true"
            @mouseleave="hovered = false"
            @focusin="focused = true"
            @focusout="onFocusOut"
            @keydown.esc="menuOpen = false"
        >
            <div class="flex h-14 items-center gap-1 pr-2 pl-2">
                <!-- Logo -->
                <RouterLink
                    :to="{ name: 'dashboard' }"
                    class="grid size-10 shrink-0 place-items-center rounded-full bg-orange-500 text-neutral-950 transition-transform duration-300 ease-spring hover:scale-110 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500"
                    aria-label="Saving Challenge home"
                >
                    <AppIcon name="piggyBank" :size="20" />
                </RouterLink>

                <!-- Desktop links: slide open / closed -->
                <div
                    class="hidden transition-[grid-template-columns] duration-500 ease-spring md:grid motion-reduce:transition-none"
                    :class="expanded ? 'grid-cols-[1fr]' : 'grid-cols-[0fr]'"
                >
                    <nav class="relative flex min-w-0 items-center gap-1 overflow-hidden pl-2" aria-label="Main" :inert="!expanded || undefined">
                        <span
                            class="pointer-events-none absolute top-0 left-0 rounded-full bg-orange-500 motion-reduce:transition-none"
                            :class="[
                                indicator.animate ? 'transition-[transform,width] duration-500 ease-spring' : '',
                                indicator.visible ? 'opacity-100' : 'opacity-0',
                            ]"
                            :style="{
                                width: `${indicator.width}px`,
                                height: `${indicator.height}px`,
                                transform: `translate(${indicator.left}px, ${indicator.top}px)`,
                            }"
                            aria-hidden="true"
                        />
                        <RouterLink
                            v-for="item in navigation"
                            ref="linkRefs"
                            :key="item.label"
                            :to="item.to"
                            class="relative flex shrink-0 items-center gap-2 rounded-full px-4 py-2 text-sm font-medium whitespace-nowrap transition-colors duration-300 focus-visible:outline-2 focus-visible:outline-orange-500"
                            :class="isActive(item) ? 'text-neutral-950' : 'text-white/70 hover:bg-white/10 hover:text-white'"
                            :aria-current="isActive(item) ? 'page' : undefined"
                        >
                            <AppIcon :name="item.icon" :size="18" />
                            <span>{{ item.label }}</span>
                        </RouterLink>
                    </nav>
                </div>

                <!-- Compact label: current page (desktop when collapsed, always on mobile) -->
                <div
                    class="grid flex-1 transition-[grid-template-columns] duration-500 ease-spring motion-reduce:transition-none"
                    :class="expanded ? 'grid-cols-[1fr] md:grid-cols-[0fr]' : 'grid-cols-[1fr]'"
                >
                    <p class="min-w-0 overflow-hidden text-sm font-semibold whitespace-nowrap">
                        <span :key="pageTitle" class="animate-pop inline-block pl-3">{{ pageTitle }}</span>
                    </p>
                </div>

                <!-- Live activity: overall savings progress (an indicator, not a link) -->
                <div
                    class="ml-2 flex shrink-0 cursor-default items-center gap-2 rounded-full bg-white/5 py-1.5 pr-3 pl-1.5"
                    role="img"
                    :aria-label="progressLabel"
                    :title="progressLabel"
                >
                    <ProgressRing :value="progress" />
                    <span class="text-xs font-semibold text-orange-400 tabular-nums" aria-hidden="true">{{ formatPercent(progress) }}</span>
                    <span v-if="totals" class="hidden text-xs text-white/60 tabular-nums lg:inline" aria-hidden="true">{{ formatCurrency(totals.total_saved, { compact: true }) }}</span>
                </div>

                <!-- Day / night toggle -->
                <button
                    type="button"
                    class="ml-1 grid size-10 shrink-0 place-items-center rounded-full text-white/80 transition hover:bg-white/10 hover:text-orange-400 focus-visible:outline-2 focus-visible:outline-orange-500"
                    :aria-label="isDark ? 'Switch to day mode' : 'Switch to night mode'"
                    :title="isDark ? 'Day mode' : 'Night mode'"
                    @click="toggleTheme"
                >
                    <AppIcon :key="isDark ? 'sun' : 'moon'" :name="isDark ? 'sun' : 'moon'" :size="18" class="animate-pop" />
                </button>

                <!-- Add saving (desktop) -->
                <button
                    type="button"
                    class="ml-1 hidden h-10 shrink-0 items-center gap-1.5 rounded-full bg-orange-500 px-3 text-sm font-semibold text-neutral-950 transition-all duration-300 ease-spring hover:scale-105 hover:bg-orange-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500 disabled:opacity-70 md:flex"
                    aria-label="Add saving"
                    :disabled="openingSaving"
                    @click="openAddSaving"
                >
                    <AppIcon name="plus" :size="18" />
                    <span v-show="expanded">Add saving</span>
                </button>

                <!-- Menu toggle (mobile) -->
                <button
                    type="button"
                    class="ml-1 grid size-10 shrink-0 place-items-center rounded-full text-white/80 transition hover:bg-white/10 hover:text-white focus-visible:outline-2 focus-visible:outline-orange-500 md:hidden"
                    :aria-expanded="menuOpen"
                    aria-controls="island-menu"
                    :aria-label="menuOpen ? 'Close menu' : 'Open menu'"
                    @click="menuOpen = !menuOpen"
                >
                    <AppIcon :name="menuOpen ? 'close' : 'menu'" />
                </button>
            </div>

            <!-- Mobile panel: the island grows downward -->
            <div
                id="island-menu"
                class="grid transition-[grid-template-rows] md:hidden motion-reduce:transition-none"
                :class="menuOpen ? 'grid-rows-[1fr] duration-500 ease-spring' : 'grid-rows-[0fr] duration-200 ease-out'"
            >
                <div class="min-h-0 overflow-hidden" :inert="!menuOpen || undefined">
                    <nav class="flex flex-col gap-1 px-2 pt-1 pb-2" aria-label="Main">
                        <RouterLink
                            v-for="item in navigation"
                            :key="item.label"
                            :to="item.to"
                            class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium"
                            :class="isActive(item) ? 'bg-orange-500 text-neutral-950' : 'text-white/75 hover:bg-white/10 hover:text-white'"
                            :aria-current="isActive(item) ? 'page' : undefined"
                        >
                            <AppIcon :name="item.icon" :size="18" />
                            {{ item.label }}
                        </RouterLink>
                        <button
                            type="button"
                            class="mt-1 flex items-center justify-center gap-2 rounded-2xl bg-white px-4 py-3 text-sm font-semibold text-neutral-950 transition hover:bg-orange-400 disabled:opacity-70"
                            :disabled="openingSaving"
                            @click="openAddSaving"
                        >
                            <AppIcon name="plus" :size="18" />
                            Add saving
                        </button>
                    </nav>
                </div>
            </div>
        </div>

        <TransactionFormModal
            :open="savingModalOpen"
            :goals="savingGoals"
            :goal-id="currentGoalId"
            @close="savingModalOpen = false"
            @saved="onSavingAdded"
        />
    </header>
</template>
