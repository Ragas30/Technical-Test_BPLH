<script setup>
    import { onMounted, reactive, ref, watch } from 'vue';
    import AppLayout from '../../layouts/AppLayout.vue';
    import PaginationBar from '../../components/PaginationBar.vue';
    import { reviewService } from '../../services/reviews';
    import { exportService } from '../../services/exports';
    import { useAuthStore } from '../../stores/auth';
    import { useToastStore } from '../../stores/toast';
    import { REVIEW_STATUS } from '../../constants/roles';
    import { formatDate } from '../../utils/format';

    const auth = useAuthStore();
    const toast = useToastStore();

    const query = reactive({
        search: '',
        status: '',
        page: 1,
    });

    const reviews = ref([]);
    const meta = ref(null);
    const loading = ref(false);
    const exporting = ref(false);

    async function fetchReviews() {
        loading.value = true;

        const params = {
            search: query.search || undefined,
            status: query.status || undefined,
            page: query.page,
            per_page: 10,
        };

        try {
            const { data } = await reviewService.list(params);
            reviews.value = data.data;
            meta.value = data.meta;
        } catch {
            toast.error('Gagal memuat data review.');
        } finally {
            loading.value = false;
        }
    }

    function goToPage(page) {
        query.page = page;
        fetchReviews();
    }

    let searchTimer = null;
    watch(
        () => query.search,
        () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                query.page = 1;
                fetchReviews();
            }, 300);
        }
    );

    watch(
        () => query.status,
        () => {
            query.page = 1;
            fetchReviews();
        }
    );

    onMounted(fetchReviews);

    async function exportReviews(kind) {
        if (exporting.value) return;

        exporting.value = true;

        try {
            await (kind === 'excel' ? exportService.reviewsExcel : exportService.reviewsPdf)({
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
</script>

<template>
    <AppLayout>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-2xl font-semibold">Manajemen Review</h2>
                <p class="text-sm text-base-content/60">Kelola proses review project.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button
                    v-if="auth.hasPermission('export.excel')"
                    type="button"
                    class="btn btn-outline btn-sm"
                    :disabled="exporting"
                    @click="exportReviews('excel')"
                >
                    Export Excel
                </button>
                <button
                    v-if="auth.hasPermission('export.pdf')"
                    type="button"
                    class="btn btn-outline btn-sm"
                    :disabled="exporting"
                    @click="exportReviews('pdf')"
                >
                    Export PDF
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
                    <option v-for="(value, key) in REVIEW_STATUS" :key="key" :value="key">{{ value.label }}</option>
                </select>
            </label>
        </div>

        <div class="mt-4 overflow-x-auto rounded-box border border-base-300 bg-base-100 shadow-sm">
            <table class="table">
                <thead>
                    <tr class="text-sm">
                        <th>Project</th>
                        <th v-if="auth.isAdmin">Reviewer</th>
                        <th>Status</th>
                        <th>Mulai</th>
                        <th>Diputuskan</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="loading">
                        <td :colspan="auth.isAdmin ? 6 : 5" class="py-16 text-center">
                            <span class="loading loading-spinner loading-lg text-primary" />
                        </td>
                    </tr>
                    <tr v-else-if="reviews.length === 0">
                        <td :colspan="auth.isAdmin ? 6 : 5" class="py-12 text-center text-sm text-base-content/50">
                            Tidak ada data review.
                        </td>
                    </tr>
                    <tr v-for="review in reviews" :key="review.id">
                        <td>
                            <div class="font-medium">{{ review.project?.title }}</div>
                            <div class="text-xs font-mono text-base-content/50">
                                {{ review.project?.project_number }}
                            </div>
                        </td>
                        <td v-if="auth.isAdmin">
                            {{ review.reviewer?.name ?? '-' }}
                        </td>
                        <td>
                            <span class="badge whitespace-nowrap" :class="REVIEW_STATUS[review.status]?.badge ?? 'badge-neutral'">
                                {{ REVIEW_STATUS[review.status]?.label ?? review.status }}
                            </span>
                        </td>
                        <td class="text-sm">{{ formatDate(review.created_at) }}</td>
                        <td class="text-sm">{{ formatDate(review.reviewed_at) }}</td>
                        <td>
                            <div class="flex justify-end gap-2">
                                <RouterLink :to="`/reviews/${review.id}`" class="btn btn-ghost btn-xs">
                                    Detail
                                </RouterLink>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <PaginationBar v-if="meta" :meta="meta" item-label="review" @page-change="goToPage" />
    </AppLayout>
</template>
