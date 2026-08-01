<script setup>
    import { onMounted, reactive, ref, watch } from 'vue';
    import AppLayout from '../../layouts/AppLayout.vue';
    import { reviewService } from '../../services/reviews';
    import { useAuthStore } from '../../stores/auth';
    import { useToastStore } from '../../stores/toast';
    import { useInfiniteScroll } from '../../composables/useInfiniteScroll';
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

    async function fetchReviews(append = false) {
        if (!append) {
            loading.value = true;
        }

        const params = {
            search: query.search || undefined,
            status: query.status || undefined,
            page: query.page,
            per_page: 10,
        };

        try {
            const { data } = await reviewService.list(params);
            reviews.value = append ? [...reviews.value, ...data.data] : data.data;
            meta.value = data.meta;
        } catch {
            toast.error('Gagal memuat data review.');
            if (append && query.page > 1) {
                query.page -= 1;
            }
        } finally {
            loading.value = false;
        }
    }

    async function appendReviews() {
        if (!meta.value?.next_page_url) return;

        query.page += 1;
        await fetchReviews(true);
    }

    const { loadingMore, sentinel, loadMore } = useInfiniteScroll(appendReviews);

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
</script>

<template>
    <AppLayout>
        <div>
            <h2 class="text-2xl font-semibold">Manajemen Review</h2>
            <p class="text-sm text-base-content/60">Kelola proses review project.</p>
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
                            <span class="badge" :class="REVIEW_STATUS[review.status]?.badge ?? 'badge-neutral'">
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

        <div v-if="meta" ref="sentinel" class="mt-4 flex flex-col items-center gap-3">
            <p class="text-sm text-base-content/60">
                Menampilkan {{ meta.from ?? 0 }}-{{ meta.to ?? 0 }} dari {{ meta.total }} review
            </p>
            <button
                v-if="meta.next_page_url"
                type="button"
                class="btn btn-sm btn-outline"
                :disabled="loadingMore"
                @click="loadMore"
            >
                <span v-if="loadingMore" class="loading loading-spinner loading-sm" />
                {{ loadingMore ? 'Memuat...' : 'Muat Lebih Banyak' }}
            </button>
            <p v-else-if="meta.total > 0" class="text-sm text-base-content/50">Semua data sudah dimuat.</p>
        </div>
    </AppLayout>
</template>
