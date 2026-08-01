import http from './http';

export const activityLogService = {
    list(params) {
        return http.get('/activity-logs', { params });
    },
    mine(params) {
        return http.get('/activity-logs/mine', { params });
    },
};
