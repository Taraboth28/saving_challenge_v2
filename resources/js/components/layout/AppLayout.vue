<script setup>
import DynamicIsland from './DynamicIsland.vue';
</script>

<template>
    <div class="min-h-screen">
        <DynamicIsland />

        <main class="mx-auto w-full max-w-6xl min-w-0 px-4 pt-24 pb-10 sm:px-6 sm:pt-28 lg:px-10">
            <slot />
        </main>
    </div>
</template>
