import { defineStore } from 'pinia';

let nextId = 0;

export const useToastStore = defineStore('toast', {
    state: () => ({
        toasts: [],
    }),

    actions: {
        push(message, type = 'success') {
            const id = ++nextId;
            this.toasts.push({ id, message, type });
            setTimeout(() => this.remove(id), 4500);
        },
        success(message) {
            this.push(message, 'success');
        },
        error(message) {
            this.push(message, 'error');
        },
        remove(id) {
            this.toasts = this.toasts.filter((toast) => toast.id !== id);
        },
    },
});
