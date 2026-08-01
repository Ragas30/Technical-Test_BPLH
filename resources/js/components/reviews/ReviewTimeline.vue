<script setup>
    import { computed } from 'vue';
    import { REVIEW_LOG_ACTIONS, REVIEW_STATUS } from '../../constants/roles';
    import { formatDate } from '../../utils/format';

    const props = defineProps({
        reviews: { type: Array, default: () => [] },
    });

    const latest = computed(() => props.reviews[0] ?? null);

    const timeline = computed(() =>
        [...props.reviews]
            .flatMap((review) =>
                (review.logs ?? []).map((log) => ({ ...log, reviewId: review.id }))
            )
            .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
    );

    function statusLabel(status) {
        return REVIEW_STATUS[status]?.label ?? status;
    }

    function statusBadge(status) {
        return REVIEW_STATUS[status]?.badge ?? 'badge-neutral';
    }

    function actionLabel(action) {
        return REVIEW_LOG_ACTIONS[action]?.label ?? action;
    }

    function actionBadge(action) {
        return REVIEW_LOG_ACTIONS[action]?.badge ?? 'badge-ghost';
    }
</script>

<template>
    <div class="space-y-6">
        <div class="card border border-base-300 bg-base-100 shadow-sm">
            <div class="card-body">
                <h3 class="card-title text-base">Hasil Review Terakhir</h3>

                <div v-if="!latest" class="py-8 text-center text-sm text-base-content/50">
                    Belum ada review untuk project ini.
                </div>

                <div v-else class="space-y-3 text-sm">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="badge" :class="statusBadge(latest.status)">
                            {{ statusLabel(latest.status) }}
                        </span>
                        <span v-if="latest.reviewer" class="text-base-content/60">oleh {{ latest.reviewer.name }}</span>
                        <span class="text-xs text-base-content/50">
                            {{ formatDate(latest.reviewed_at ?? latest.created_at) }}
                        </span>
                    </div>
                    <p v-if="latest.notes" class="whitespace-pre-line rounded-box bg-base-200 p-3">
                        {{ latest.notes }}
                    </p>
                    <p v-else class="text-base-content/50">Tidak ada catatan pada review ini.</p>
                </div>
            </div>
        </div>

        <div class="card border border-base-300 bg-base-100 shadow-sm">
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
                                <span class="badge" :class="actionBadge(log.action)">
                                    {{ actionLabel(log.action) }}
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
    </div>
</template>
