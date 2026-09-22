<script setup>
import { ref } from 'vue';

const count = ref(0);
</script>

<template>
    <main class="flex min-h-screen flex-col items-center justify-center gap-6 bg-slate-50 text-slate-800">
        <h1 class="text-4xl font-semibold">Saving Challenge</h1>
        <p class="text-slate-500">Laravel + Vue 3 + Tailwind CSS</p>
        <button
            class="rounded-lg bg-emerald-600 px-5 py-2 font-medium text-white hover:bg-emerald-700"
            @click="count++"
        >
            Clicked {{ count }} times
        </button>
    </main>
</template>
