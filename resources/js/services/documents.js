import http from './http';

export const documentService = {
    list(projectId) {
        return http.get(`/projects/${projectId}/documents`);
    },
    upload(projectId, formData) {
        return http.post(`/projects/${projectId}/documents`, formData);
    },
    replace(documentId, formData) {
        return http.post(`/documents/${documentId}/replace`, formData);
    },
    destroy(documentId) {
        return http.delete(`/documents/${documentId}`);
    },
    download(documentId) {
        return http.get(`/documents/${documentId}/download`, { responseType: 'blob' });
    },
    preview(documentId) {
        return http.get(`/documents/${documentId}/preview`, { responseType: 'blob' });
    },
};
