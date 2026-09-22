<script setup>
import StateMessage from '../components/ui/StateMessage.vue';
</script>

<template>
    <StateMessage class="card" title="Page not found" message="The page you are looking for does not exist.">
        <RouterLink :to="{ name: 'dashboard' }" class="btn btn-primary">Go to dashboard</RouterLink>
    </StateMessage>
</template>
