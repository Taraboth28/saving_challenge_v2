import { ref, shallowRef } from 'vue';

/**
 * Wraps an async loader with loading / error state and a reload function.
 *
 * const { data, loading, error, reload } = useAsyncData(() => goalsApi.list());
 */
export function useAsyncData(loader, { immediate = true, initialData = null } = {}) {
    const data = shallowRef(initialData);
    const loading = ref(false);
    const error = ref(null);
    let latestRequest = 0;

    async function reload(...args) {
        const requestId = ++latestRequest;
        loading.value = true;
        error.value = null;

        try {
            const result = await loader(...args);

            if (requestId === latestRequest) {
                data.value = result;
            }
        } catch (caught) {
            if (requestId === latestRequest) {
                error.value = caught;
            }
        } finally {
            if (requestId === latestRequest) {
                loading.value = false;
            }
        }
    }

    if (immediate) {
        reload();
    }

    return { data, loading, error, reload };
}
