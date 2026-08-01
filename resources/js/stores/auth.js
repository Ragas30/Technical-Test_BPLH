import { defineStore } from 'pinia';
import { authService } from '../services/auth';
import { TOKEN_KEY } from '../services/http';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        token: localStorage.getItem(TOKEN_KEY) ?? null,
        user: null,
    }),

    getters: {
        isAuthenticated: (state) => Boolean(state.token),
        roles: (state) => state.user?.roles ?? [],
        permissions: (state) => state.user?.permissions ?? [],
        isAdmin: (state) => (state.user?.roles ?? []).includes('admin'),
        isReviewer: (state) => (state.user?.roles ?? []).includes('reviewer'),
        isApplicant: (state) => (state.user?.roles ?? []).includes('applicant'),
        hasPermission: (state) => (permission) => (state.user?.permissions ?? []).includes(permission),
    },

    actions: {
        async login(payload) {
            const { data } = await authService.login(payload);
            this.token = data.data.token;
            this.user = data.data.user;
            localStorage.setItem(TOKEN_KEY, this.token);
        },

        async register(payload) {
            const { data } = await authService.register(payload);
            this.token = data.data.token;
            this.user = data.data.user;
            localStorage.setItem(TOKEN_KEY, this.token);
        },

        async fetchProfile() {
            const { data } = await authService.profile();
            this.user = data.data;
        },

        async logout() {
            try {
                await authService.logout();
            } finally {
                this.clearSession();
            }
        },

        clearSession() {
            this.token = null;
            this.user = null;
            localStorage.removeItem(TOKEN_KEY);
        },
    },
});
