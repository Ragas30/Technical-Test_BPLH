<script setup>
    import { onMounted, reactive, ref, watch } from 'vue';
    import AppLayout from '../../layouts/AppLayout.vue';
    import PaginationBar from '../../components/PaginationBar.vue';
    import { activityLogService } from '../../services/activityLogs';
    import { useAuthStore } from '../../stores/auth';
    import { useToastStore } from '../../stores/toast';
    import { ACTION_LABELS, ACTION_BADGES } from '../../constants/roles';
    import { formatDate } from '../../utils/format';

    const auth = useAuthStore();
    const toast = useToastStore();

    const query = reactive({
        search: '',
        action: '',
        page: 1,
    });

    const activities = ref([]);
    const meta = ref(null);
    const loading = ref(false);

    const ACTION_OPTIONS = Object.entries(ACTION_LABELS).map(([value, label]) => ({ value, label }));

    async function fetchActivities() {
        loading.value = true;

        const params = {
            search: query.search || undefined,
            action: query.action || undefined,
            page: query.page,
            per_page: 20,
        };

        const requester = auth.isAdmin ? activityLogService.list : activityLogService.mine;

        try {
            const { data } = await requester(params);
            activities.value = data.data;
            meta.value = data.meta;
        } catch {
            toast.error('Gagal memuat data aktivitas.');
        } finally {
            loading.value = false;
        }
    }

    function goToPage(page) {
        query.page = page;
        fetchActivities();
    }

    let searchTimer = null;
    watch(
        () => query.search,
        () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                query.page = 1;
                fetchActivities();
            }, 300);
        }
    );

    watch(
        () => query.action,
        () => {
            query.page = 1;
            fetchActivities();
        }
    );

    onMounted(fetchActivities);
</script>

<template>
    <AppLayout>
        <div>
            <h2 class="text-2xl font-semibold">Aktivitas</h2>
            <p class="text-sm text-base-content/60">
                {{ auth.isAdmin ? 'Timeline seluruh aktivitas dalam sistem.' : 'Timeline aktivitas Anda.' }}
            </p>
        </div>

        <div class="mt-6 flex flex-wrap items-end gap-3">
            <label class="form-control w-full max-w-xs">
                <div class="label">
                    <span class="label-text">Cari</span>
                </div>
                <input
                    v-model="query.search"
                    type="search"
                    placeholder="Deskripsi atau nama pengguna..."
                    class="input input-bordered input-sm w-full"
                />
            </label>

            <label class="form-control w-52">
                <div class="label">
                    <span class="label-text">Jenis Aktivitas</span>
                </div>
                <select v-model="query.action" class="select select-bordered select-sm w-full">
                    <option value="">Semua</option>
                    <option v-for="option in ACTION_OPTIONS" :key="option.value" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>
            </label>
        </div>

        <div class="mt-4 rounded-box border border-base-300 bg-base-100 shadow-sm">
            <div v-if="loading" class="flex items-center justify-center gap-3 py-20">
                <span class="loading loading-spinner loading-lg text-primary" />
            </div>

            <div v-else-if="activities.length === 0" class="py-16 text-center">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    stroke="currentColor"
                    class="mx-auto h-12 w-12 text-base-content/30"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                    />
                </svg>
                <p class="mt-3 font-medium">Belum ada aktivitas</p>
                <p class="text-sm text-base-content/50">Tidak ada data yang cocok dengan pencarian Anda.</p>
            </div>

            <ul v-else class="divide-y divide-base-200">
                <li
                    v-for="activity in activities"
                    :key="activity.id"
                    class="flex items-start gap-4 px-5 py-4"
                >
                    <div class="avatar placeholder">
                        <div
                            class="flex w-10 items-center justify-center rounded-full bg-base-200 text-xs font-semibold text-base-content"
                        >
                            {{ activity.user?.name?.charAt(0) ?? '?' }}
                        </div>
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-medium">{{ activity.user?.name ?? 'Sistem' }}</span>
                            <span class="badge badge-sm" :class="ACTION_BADGES[activity.action] ?? 'badge-neutral'">
                                {{ ACTION_LABELS[activity.action] ?? activity.action }}
                            </span>
                            <span class="text-xs text-base-content/50">{{ formatDate(activity.created_at) }}</span>
                        </div>
                        <p class="mt-1 text-sm">{{ activity.description }}</p>
                        <p v-if="activity.project" class="mt-0.5 text-xs text-base-content/50">
                            {{ activity.project.project_number }} · {{ activity.project.title }}
                        </p>
                    </div>
                </li>
            </ul>
        </div>

        <PaginationBar v-if="meta" :meta="meta" item-label="aktivitas" @page-change="goToPage" />
    </AppLayout>
</template>
