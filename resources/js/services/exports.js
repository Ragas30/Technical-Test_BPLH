import http from './http';

function triggerDownload(response, fallbackName) {
    const disposition = response.headers?.['content-disposition'] ?? '';
    const match = disposition.match(/filename\*?=(?:UTF-8'')?"?([^";]+)"?/i);
    const filename = match ? decodeURIComponent(match[1]) : fallbackName;

    const url = window.URL.createObjectURL(response.data);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
}

function download(url, params, fallbackName) {
    return http
        .get(url, { params, responseType: 'blob' })
        .then((response) => triggerDownload(response, fallbackName));
}

export const exportService = {
    projectsExcel(params) {
        return download('/export/projects', params, 'projects.xlsx');
    },
    projectsPdf(params) {
        return download('/export/projects/pdf', params, 'projects.pdf');
    },
    reviewsExcel(params) {
        return download('/export/reviews', params, 'reviews.xlsx');
    },
    reviewsPdf(params) {
        return download('/export/reviews/pdf', params, 'reviews.pdf');
    },
};
