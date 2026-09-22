import { ref } from 'vue';
import { dashboardApi } from '../api/dashboard';

/**
 * App-wide savings totals shown as the Dynamic Island's "live activity".
 * Shared state: every caller sees the same totals; call refresh() after data changes.
 */
const totals = ref(null);
let pending = null;

export function useSavingsPulse() {
    function refresh() {
        pending ??= dashboardApi
            .overview()
            .then((overview) => (totals.value = overview.totals))
            .catch(() => {})
            .finally(() => (pending = null));

        return pending;
    }

    return { totals, refresh };
}
