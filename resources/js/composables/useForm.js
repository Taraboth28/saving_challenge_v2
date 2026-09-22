import { reactive, ref } from 'vue';

/**
 * Form state with server-side validation error handling.
 *
 * const form = useForm({ name: '' });
 * await form.submit((values) => goalsApi.create(values));
 */
export function useForm(initialValues) {
    const values = reactive({ ...initialValues });
    const errors = ref({});
    const message = ref('');
    const processing = ref(false);

    function reset(nextValues = initialValues) {
        Object.assign(values, nextValues);
        errors.value = {};
        message.value = '';
    }

    function error(field) {
        return errors.value[field]?.[0] ?? '';
    }

    async function submit(action) {
        processing.value = true;
        errors.value = {};
        message.value = '';

        try {
            return await action({ ...values });
        } catch (caught) {
            errors.value = caught.errors ?? {};
            message.value = caught.isValidationError ? '' : caught.message;
            throw caught;
        } finally {
            processing.value = false;
        }
    }

    return { values, errors, message, processing, error, reset, submit };
}
