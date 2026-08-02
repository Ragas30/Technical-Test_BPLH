<script setup>
    import { computed, onMounted, reactive, ref, watch } from 'vue';
    import AppLayout from '../../layouts/AppLayout.vue';
    import ConfirmModal from '../../components/ConfirmModal.vue';
    import PaginationBar from '../../components/PaginationBar.vue';
    import ProjectFormModal from '../../components/ProjectFormModal.vue';
    import { projectService } from '../../services/projects';
    import { reviewService } from '../../services/reviews';
    import { exportService } from '../../services/exports';
    import { useAuthStore } from '../../stores/auth';
    import { useToastStore } from '../../stores/toast';
    import { PROJECT_STATUS } from '../../constants/roles';
    import { formatDate } from '../../utils/format';

    const auth = useAuthStore();
    const toast = useToastStore();

    const query = reactive({
        search: '',
        status: '',
        sort_by: 'created_at',
        sort_dir: 'desc',
        page: 1,
    });

    const projects = ref([]);
    const meta = ref(null);
    const loading = ref(false);
    const submittingId = ref(null);
    const startingId = ref(null);
    const deletingId = ref(null);
    const formModalOpen = ref(false);
    const editingProjectId = ref(null);
    const confirmModalOpen = ref(false);
    const confirmModalRef = ref(null);
    const confirmAction = ref(null);
    const exporting = ref(false);

    const isApplicant = computed(() => auth.isApplicant);

    function canEdit(project) {
        return auth.hasPermission('project.update') && project.status === 'draft';
    }

    function canSubmit(project) {
        return auth.hasPermission('project.submit') && (project.status === 'draft' || project.status === 'revision');
    }

    function canDelete(project) {
        return auth.hasPermission('project.delete') && project.status === 'draft';
    }

    function canStartReview(project) {
        return auth.hasPermission('review.start') && project.status === 'submitted';
    }

    async function fetchProjects() {
        loading.value = true;

        const params = {
            search: query.search || undefined,
            status: query.status || undefined,
            sort_by: query.sort_by,
            sort_dir: query.sort_dir,
            page: query.page,
            per_page: 10,
        };

        try {
            const { data } = isApplicant.value ? await projectService.mine(params) : await projectService.list(params);
            projects.value = data.data;
            meta.value = data.meta;
        } catch {
            toast.error('Gagal memuat data project.');
        } finally {
            loading.value = false;
        }
    }

    function goToPage(page) {
        query.page = page;
        fetchProjects();
    }

    function sortBy(field) {
        if (query.sort_by === field) {
            query.sort_dir = query.sort_dir === 'asc' ? 'desc' : 'asc';
        } else {
            query.sort_by = field;
            query.sort_dir = 'asc';
        }
        query.page = 1;
    }

    function sortIcon(field) {
        if (query.sort_by !== field) return '';
        return query.sort_dir === 'asc' ? '↑' : '↓';
    }

    function openCreate() {
        editingProjectId.value = null;
        formModalOpen.value = true;
    }

    function openEdit(projectId) {
        editingProjectId.value = projectId;
        formModalOpen.value = true;
    }

    function onSaved() {
        fetchProjects();
    }

    function openSubmit(project) {
        const isRevision = project.status === 'revision';

        confirmAction.value = {
            title: isRevision ? 'Ajukan Ulang Project' : 'Ajukan Project',
            message: isRevision
                ? `Ajukan ulang project "${project.title}" untuk direview kembali?`
                : `Ajukan project "${project.title}" untuk review?`,
            confirmLabel: isRevision ? 'Ajukan Ulang' : 'Ajukan',
            confirmClass: 'btn-primary',
            run: () => submitProject(project),
        };
        confirmModalOpen.value = true;
    }

    function openStartReview(project) {
        confirmAction.value = {
            title: 'Mulai Review',
            message: `Mulai review untuk project "${project.title}"?`,
            confirmLabel: 'Mulai Review',
            confirmClass: 'btn-primary',
            run: () => startReview(project),
        };
        confirmModalOpen.value = true;
    }

    function openRemove(project) {
        confirmAction.value = {
            title: 'Hapus Project',
            message: `Hapus project "${project.title}"? Tindakan ini tidak dapat dibatalkan.`,
            confirmLabel: 'Hapus',
            confirmClass: 'btn-error',
            run: () => removeProject(project),
        };
        confirmModalOpen.value = true;
    }

    async function handleConfirm() {
        const action = confirmAction.value;
        if (!action) return;

        confirmModalRef.value?.setSubmitting(true);

        try {
            await action.run();
            confirmModalOpen.value = false;
        } catch (error) {
            confirmModalRef.value?.setSubmitting(false);
            toast.error(
                error?.response?.data?.errors?.status?.[0] ?? error?.response?.data?.message ?? 'Gagal melakukan aksi.'
            );
        }
    }

    async function submitProject(project) {
        submittingId.value = project.id;

        try {
            await projectService.submit(project.id);
            toast.success('Project berhasil diajukan.');
            await fetchProjects();
        } finally {
            submittingId.value = null;
        }
    }

    async function startReview(project) {
        startingId.value = project.id;

        try {
            await reviewService.start(project.id);
            toast.success('Review dimulai.');
            await fetchProjects();
        } finally {
            startingId.value = null;
        }
    }

    async function removeProject(project) {
        deletingId.value = project.id;

        try {
            await projectService.destroy(project.id);
            toast.success('Project berhasil dihapus.');
            if (projects.value.length === 1 && query.page > 1) {
                query.page -= 1;
            }
            await fetchProjects();
        } finally {
            deletingId.value = null;
        }
    }

    async function exportProjects(kind) {
        if (exporting.value) return;

        exporting.value = true;

        try {
            await (kind === 'excel' ? exportService.projectsExcel : exportService.projectsPdf)({
                search: query.search || undefined,
                status: query.status || undefined,
            });
            toast.success('Export berhasil diunduh.');
        } catch (error) {
            toast.error(error?.response?.data?.message ?? 'Gagal melakukan export.');
        } finally {
            exporting.value = false;
        }
    }

    let searchTimer = null;
    watch(
        () => query.search,
        () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                query.page = 1;
                fetchProjects();
            }, 300);
        }
    );

    watch(
        () => [query.status, query.sort_by, query.sort_dir],
        () => {
            query.page = 1;
            fetchProjects();
        }
    );

    onMounted(fetchProjects);
</script>

<template>
    <AppLayout>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-2xl font-semibold">{{ isApplicant ? 'Project Saya' : 'Manajemen Project' }}</h2>
                <p v-if="isApplicant" class="text-sm text-base-content/60">Kelola pengajuan project Anda.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button
                    v-if="auth.hasPermission('export.excel')"
                    type="button"
                    class="btn btn-outline btn-sm"
                    :disabled="exporting"
                    @click="exportProjects('excel')"
                >
                    Export Excel
                </button>
                <button
                    v-if="auth.hasPermission('export.pdf')"
                    type="button"
                    class="btn btn-outline btn-sm"
                    :disabled="exporting"
                    @click="exportProjects('pdf')"
                >
                    Export PDF
                </button>
                <button
                    v-if="auth.hasPermission('project.create')"
                    type="button"
                    class="btn btn-primary"
                    @click="openCreate"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="2"
                        stroke="currentColor"
                        class="h-5 w-5"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Buat Project
                </button>
            </div>
        </div>

        <div class="mt-6 flex flex-wrap items-end gap-3">
            <label class="form-control w-full max-w-xs">
                <div class="label">
                    <span class="label-text">Cari</span>
                </div>
                <input
                    v-model="query.search"
                    type="search"
                    placeholder="Judul atau nomor project..."
                    class="input input-bordered input-sm w-full"
                />
            </label>

            <label class="form-control w-44">
                <div class="label">
                    <span class="label-text">Status</span>
                </div>
                <select v-model="query.status" class="select select-bordered select-sm w-full">
                    <option value="">Semua</option>
                    <option v-for="(value, key) in PROJECT_STATUS" :key="key" :value="key">{{ value.label }}</option>
                </select>
            </label>
        </div>

        <div class="mt-4 overflow-x-auto rounded-box border border-base-300 bg-base-100 shadow-sm">
            <table class="table">
                <thead>
                    <tr class="text-sm">
                        <th>Project</th>
                        <th v-if="!isApplicant">Pemilik</th>
                        <th>Status</th>
                        <th class="cursor-pointer select-none" @click="sortBy('created_at')">
                            Dibuat {{ sortIcon('created_at') }}
                        </th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="loading">
                        <td :colspan="isApplicant ? 4 : 5" class="py-16 text-center">
                            <span class="loading loading-spinner loading-lg text-primary" />
                        </td>
                    </tr>
                    <tr v-else-if="projects.length === 0">
                        <td :colspan="isApplicant ? 4 : 5" class="py-12 text-center text-sm text-base-content/50">
                            Tidak ada data project.
                        </td>
                    </tr>
                    <tr v-for="project in projects" :key="project.id">
                        <td>
                            <div class="font-medium">{{ project.title }}</div>
                            <div class="text-xs font-mono text-base-content/50">{{ project.project_number }}</div>
                        </td>
                        <td v-if="!isApplicant">
                            {{ project.user?.name ?? '-' }}
                        </td>
                        <td>
                            <span class="badge" :class="PROJECT_STATUS[project.status]?.badge ?? 'badge-neutral'">
                                {{ PROJECT_STATUS[project.status]?.label ?? project.status }}
                            </span>
                        </td>
                        <td class="text-sm">{{ formatDate(project.created_at) }}</td>
                        <td>
                            <div class="flex justify-end gap-2">
                                <RouterLink :to="`/projects/${project.id}`" class="btn btn-ghost btn-xs">
                                    Detail
                                </RouterLink>
                                <button
                                    v-if="canStartReview(project)"
                                    type="button"
                                    class="btn btn-ghost btn-xs text-info"
                                    :disabled="startingId === project.id"
                                    @click="openStartReview(project)"
                                >
                                    Mulai Review
                                </button>
                                <button
                                    v-if="canEdit(project)"
                                    type="button"
                                    class="btn btn-ghost btn-xs"
                                    @click="openEdit(project.id)"
                                >
                                    Edit
                                </button>
                                <button
                                    v-if="canSubmit(project)"
                                    type="button"
                                    class="btn btn-ghost btn-xs text-info"
                                    :disabled="submittingId === project.id"
                                    @click="openSubmit(project)"
                                >
                                    {{ project.status === 'revision' ? 'Ajukan Ulang' : 'Ajukan' }}
                                </button>
                                <button
                                    v-if="canDelete(project)"
                                    type="button"
                                    class="btn btn-ghost btn-xs text-error"
                                    :disabled="deletingId === project.id"
                                    @click="openRemove(project)"
                                >
                                    Hapus
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <PaginationBar v-if="meta" :meta="meta" item-label="project" @page-change="goToPage" />

        <ProjectFormModal v-model="formModalOpen" :project-id="editingProjectId" @saved="onSaved" />

        <ConfirmModal
            ref="confirmModalRef"
            v-model="confirmModalOpen"
            :title="confirmAction?.title ?? 'Konfirmasi'"
            :message="confirmAction?.message ?? ''"
            :confirm-label="confirmAction?.confirmLabel ?? 'Konfirmasi'"
            :confirm-class="confirmAction?.confirmClass ?? 'btn-primary'"
            @confirm="handleConfirm"
        />
    </AppLayout>
</template>
