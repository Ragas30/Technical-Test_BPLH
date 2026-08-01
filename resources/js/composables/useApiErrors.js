import { ref } from 'vue';

export function useApiErrors() {
    const errors = ref({});

    function setErrors(error) {
        errors.value = error?.response?.data?.errors ?? {};
    }

    function resetErrors() {
        errors.value = {};
    }

    function fieldErrors(field) {
        return errors.value[field] ?? [];
    }

    return { errors, setErrors, resetErrors, fieldErrors };
}
