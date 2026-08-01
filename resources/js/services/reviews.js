import http from './http';

export const reviewService = {
    list(params) {
        return http.get('/reviews', { params });
    },
    show(id) {
        return http.get(`/reviews/${id}`);
    },
    start(projectId) {
        return http.post(`/projects/${projectId}/reviews`);
    },
    approve(id, payload) {
        return http.post(`/reviews/${id}/approve`, payload);
    },
    reject(id, payload) {
        return http.post(`/reviews/${id}/reject`, payload);
    },
    revision(id, payload) {
        return http.post(`/reviews/${id}/revision`, payload);
    },
    comment(id, payload) {
        return http.post(`/reviews/${id}/comment`, payload);
    },
};
