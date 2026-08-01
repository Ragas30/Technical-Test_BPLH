import http from './http';

export const userService = {
    list(params) {
        return http.get('/users', { params });
    },
    show(id) {
        return http.get(`/users/${id}`);
    },
    create(payload) {
        return http.post('/users', payload);
    },
    update(id, payload) {
        return http.put(`/users/${id}`, payload);
    },
    destroy(id) {
        return http.delete(`/users/${id}`);
    },
    restore(id) {
        return http.post(`/users/${id}/restore`);
    },
    assignRoles(id, roles) {
        return http.put(`/users/${id}/roles`, { roles });
    },
    assignPermissions(id, permissions) {
        return http.put(`/users/${id}/permissions`, { permissions });
    },
};
