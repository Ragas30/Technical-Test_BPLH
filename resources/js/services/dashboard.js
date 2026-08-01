import http from './http';

export const dashboardService = {
    get() {
        return http.get('/dashboard');
    },
};
