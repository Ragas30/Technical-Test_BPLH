<script setup>
    import { computed, onMounted, onUnmounted, ref } from 'vue';
    import { useRouter } from 'vue-router';
    import { useNotificationStore } from '../stores/notification';
    import { formatDate } from '../utils/format';

    const router = useRouter();
    const notification = useNotificationStore();
    const isOpen = ref(false);
    const rootRef = ref(null);

    const visibleItems = computed(() => notification.items.slice(0, 8));

    async function openNotification(item) {
        if (!item.read_at) {
            await notification.markAsRead(item.id);
        }

        isOpen.value = false;

        if (item.data?.action_url) {
            router.push(item.data.action_url);
        }
    }

    async function markAllAsRead() {
        await notification.markAllAsRead();
    }

    function toggleDropdown() {
        isOpen.value = !isOpen.value;
    }

    function handleClickOutside(event) {
        if (rootRef.value && !rootRef.value.contains(event.target)) {
            isOpen.value = false;
        }
    }

    function handleEscape(event) {
        if (event.key === 'Escape') {
            isOpen.value = false;
        }
    }

    onMounted(() => notification.startPolling());
    onMounted(() => document.addEventListener('click', handleClickOutside));
    onMounted(() => document.addEventListener('keydown', handleEscape));
    onUnmounted(() => notification.stopPolling());
    onUnmounted(() => document.removeEventListener('click', handleClickOutside));
    onUnmounted(() => document.removeEventListener('keydown', handleEscape));
</script>

<template>
    <div ref="rootRef" class="relative">
        <button
            type="button"
            class="btn btn-ghost btn-circle"
            aria-label="Notifikasi"
            :aria-expanded="isOpen"
            @click="toggleDropdown"
        >
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
        </button>

        <div
            v-if="isOpen"
            class="absolute right-0 z-[60] mt-2 w-[min(22rem,calc(100vw-1rem))] overflow-hidden rounded-box border border-base-300 bg-base-100 shadow-xl"
        >
            <div class="flex items-center justify-between gap-3 border-b border-base-300 px-4 py-3">
                <span class="font-semibold">Notifikasi</span>
                <button
                    type="button"
                    class="btn btn-ghost btn-xs text-primary"
                    :disabled="notification.unreadCount === 0"
                    @click="markAllAsRead"
                >
                    Tandai semua dibaca
                </button>
            </div>

            <div v-if="visibleItems.length === 0" class="pointer-events-none">
                <div class="py-8 text-center text-sm text-base-content/50">Tidak ada notifikasi.</div>
            </div>

            <div v-else class="max-h-96 overflow-y-auto p-2">
                <button
                    v-for="item in visibleItems"
                    :key="item.id"
                    type="button"
                    class="flex w-full items-start gap-3 rounded-xl px-3 py-3 text-left transition hover:bg-base-200/80"
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
                        <span class="block text-sm font-medium leading-5">{{ item.data?.title }}</span>
                        <span class="block text-xs leading-5 text-base-content/60">{{ item.data?.message }}</span>
                        <span class="block text-xs text-base-content/40">{{ formatDate(item.created_at) }}</span>
                    </span>
                </button>
            </div>
        </div>
    </div>
</template>
