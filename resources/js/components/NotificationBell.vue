<script setup>
    import { computed, onMounted, onUnmounted } from 'vue';
    import { useRouter } from 'vue-router';
    import { useNotificationStore } from '../stores/notification';
    import { formatDate } from '../utils/format';

    const router = useRouter();
    const notification = useNotificationStore();

    const visibleItems = computed(() => notification.items.slice(0, 8));

    function openNotification(item) {
        if (!item.read_at) {
            notification.markAsRead(item.id);
        }

        if (item.data?.action_url) {
            router.push(item.data.action_url);
        }
    }

    onMounted(() => notification.startPolling());
    onUnmounted(() => notification.stopPolling());
</script>

<template>
    <div class="dropdown dropdown-end">
        <div tabindex="0" role="button" class="btn btn-ghost btn-circle" aria-label="Notifikasi">
            <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="h-6 w-6"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"
                />
            </svg>
            <span
                v-if="notification.unreadCount > 0"
                class="badge badge-error badge-xs absolute right-0.5 top-0.5"
            >
                {{ notification.unreadCount > 9 ? '9+' : notification.unreadCount }}
            </span>
        </div>

        <ul
            tabindex="0"
            class="menu dropdown-content z-[1] mt-2 w-80 rounded-box border border-base-300 bg-base-100 p-2 shadow"
        >
            <li class="flex items-center justify-between px-3 py-2">
                <span class="font-semibold">Notifikasi</span>
                <button
                    type="button"
                    class="btn btn-ghost btn-xs text-primary"
                    :disabled="notification.unreadCount === 0"
                    @click="notification.markAllAsRead()"
                >
                    Tandai semua dibaca
                </button>
            </li>

            <div class="divider my-1" />

            <li v-if="visibleItems.length === 0" class="pointer-events-none">
                <div class="py-8 text-center text-sm text-base-content/50">Tidak ada notifikasi.</div>
            </li>

            <li v-for="item in visibleItems" :key="item.id" class="px-1">
                <a
                    class="flex items-start gap-3"
                    :class="{ 'bg-base-200/60': !item.read_at }"
                    @click="openNotification(item)"
                >
                    <span
                        v-if="!item.read_at"
                        class="mt-2 h-2 w-2 shrink-0 rounded-full bg-primary"
                        aria-label="Belum dibaca"
                    />
                    <span v-else class="mt-2 h-2 w-2 shrink-0 rounded-full bg-transparent" />
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-medium">{{ item.data?.title }}</span>
                        <span class="block truncate text-xs text-base-content/60">{{ item.data?.message }}</span>
                        <span class="block text-xs text-base-content/40">{{ formatDate(item.created_at) }}</span>
                    </span>
                </a>
            </li>
        </ul>
    </div>
</template>
