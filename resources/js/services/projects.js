import http from './http';

export const projectService = {
    list(params) {
        return http.get('/projects', { params });
    },
    mine(params) {
        return http.get('/projects/mine', { params });
    },
    show(id) {
        return http.get(`/projects/${id}`);
    },
    create(payload) {
        return http.post('/projects', payload);
    },
    update(id, payload) {
        return http.put(`/projects/${id}`, payload);
    },
    destroy(id) {
        return http.delete(`/projects/${id}`);
    },
    submit(id) {
        return http.post(`/projects/${id}/submit`);
    },
};
