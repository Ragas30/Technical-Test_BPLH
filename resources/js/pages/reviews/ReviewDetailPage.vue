<script setup>
    import { computed, onBeforeUnmount, ref, watch } from 'vue';
    import { useRoute } from 'vue-router';
    import AppLayout from '../../layouts/AppLayout.vue';
    import ReviewDecisionModal from '../../components/reviews/ReviewDecisionModal.vue';
    import { reviewService } from '../../services/reviews';
    import { documentService } from '../../services/documents';
    import { useAuthStore } from '../../stores/auth';
    import { useToastStore } from '../../stores/toast';
    import { PROJECT_STATUS, REVIEW_LOG_ACTIONS, REVIEW_STATUS } from '../../constants/roles';
    import { formatBytes } from '../../constants/documents';
    import { formatDate, initials } from '../../utils/format';

    const route = useRoute();
    const auth = useAuthStore();
    const toast = useToastStore();

    const loading = ref(true);
    const loadError = ref(false);
    const review = ref(null);
    const documents = ref([]);
    const documentsLoading = ref(false);

    const decisionModalOpen = ref(false);
    const decisionType = ref('approve');
    const decisionModalRef = ref(null);

    const previewUrl = ref(null);
    const previewName = ref('');
    const previewLoading = ref(false);
    const downloadingId = ref(null);

    const canDecide = computed(
        () =>
            review.value?.status === 'under_review' &&
            review.value?.reviewer?.id === auth.user?.id &&
            auth.hasPermission('review.approve')
    );

    const canComment = computed(
        () => review.value?.reviewer?.id === auth.user?.id && auth.hasPermission('review.comment')
    );

    const timeline = computed(() => [...(review.value?.logs ?? [])].reverse());

    async function loadReview() {
        loading.value = true;
        loadError.value = false;

        try {
            const { data } = await reviewService.show(route.params.id);
            review.value = data.data;

            await loadDocuments();
        } catch {
            loadError.value = true;
        } finally {
            loading.value = false;
        }
    }

    async function loadDocuments() {
        documentsLoading.value = true;

        try {
            const { data } = await documentService.list(review.value.project.id);
            documents.value = data.data;
        } catch {
            toast.error('Gagal memuat dokumen project.');
        } finally {
            documentsLoading.value = false;
        }
    }

    function openDecision(type) {
        decisionType.value = type;
        decisionModalOpen.value = true;
    }

    async function submitDecision({ notes }) {
        decisionModalRef.value?.setSubmitting(true);

        const calls = {
            approve: reviewService.approve,
            reject: reviewService.reject,
            revision: reviewService.revision,
            comment: reviewService.comment,
        };

        try {
            await calls[decisionType.value](review.value.id, { notes });
            toast.success('Tindakan review berhasil.');
            decisionModalOpen.value = false;
            await loadReview();
        } catch (error) {
            toast.error(error?.response?.data?.errors?.notes?.[0] ?? 'Gagal memproses review.');
        } finally {
            decisionModalRef.value?.setSubmitting(false);
        }
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

    watch(
        () => route.params.id,
        () => loadReview(),
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
            <p class="text-sm text-base-content/60">Review tidak ditemukan atau Anda tidak berhak mengaksesnya.</p>
            <RouterLink to="/reviews" class="btn btn-primary btn-sm">Kembali ke Daftar Review</RouterLink>
        </div>

        <div v-else-if="review">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <RouterLink to="/reviews" class="btn btn-ghost btn-sm" aria-label="Kembali">
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
                            <h2 class="text-2xl font-semibold">{{ review.project?.title }}</h2>
                            <span class="badge whitespace-nowrap" :class="REVIEW_STATUS[review.status]?.badge ?? 'badge-neutral'">
                                {{ REVIEW_STATUS[review.status]?.label ?? review.status }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm font-mono text-base-content/50">{{ review.project?.project_number }}</p>
                    </div>
                </div>

                <div v-if="canDecide" class="flex flex-wrap gap-2">
                    <button type="button" class="btn btn-primary btn-sm" @click="openDecision('approve')">
                        Setujui
                    </button>
                    <button type="button" class="btn btn-error btn-sm" @click="openDecision('reject')">Tolak</button>
                    <button type="button" class="btn btn-warning btn-sm" @click="openDecision('revision')">
                        Minta Revisi
                    </button>
                    <button
                        v-if="canComment"
                        type="button"
                        class="btn btn-info btn-sm btn-outline"
                        @click="openDecision('comment')"
                    >
                        Komentar
                    </button>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="card border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body">
                        <h3 class="card-title text-base">Informasi Project</h3>

                        <div class="space-y-4 text-sm">
                            <div>
                                <p class="text-xs font-semibold text-base-content/50">Deskripsi</p>
                                <p class="mt-1 whitespace-pre-line">{{ review.project?.description || '-' }}</p>
                            </div>

                            <div v-if="review.project?.user">
                                <p class="text-xs font-semibold text-base-content/50">Pemilik</p>
                                <div class="mt-1 flex items-center gap-2">
                                    <div class="avatar placeholder">
                                        <div
                                            class="flex h-8 w-8 items-center justify-center rounded-full bg-primary/15 text-xs font-semibold text-primary"
                                        >
                                            {{ initials(review.project.user.name) }}
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-sm">{{ review.project.user.name }}</p>
                                        <p class="text-xs text-base-content/50">{{ review.project.user.email }}</p>
                                    </div>
                                </div>
                            </div>

                            <div v-if="review.reviewer">
                                <p class="text-xs font-semibold text-base-content/50">Reviewer</p>
                                <div class="mt-1 flex items-center gap-2">
                                    <div class="avatar placeholder">
                                        <div
                                            class="flex h-8 w-8 items-center justify-center rounded-full bg-secondary/15 text-xs font-semibold text-secondary"
                                        >
                                            {{ initials(review.reviewer.name) }}
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-sm">{{ review.reviewer.name }}</p>
                                        <p class="text-xs text-base-content/50">{{ review.reviewer.email }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <p class="text-xs font-semibold text-base-content/50">Status Project</p>
                                    <p class="mt-1">
                                        <span
                                            class="badge whitespace-nowrap"
                                            :class="PROJECT_STATUS[review.project?.status]?.badge ?? 'badge-neutral'"
                                        >
                                            {{
                                                PROJECT_STATUS[review.project?.status]?.label ?? review.project?.status
                                            }}
                                        </span>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-base-content/50">Mulai Review</p>
                                    <p class="mt-1">{{ formatDate(review.created_at) }}</p>
                                </div>
                            </div>

                            <div v-if="review.notes">
                                <p class="text-xs font-semibold text-base-content/50">Catatan</p>
                                <p class="mt-1 whitespace-pre-line">{{ review.notes }}</p>
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

                        <div v-if="documentsLoading" class="flex justify-center py-12">
                            <span class="loading loading-spinner loading-lg text-primary" />
                        </div>

                        <div v-else-if="documents.length === 0" class="py-8 text-center text-sm text-base-content/50">
                            Belum ada dokumen pada project ini.
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
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-6 border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body">
                    <h3 class="card-title text-base">Timeline Review</h3>

                    <div v-if="timeline.length === 0" class="py-8 text-center text-sm text-base-content/50">
                        Belum ada aktivitas review.
                    </div>

                    <ul v-else class="timeline timeline-vertical timeline-snap-icon max-md:timeline-compact">
                        <li v-for="log in timeline" :key="log.id">
                            <div class="timeline-middle">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.5"
                                    stroke="currentColor"
                                    class="h-4 w-4"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"
                                    />
                                </svg>
                            </div>
                            <div class="timeline-end timeline-box">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="badge whitespace-nowrap" :class="REVIEW_LOG_ACTIONS[log.action]?.badge ?? 'badge-ghost'">
                                        {{ REVIEW_LOG_ACTIONS[log.action]?.label ?? log.action }}
                                    </span>
                                    <span class="text-xs text-base-content/50">{{ formatDate(log.created_at) }}</span>
                                </div>
                                <p v-if="log.notes" class="mt-2 text-sm whitespace-pre-line">{{ log.notes }}</p>
                                <p class="mt-1 text-xs text-base-content/50">oleh {{ log.reviewer?.name ?? '-' }}</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <ReviewDecisionModal
                ref="decisionModalRef"
                v-model="decisionModalOpen"
                :type="decisionType"
                @submitted="submitDecision"
            />
        </div>

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
    </AppLayout>
</template>
