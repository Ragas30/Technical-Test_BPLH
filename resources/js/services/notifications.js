import http from './http';

export const notificationService = {
    list(params) {
        return http.get('/notifications', { params });
    },
    unreadCount() {
        return http.get('/notifications/unread-count');
    },
    markAsRead(id) {
        return http.post(`/notifications/${id}/read`);
    },
    markAllAsRead() {
        return http.post('/notifications/read-all');
    },
};
