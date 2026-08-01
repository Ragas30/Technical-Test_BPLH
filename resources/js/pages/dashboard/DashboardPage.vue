<script setup>
    import { onMounted, ref } from 'vue';
    import AppLayout from '../../layouts/AppLayout.vue';
    import StatCard from '../../components/StatCard.vue';
    import BarChart from '../../components/BarChart.vue';
    import { dashboardService } from '../../services/dashboard';
    import { useAuthStore } from '../../stores/auth';
    import { PROJECT_STATUS, REVIEW_STATUS, ACTION_LABELS } from '../../constants/roles';
    import { formatDate } from '../../utils/format';

    const auth = useAuthStore();

    const loading = ref(true);
    const dashboard = ref(null);

    const PROJECT_CARDS = [
        { key: 'draft', label: 'Draft' },
        { key: 'submitted', label: 'Diajukan' },
        { key: 'under_review', label: 'Sedang Ditinjau' },
        { key: 'revision', label: 'Revisi' },
        { key: 'rejected', label: 'Ditolak' },
        { key: 'approved', label: 'Disetujui' },
    ];

    const REVIEW_CARDS = [
        { key: 'pending', label: 'Menunggu Review' },
        { key: 'under_review', label: 'Sedang Ditinjau' },
        { key: 'revision', label: 'Revisi' },
        { key: 'approved', label: 'Disetujui' },
        { key: 'rejected', label: 'Ditolak' },
    ];

    function badgeClass(status) {
        return PROJECT_STATUS[status]?.badge ?? 'badge-neutral';
    }

    onMounted(async () => {
        try {
            const { data } = await dashboardService.get();
            dashboard.value = data.data;
        } finally {
            loading.value = false;
        }
    });
</script>

<template>
    <AppLayout>
        <div v-if="loading" class="flex justify-center py-24">
            <span class="loading loading-spinner loading-lg text-primary" />
        </div>

        <div v-else-if="dashboard" class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-semibold">Selamat datang, {{ auth.user?.name }}</h2>
                    <p class="text-sm text-base-content/60">Ringkasan aktivitas Anda di DocFlow.</p>
                </div>
            </div>

            <template v-if="dashboard.role === 'admin'">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard label="Total Pengguna" :value="dashboard.statistics.total_users" tone="text-info" />
                    <StatCard label="Total Project" :value="dashboard.statistics.total_projects" tone="text-primary" />
                    <StatCard label="Disetujui" :value="dashboard.statistics.approved" tone="text-success" />
                    <StatCard label="Ditolak" :value="dashboard.statistics.rejected" tone="text-error" />
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div class="card border border-base-300 bg-base-100 shadow-sm">
                        <div class="card-body">
                            <h3 class="card-title text-base">Project per Bulan</h3>
                            <BarChart :data="dashboard.monthly_stats" />
                        </div>
                    </div>

                    <div class="card border border-base-300 bg-base-100 shadow-sm">
                        <div class="card-body">
                            <h3 class="card-title text-base">Status Project</h3>
                            <div class="grid grid-cols-2 gap-2">
                                <div
                                    v-for="card in PROJECT_CARDS"
                                    :key="card.key"
                                    class="flex items-center justify-between rounded-box border border-base-300 px-4 py-3"
                                >
                                    <span class="text-sm">{{ card.label }}</span>
                                    <span class="badge" :class="PROJECT_STATUS[card.key].badge">{{
                                        dashboard.statistics[card.key]
                                    }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body">
                        <h3 class="card-title text-base">Aktivitas Terbaru</h3>
                        <div
                            v-if="dashboard.recent_activities.length === 0"
                            class="py-6 text-center text-sm text-base-content/50"
                        >
                            Belum ada aktivitas.
                        </div>
                        <ul v-else class="space-y-3">
                            <li
                                v-for="activity in dashboard.recent_activities"
                                :key="activity.id"
                                class="flex items-start gap-3 border-b border-base-200 pb-3 last:border-0 last:pb-0"
                            >
                                <span class="badge badge-outline badge-info shrink-0">{{
                                    ACTION_LABELS[activity.action] ?? activity.action
                                }}</span>
                                <div class="min-w-0">
                                    <p class="text-sm">{{ activity.description }}</p>
                                    <p class="text-xs text-base-content/50">
                                        {{ activity.user }} · {{ formatDate(activity.created_at) }}
                                    </p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </template>

            <template v-else-if="dashboard.role === 'reviewer'">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <StatCard
                        v-for="card in REVIEW_CARDS"
                        :key="card.key"
                        :label="card.label"
                        :value="dashboard.statistics[card.key]"
                    />
                </div>

                <div class="card border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body">
                        <h3 class="card-title text-base">Review per Bulan</h3>
                        <BarChart :data="dashboard.monthly_stats" />
                    </div>
                </div>

                <div class="card border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body">
                        <h3 class="card-title text-base">Review Terbaru</h3>
                        <div
                            v-if="dashboard.recent_reviews.length === 0"
                            class="py-6 text-center text-sm text-base-content/50"
                        >
                            Belum ada review.
                        </div>
                        <div v-else class="overflow-x-auto">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Project</th>
                                        <th>Status</th>
                                        <th>Catatan</th>
                                        <th>Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="review in dashboard.recent_reviews" :key="review.id">
                                        <td>
                                            <span class="font-medium">{{ review.project_title ?? '-' }}</span>
                                            <span
                                                v-if="review.project_number"
                                                class="block text-xs text-base-content/50"
                                                >{{ review.project_number }}</span
                                            >
                                        </td>
                                        <td>
                                            <span class="badge" :class="badgeClass(review.status)">
                                                {{ REVIEW_STATUS[review.status]?.label ?? review.status }}
                                            </span>
                                        </td>
                                        <td class="max-w-xs truncate">{{ review.notes ?? '-' }}</td>
                                        <td class="text-sm">
                                            {{ formatDate(review.reviewed_at ?? review.created_at) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </template>

            <template v-else>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard label="Total Project" :value="dashboard.statistics.total_projects" tone="text-primary" />
                    <StatCard label="Disetujui" :value="dashboard.statistics.approved" tone="text-success" />
                    <StatCard
                        label="Menunggu"
                        :value="dashboard.statistics.submitted + dashboard.statistics.under_review"
                        tone="text-warning"
                    />
                    <StatCard label="Ditolak" :value="dashboard.statistics.rejected" tone="text-error" />
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div class="card border border-base-300 bg-base-100 shadow-sm">
                        <div class="card-body">
                            <h3 class="card-title text-base">Project per Bulan</h3>
                            <BarChart :data="dashboard.monthly_stats" />
                        </div>
                    </div>

                    <div class="card border border-base-300 bg-base-100 shadow-sm">
                        <div class="card-body">
                            <h3 class="card-title text-base">Status Project Anda</h3>
                            <div class="grid grid-cols-2 gap-2">
                                <div
                                    v-for="card in PROJECT_CARDS"
                                    :key="card.key"
                                    class="flex items-center justify-between rounded-box border border-base-300 px-4 py-3"
                                >
                                    <span class="text-sm">{{ card.label }}</span>
                                    <span class="badge" :class="PROJECT_STATUS[card.key].badge">{{
                                        dashboard.statistics[card.key]
                                    }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body">
                        <h3 class="card-title text-base">Aktivitas Terbaru</h3>
                        <div
                            v-if="dashboard.recent_activities.length === 0"
                            class="py-6 text-center text-sm text-base-content/50"
                        >
                            Belum ada aktivitas.
                        </div>
                        <ul v-else class="space-y-3">
                            <li
                                v-for="activity in dashboard.recent_activities"
                                :key="activity.id"
                                class="flex items-start gap-3 border-b border-base-200 pb-3 last:border-0 last:pb-0"
                            >
                                <span class="badge badge-outline badge-info shrink-0">{{
                                    ACTION_LABELS[activity.action] ?? activity.action
                                }}</span>
                                <div class="min-w-0">
                                    <p class="text-sm">{{ activity.description }}</p>
                                    <p class="text-xs text-base-content/50">
                                        {{ activity.user }} · {{ formatDate(activity.created_at) }}
                                    </p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </template>
        </div>
    </AppLayout>
</template>
