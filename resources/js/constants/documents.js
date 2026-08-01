export const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx', 'xlsx'];

export const MAX_UPLOAD_SIZE = 10 * 1024 * 1024;

export const MAX_FILES_PER_UPLOAD = 10;

export function formatBytes(bytes) {
    if (!bytes || bytes <= 0) return '0 B';

    const units = ['B', 'KB', 'MB', 'GB'];
    const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);

    return `${(bytes / 1024 ** index).toFixed(index === 0 ? 0 : 1)} ${units[index]}`;
}

export function extensionLabel(extension) {
    return `.${extension}`;
}
