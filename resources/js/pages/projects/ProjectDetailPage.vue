<script setup>
    import { computed, onBeforeUnmount, ref, watch } from 'vue';
    import { useRoute } from 'vue-router';
    import AppLayout from '../../layouts/AppLayout.vue';
    import ConfirmModal from '../../components/ConfirmModal.vue';
    import ReviewTimeline from '../../components/reviews/ReviewTimeline.vue';
    import UploadDocumentModal from '../../components/UploadDocumentModal.vue';
    import ReplaceDocumentModal from '../../components/ReplaceDocumentModal.vue';
    import { projectService } from '../../services/projects';
    import { documentService } from '../../services/documents';
    import { useAuthStore } from '../../stores/auth';
    import { useToastStore } from '../../stores/toast';
    import { PROJECT_STATUS } from '../../constants/roles';
    import { formatBytes } from '../../constants/documents';
    import { formatDate, initials } from '../../utils/format';

    const route = useRoute();
    const auth = useAuthStore();
    const toast = useToastStore();

    const loading = ref(true);
    const loadError = ref(false);
    const project = ref(null);
    const documents = ref([]);
    const documentsLoading = ref(false);

    const uploadModalOpen = ref(false);
    const replaceModalOpen = ref(false);
    const replacingDocument = ref(null);

    const previewUrl = ref(null);
    const previewName = ref('');
    const previewLoading = ref(false);
    const downloadingId = ref(null);
    const deletingId = ref(null);

    const confirmModalOpen = ref(false);
    const confirmModalRef = ref(null);
    const confirmAction = ref(null);

    const isOwner = computed(() => project.value?.user?.id === auth.user?.id);
    const canAccessDocuments = computed(
        () => auth.hasPermission('document.download') && (auth.isAdmin || auth.isReviewer || isOwner.value)
    );
    const canManageDocuments = computed(() => auth.hasPermission('document.upload') && (auth.isAdmin || isOwner.value));

    const canSubmit = computed(
        () =>
            isOwner.value &&
            auth.hasPermission('project.submit') &&
            (project.value?.status === 'draft' || project.value?.status === 'revision')
    );

    async function loadProject() {
        loading.value = true;
        loadError.value = false;

        try {
            const { data } = await projectService.show(route.params.id);
            project.value = data.data;

            if (canAccessDocuments.value) {
                await loadDocuments();
            }
        } catch {
            loadError.value = true;
        } finally {
            loading.value = false;
        }
    }

    async function loadDocuments() {
        documentsLoading.value = true;

        try {
            const { data } = await documentService.list(project.value.id);
            documents.value = data.data;
        } catch {
            toast.error('Gagal memuat dokumen project.');
        } finally {
            documentsLoading.value = false;
        }
    }

    function openUpload() {
        uploadModalOpen.value = true;
    }

    function openReplace(document) {
        replacingDocument.value = document;
        replaceModalOpen.value = true;
    }

    async function onDocumentsChanged() {
        await loadDocuments();
    }

    function isPdf(document) {
        return document.extension === 'pdf';
    }

    async function previewDocument(document) {
        previewLoading.value = true;

        try {
            const { data } = await documentService.preview(document.id);
            const url = URL.createObjectURL(new Blob([data], { type: document.mime_type }));
            previewUrl.value = url;
            previewName.value = document.original_name;
        } catch {
            toast.error('Gagal membuka pratinjau dokumen.');
        } finally {
            previewLoading.value = false;
        }
    }

    function closePreview() {
        if (previewUrl.value) {
            URL.revokeObjectURL(previewUrl.value);
        }
        previewUrl.value = null;
        previewName.value = '';
    }

    async function downloadDocument(document) {
        downloadingId.value = document.id;

        try {
            const { data } = await documentService.download(document.id);
            const url = URL.createObjectURL(new Blob([data], { type: document.mime_type }));
            const link = document.createElement('a');
            link.href = url;
            link.download = document.original_name;
            document.body.appendChild(link);
            link.click();
            link.remove();
            URL.revokeObjectURL(url);
        } catch {
            toast.error('Gagal mengunduh dokumen.');
        } finally {
            downloadingId.value = null;
        }
    }

    async function removeDocument(document) {
        deletingId.value = document.id;

        try {
            await documentService.destroy(document.id);
            toast.success('Dokumen berhasil dihapus.');
            await loadDocuments();
        } catch {
            toast.error('Gagal menghapus dokumen.');
        } finally {
            deletingId.value = null;
        }
    }

    function openRemove(document) {
        confirmAction.value = {
            title: 'Hapus Dokumen',
            message: `Hapus dokumen "${document.original_name}"? Tindakan ini tidak dapat dibatalkan.`,
            confirmLabel: 'Hapus',
            confirmClass: 'btn-error',
            run: () => removeDocument(document),
        };
        confirmModalOpen.value = true;
    }

    async function handleConfirm() {
        const action = confirmAction.value;
        confirmModalOpen.value = false;
        confirmAction.value = null;

        try {
            await action.run();
        } catch (error) {
            toast.error(error?.response?.data?.message ?? 'Terjadi kesalahan, coba lagi.');
        }
    }

    function openSubmit() {
        const isRevision = project.value?.status === 'revision';

        confirmAction.value = {
            title: isRevision ? 'Ajukan Ulang Project' : 'Ajukan Project',
            message: isRevision
                ? `Ajukan ulang project "${project.value?.title}" untuk direview kembali?`
                : `Ajukan project "${project.value?.title}" untuk review?`,
            confirmLabel: isRevision ? 'Ajukan Ulang' : 'Ajukan',
            confirmClass: 'btn-primary',
            run: submitProject,
        };
        confirmModalOpen.value = true;
    }

    async function submitProject() {
        try {
            await projectService.submit(project.value.id);
            toast.success('Project berhasil diajukan.');
            await loadProject();
        } catch (error) {
            toast.error(
                error?.response?.data?.errors?.status?.[0] ??
                    error?.response?.data?.message ??
                    'Gagal mengajukan project.'
            );
        }
    }

    watch(
        () => route.params.id,
        () => loadProject(),
        { immediate: true }
    );

    onBeforeUnmount(closePreview);
</script>

<template>
    <AppLayout>
        <div v-if="loading" class="flex justify-center py-20">
            <span class="loading loading-spinner loading-lg text-primary" />
        </div>

        <div v-else-if="loadError" class="flex flex-col items-center justify-center gap-3 py-20">
            <p class="text-sm text-base-content/60">Project tidak ditemukan atau Anda tidak berhak mengaksesnya.</p>
            <RouterLink to="/projects" class="btn btn-primary btn-sm">Kembali ke Daftar Project</RouterLink>
        </div>

        <div v-else-if="project">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <RouterLink to="/projects" class="btn btn-ghost btn-sm" aria-label="Kembali">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2"
                            stroke="currentColor"
                            class="h-4 w-4"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                        </svg>
                    </RouterLink>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-2xl font-semibold">{{ project.title }}</h2>
                            <span class="badge" :class="PROJECT_STATUS[project.status]?.badge ?? 'badge-neutral'">
                                {{ PROJECT_STATUS[project.status]?.label ?? project.status }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm font-mono text-base-content/50">{{ project.project_number }}</p>
                    </div>
                </div>

                <button v-if="canSubmit" type="button" class="btn btn-info" @click="openSubmit">
                    {{ project.status === 'revision' ? 'Ajukan Ulang' : 'Ajukan' }}
                </button>

                <button v-if="canManageDocuments" type="button" class="btn btn-primary" @click="openUpload">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                        class="h-5 w-5"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z"
                        />
                    </svg>
                    Unggah Dokumen
                </button>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="card border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body">
                        <h3 class="card-title text-base">Informasi Project</h3>

                        <div class="space-y-4 text-sm">
                            <div>
                                <p class="text-xs font-semibold text-base-content/50">Deskripsi</p>
                                <p class="mt-1 whitespace-pre-line">{{ project.description || '-' }}</p>
                            </div>

                            <div v-if="project.user">
                                <p class="text-xs font-semibold text-base-content/50">Pemilik</p>
                                <div class="mt-1 flex items-center gap-2">
                                    <div class="avatar placeholder">
                                        <div
                                            class="flex h-8 w-8 items-center justify-center rounded-full bg-primary/15 text-xs font-semibold text-primary"
                                        >
                                            {{ initials(project.user.name) }}
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-sm">{{ project.user.name }}</p>
                                        <p class="text-xs text-base-content/50">{{ project.user.email }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <p class="text-xs font-semibold text-base-content/50">Dibuat</p>
                                    <p class="mt-1">{{ formatDate(project.created_at) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-base-content/50">Diajukan</p>
                                    <p class="mt-1">{{ formatDate(project.submitted_at) }}</p>
                                </div>
                            </div>

                            <div>
                                <p class="text-xs font-semibold text-base-content/50">Slug</p>
                                <p class="mt-1 break-all font-mono text-xs">{{ project.slug }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border border-base-300 bg-base-100 shadow-sm lg:col-span-2">
                    <div class="card-body">
                        <div class="flex items-center justify-between">
                            <h3 class="card-title text-base">Dokumen</h3>
                            <span v-if="documents.length" class="badge badge-ghost">{{ documents.length }} berkas</span>
                        </div>

                        <div v-if="!canAccessDocuments" class="py-8 text-center text-sm text-base-content/50">
                            Anda tidak memiliki akses ke dokumen project ini.
                        </div>

                        <div v-else-if="documentsLoading" class="flex justify-center py-12">
                            <span class="loading loading-spinner loading-lg text-primary" />
                        </div>

                        <div v-else-if="documents.length === 0" class="py-8 text-center text-sm text-base-content/50">
                            Belum ada dokumen.
                            {{ canManageDocuments ? 'Klik "Unggah Dokumen" untuk menambahkan.' : '' }}
                        </div>

                        <div v-else class="space-y-2">
                            <div
                                v-for="document in documents"
                                :key="document.id"
                                class="flex flex-wrap items-center justify-between gap-3 rounded-box border border-base-300 bg-base-100 px-3 py-2.5"
                            >
                                <div class="flex min-w-0 items-center gap-3">
                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-box bg-primary/10 text-xs font-bold text-primary"
                                    >
                                        {{ document.extension.toUpperCase() }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium">{{ document.original_name }}</p>
                                        <p class="text-xs text-base-content/50">
                                            {{ formatBytes(document.size) }} · oleh
                                            {{ document.uploader?.name ?? '-' }} · {{ formatDate(document.created_at) }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex shrink-0 items-center gap-1">
                                    <button
                                        v-if="isPdf(document)"
                                        type="button"
                                        class="btn btn-ghost btn-xs"
                                        :disabled="previewLoading"
                                        @click="previewDocument(document)"
                                    >
                                        Pratinjau
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-ghost btn-xs"
                                        :disabled="downloadingId === document.id"
                                        @click="downloadDocument(document)"
                                    >
                                        <span
                                            v-if="downloadingId === document.id"
                                            class="loading loading-spinner loading-xs"
                                        />
                                        Unduh
                                    </button>
                                    <button
                                        v-if="canManageDocuments"
                                        type="button"
                                        class="btn btn-ghost btn-xs"
                                        @click="openReplace(document)"
                                    >
                                        Ganti
                                    </button>
                                    <button
                                        v-if="canManageDocuments"
                                        type="button"
                                        class="btn btn-ghost btn-xs text-error"
                                        :disabled="deletingId === document.id"
                                        @click="openRemove(document)"
                                    >
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <ReviewTimeline :reviews="project.reviews ?? []" />
            </div>

            <UploadDocumentModal v-model="uploadModalOpen" :project-id="project.id" @uploaded="onDocumentsChanged" />

            <ReplaceDocumentModal
                v-model="replaceModalOpen"
                :document-id="replacingDocument?.id"
                :current-name="replacingDocument?.original_name"
                @replaced="onDocumentsChanged"
            />

            <ConfirmModal
                ref="confirmModalRef"
                v-model="confirmModalOpen"
                :title="confirmAction?.title ?? 'Konfirmasi'"
                :message="confirmAction?.message ?? ''"
                :confirm-label="confirmAction?.confirmLabel ?? 'Konfirmasi'"
                :confirm-class="confirmAction?.confirmClass ?? 'btn-primary'"
                @confirm="handleConfirm"
            />

            <dialog class="modal" :class="{ 'modal-open': previewUrl || previewLoading }">
                <div class="modal-box max-w-4xl overflow-y-auto">
                    <button
                        type="button"
                        class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3"
                        aria-label="Tutup pratinjau"
                        @click="closePreview"
                    >
                        ✕
                    </button>

                    <h3 class="mb-3 truncate pr-8 text-lg font-semibold">{{ previewName }}</h3>

                    <div v-if="previewLoading" class="flex justify-center py-20">
                        <span class="loading loading-spinner loading-lg text-primary" />
                    </div>

                    <iframe
                        v-else-if="previewUrl"
                        :src="previewUrl"
                        class="h-[70vh] w-full rounded-box border border-base-300 bg-white"
                        title="Pratinjau dokumen"
                    />
                </div>

                <form method="dialog" class="modal-backdrop">
                    <button type="button" @click="closePreview">tutup</button>
                </form>
            </dialog>
        </div>
    </AppLayout>
</template>
