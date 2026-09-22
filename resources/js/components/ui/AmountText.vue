<script setup>
import { computed } from 'vue';
import { formatCurrency } from '../../utils/format';

/**
 * A transaction amount with its direction: "+$50.00" for deposits, "−$20.00" for withdrawals.
 */
const props = defineProps({
    amount: { type: Number, required: true },
    type: { type: String, default: 'deposit' },
});

const isWithdrawal = computed(() => props.type === 'withdrawal');
</script>

<template>
    <span class="font-medium tabular-nums" :class="isWithdrawal ? 'text-rose-700' : 'text-emerald-700'">
        {{ isWithdrawal ? '−' : '+' }}{{ formatCurrency(amount) }}
    </span>
</template>
