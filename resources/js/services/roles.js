import http from './http';

export const roleService = {
    list() {
        return http.get('/roles');
    },
    permissions() {
        return http.get('/permissions');
    },
};
