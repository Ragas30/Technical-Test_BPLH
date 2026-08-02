<script setup>
    import { onMounted, ref } from 'vue';
    import AppLayout from '../../layouts/AppLayout.vue';
    import StatCard from '../../components/StatCard.vue';
    import BarChart from '../../components/BarChart.vue';
    import { dashboardService } from '../../services/dashboard';
    import { useAuthStore } from '../../stores/auth';
    import { PROJECT_STATUS, REVIEW_STATUS, ACTION_LABELS, ACTION_BADGES } from '../../constants/roles';
    import { formatDate } from '../../utils/format';

    const auth = useAuthStore();

    const loading = ref(true);
    const dashboard = ref(null);

    const ICONS = {
        users: 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z',
        folder: 'M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z',
        check: 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        xMark: 'M9.75 9.75l4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        clock: 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        document:
            'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z',
        refresh:
            'M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99',
        eye: 'M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z',
    };

    const DOT_COLORS = {
        'badge-neutral': 'bg-neutral/40',
        'badge-info': 'bg-info',
        'badge-success': 'bg-success',
        'badge-error': 'bg-error',
        'badge-warning': 'bg-warning',
        'badge-primary': 'bg-primary',
        'badge-secondary': 'bg-secondary',
        'badge-ghost': 'bg-base-300',
    };

    const PROJECT_CARDS = Object.entries(PROJECT_STATUS).map(([key, value]) => ({ key, label: value.label }));

    const STATUS_DOT = Object.fromEntries(
        Object.entries(PROJECT_STATUS).map(([key, value]) => [key, DOT_COLORS[value.badge] ?? 'bg-base-300']),
    );

    const REVIEW_CARDS = [
        { key: 'pending', label: 'Menunggu Review', icon: ICONS.clock, tone: 'bg-neutral/10 text-neutral' },
        { key: 'under_review', label: 'Sedang Ditinjau', icon: ICONS.eye, tone: 'bg-warning/10 text-warning' },
        { key: 'revision', label: 'Revisi', icon: ICONS.refresh, tone: 'bg-secondary/10 text-secondary' },
        { key: 'approved', label: 'Disetujui', icon: ICONS.check, tone: 'bg-success/10 text-success' },
        { key: 'rejected', label: 'Ditolak', icon: ICONS.xMark, tone: 'bg-error/10 text-error' },
    ];

    function badgeClass(status) {
        return PROJECT_STATUS[status]?.badge ?? 'badge-neutral';
    }

    function activityDot(action) {
        return DOT_COLORS[ACTION_BADGES[action]] ?? 'bg-base-300';
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
                    <p class="text-sm font-semibold text-primary">Dashboard</p>
                    <h2 class="mt-0.5 text-2xl font-bold tracking-tight">Selamat datang, {{ auth.user?.name }}</h2>
                    <p class="text-sm text-base-content/60">Ringkasan aktivitas Anda di DocFlow.</p>
                </div>
            </div>

            <template v-if="dashboard.role === 'admin'">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        label="Total Pengguna"
                        :value="dashboard.statistics.total_users"
                        tone="bg-info/10 text-info"
                    >
                        <template #figure>
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.6"
                                stroke="currentColor"
                                class="h-5 w-5"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" :d="ICONS.users" />
                            </svg>
                        </template>
                    </StatCard>
                    <StatCard
                        label="Total Project"
                        :value="dashboard.statistics.total_projects"
                        tone="bg-primary/10 text-primary"
                    >
                        <template #figure>
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.6"
                                stroke="currentColor"
                                class="h-5 w-5"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" :d="ICONS.folder" />
                            </svg>
                        </template>
                    </StatCard>
                    <StatCard
                        label="Disetujui"
                        :value="dashboard.statistics.approved"
                        tone="bg-success/10 text-success"
                    >
                        <template #figure>
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.6"
                                stroke="currentColor"
                                class="h-5 w-5"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" :d="ICONS.check" />
                            </svg>
                        </template>
                    </StatCard>
                    <StatCard label="Ditolak" :value="dashboard.statistics.rejected" tone="bg-error/10 text-error">
                        <template #figure>
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.6"
                                stroke="currentColor"
                                class="h-5 w-5"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" :d="ICONS.xMark" />
                            </svg>
                        </template>
                    </StatCard>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div class="rounded-2xl border border-base-300 bg-base-100 p-5 shadow-sm">
                        <h3 class="mb-4 text-base font-semibold">Project per Bulan</h3>
                        <BarChart :data="dashboard.monthly_stats" />
                    </div>

                    <div class="rounded-2xl border border-base-300 bg-base-100 p-5 shadow-sm">
                        <h3 class="mb-4 text-base font-semibold">Status Project</h3>
                        <div class="grid grid-cols-2 gap-2.5">
                            <div
                                v-for="card in PROJECT_CARDS"
                                :key="card.key"
                                class="flex items-center justify-between gap-2 rounded-xl bg-base-200/60 px-4 py-3"
                            >
                                <span class="flex min-w-0 items-center gap-2 text-sm">
                                    <span class="h-2 w-2 shrink-0 rounded-full" :class="STATUS_DOT[card.key]" />
                                    <span class="truncate">{{ card.label }}</span>
                                </span>
                                <span class="text-sm font-bold">{{ dashboard.statistics[card.key] }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-base-300 bg-base-100 p-5 shadow-sm">
                    <h3 class="mb-4 text-base font-semibold">Aktivitas Terbaru</h3>
                    <div
                        v-if="dashboard.recent_activities.length === 0"
                        class="py-6 text-center text-sm text-base-content/50"
                    >
                        Belum ada aktivitas.
                    </div>
                    <ul v-else>
                        <li
                            v-for="(activity, index) in dashboard.recent_activities"
                            :key="activity.id"
                            class="flex gap-3"
                        >
                            <div class="flex flex-col items-center">
                                <span
                                    class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full"
                                    :class="activityDot(activity.action)"
                                />
                                <span
                                    v-if="index !== dashboard.recent_activities.length - 1"
                                    class="w-px flex-1 bg-base-300"
                                />
                            </div>
                            <div class="min-w-0 pb-4">
                                <p class="text-sm">{{ activity.description }}</p>
                                <p class="mt-0.5 text-xs text-base-content/50">
                                    {{ ACTION_LABELS[activity.action] ?? activity.action }} · {{ activity.user }} ·
                                    {{ formatDate(activity.created_at) }}
                                </p>
                            </div>
                        </li>
                    </ul>
                </div>
            </template>

            <template v-else-if="dashboard.role === 'reviewer'">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <StatCard
                        v-for="card in REVIEW_CARDS"
                        :key="card.key"
                        :label="card.label"
                        :value="dashboard.statistics[card.key]"
                        :tone="card.tone"
                    >
                        <template #figure>
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.6"
                                stroke="currentColor"
                                class="h-5 w-5"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" :d="card.icon" />
                            </svg>
                        </template>
                    </StatCard>
                </div>

                <div class="rounded-2xl border border-base-300 bg-base-100 p-5 shadow-sm">
                    <h3 class="mb-4 text-base font-semibold">Review per Bulan</h3>
                    <BarChart :data="dashboard.monthly_stats" />
                </div>

                <div class="rounded-2xl border border-base-300 bg-base-100 p-5 shadow-sm">
                    <h3 class="mb-4 text-base font-semibold">Review Terbaru</h3>
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
                                        <span v-if="review.project_number" class="block text-xs text-base-content/50">
                                            {{ review.project_number }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge whitespace-nowrap" :class="badgeClass(review.status)">
                                            {{ REVIEW_STATUS[review.status]?.label ?? review.status }}
                                        </span>
                                    </td>
                                    <td class="max-w-xs truncate">{{ review.notes ?? '-' }}</td>
                                    <td class="text-sm">{{ formatDate(review.reviewed_at ?? review.created_at) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>

            <template v-else>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        label="Total Project"
                        :value="dashboard.statistics.total_projects"
                        tone="bg-primary/10 text-primary"
                    >
                        <template #figure>
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.6"
                                stroke="currentColor"
                                class="h-5 w-5"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" :d="ICONS.folder" />
                            </svg>
                        </template>
                    </StatCard>
                    <StatCard
                        label="Disetujui"
                        :value="dashboard.statistics.approved"
                        tone="bg-success/10 text-success"
                    >
                        <template #figure>
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.6"
                                stroke="currentColor"
                                class="h-5 w-5"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" :d="ICONS.check" />
                            </svg>
                        </template>
                    </StatCard>
                    <StatCard
                        label="Menunggu"
                        :value="dashboard.statistics.submitted + dashboard.statistics.under_review"
                        tone="bg-warning/10 text-warning"
                    >
                        <template #figure>
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.6"
                                stroke="currentColor"
                                class="h-5 w-5"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" :d="ICONS.clock" />
                            </svg>
                        </template>
                    </StatCard>
                    <StatCard label="Ditolak" :value="dashboard.statistics.rejected" tone="bg-error/10 text-error">
                        <template #figure>
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.6"
                                stroke="currentColor"
                                class="h-5 w-5"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" :d="ICONS.xMark" />
                            </svg>
                        </template>
                    </StatCard>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div class="rounded-2xl border border-base-300 bg-base-100 p-5 shadow-sm">
                        <h3 class="mb-4 text-base font-semibold">Project per Bulan</h3>
                        <BarChart :data="dashboard.monthly_stats" />
                    </div>

                    <div class="rounded-2xl border border-base-300 bg-base-100 p-5 shadow-sm">
                        <h3 class="mb-4 text-base font-semibold">Status Project Anda</h3>
                        <div class="grid grid-cols-2 gap-2.5">
                            <div
                                v-for="card in PROJECT_CARDS"
                                :key="card.key"
                                class="flex items-center justify-between gap-2 rounded-xl bg-base-200/60 px-4 py-3"
                            >
                                <span class="flex min-w-0 items-center gap-2 text-sm">
                                    <span class="h-2 w-2 shrink-0 rounded-full" :class="STATUS_DOT[card.key]" />
                                    <span class="truncate">{{ card.label }}</span>
                                </span>
                                <span class="text-sm font-bold">{{ dashboard.statistics[card.key] }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-base-300 bg-base-100 p-5 shadow-sm">
                    <h3 class="mb-4 text-base font-semibold">Aktivitas Terbaru</h3>
                    <div
                        v-if="dashboard.recent_activities.length === 0"
                        class="py-6 text-center text-sm text-base-content/50"
                    >
                        Belum ada aktivitas.
                    </div>
                    <ul v-else>
                        <li
                            v-for="(activity, index) in dashboard.recent_activities"
                            :key="activity.id"
                            class="flex gap-3"
                        >
                            <div class="flex flex-col items-center">
                                <span
                                    class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full"
                                    :class="activityDot(activity.action)"
                                />
                                <span
                                    v-if="index !== dashboard.recent_activities.length - 1"
                                    class="w-px flex-1 bg-base-300"
                                />
                            </div>
                            <div class="min-w-0 pb-4">
                                <p class="text-sm">{{ activity.description }}</p>
                                <p class="mt-0.5 text-xs text-base-content/50">
                                    {{ ACTION_LABELS[activity.action] ?? activity.action }} · {{ activity.user }} ·
                                    {{ formatDate(activity.created_at) }}
                                </p>
                            </div>
                        </li>
                    </ul>
                </div>
            </template>
        </div>
    </AppLayout>
</template>
