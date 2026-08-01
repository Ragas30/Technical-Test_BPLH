export function formatDate(value) {
    if (!value) return '-';

    return new Date(value).toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

export function initials(name) {
    if (!name) return '?';

    return name
        .split(' ')
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase();
}

export function firstErrorMessage(error) {
    const errors = error?.response?.data?.errors;

    if (errors) {
        const firstKey = Object.keys(errors)[0];

        if (firstKey && errors[firstKey]?.length) {
            return errors[firstKey][0];
        }
    }

    return error?.response?.data?.message ?? 'Terjadi kesalahan. Silakan coba lagi.';
}
