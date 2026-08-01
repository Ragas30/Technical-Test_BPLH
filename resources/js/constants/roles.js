export const PROJECT_STATUS = {
    draft: { label: 'Draft', badge: 'badge-neutral' },
    submitted: { label: 'Diajukan', badge: 'badge-info' },
    under_review: { label: 'Sedang Ditinjau', badge: 'badge-warning' },
    revision: { label: 'Revisi', badge: 'badge-secondary' },
    rejected: { label: 'Ditolak', badge: 'badge-error' },
    approved: { label: 'Disetujui', badge: 'badge-success' },
};

export const REVIEW_STATUS = {
    pending: { label: 'Menunggu', badge: 'badge-neutral' },
    under_review: { label: 'Sedang Ditinjau', badge: 'badge-warning' },
    approved: { label: 'Disetujui', badge: 'badge-success' },
    rejected: { label: 'Ditolak', badge: 'badge-error' },
    revision: { label: 'Revisi', badge: 'badge-secondary' },
};

export const REVIEW_LOG_ACTIONS = {
    submitted: { label: 'Diajukan', badge: 'badge-info' },
    under_review: { label: 'Sedang Ditinjau', badge: 'badge-warning' },
    approved: { label: 'Disetujui', badge: 'badge-success' },
    rejected: { label: 'Ditolak', badge: 'badge-error' },
    revision: { label: 'Revisi diminta', badge: 'badge-secondary' },
    comment: { label: 'Komentar', badge: 'badge-ghost' },
};

export const ACTION_LABELS = {
    login: 'Login',
    logout: 'Logout',
    project_created: 'Project dibuat',
    project_updated: 'Project diperbarui',
    project_deleted: 'Project dihapus',
    project_submitted: 'Project diajukan',
    document_uploaded: 'Dokumen diunggah',
    document_deleted: 'Dokumen dihapus',
    review_started: 'Review dimulai',
    review_approved: 'Review disetujui',
    review_rejected: 'Review ditolak',
    revision_requested: 'Revisi diminta',
};

export const ROLE_OPTIONS = [
    { value: 'admin', label: 'Admin' },
    { value: 'reviewer', label: 'Reviewer' },
    { value: 'applicant', label: 'Applicant' },
];

export function roleLabel(role) {
    return ROLE_OPTIONS.find((option) => option.value === role)?.label ?? role;
}
