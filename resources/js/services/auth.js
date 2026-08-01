import http from './http';

export const authService = {
    login(payload) {
        return http.post('/auth/login', payload);
    },
    register(payload) {
        return http.post('/auth/register', payload);
    },
    profile() {
        return http.get('/auth/profile');
    },
    updateProfile(payload) {
        return http.put('/auth/profile', payload);
    },
    changePassword(payload) {
        return http.put('/auth/change-password', payload);
    },
    forgotPassword(email) {
        return http.post('/auth/forgot-password', { email });
    },
    resetPassword(payload) {
        return http.post('/auth/reset-password', payload);
    },
    logout() {
        return http.post('/auth/logout');
    },
};
