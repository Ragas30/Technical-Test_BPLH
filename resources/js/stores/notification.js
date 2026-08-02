import { defineStore } from 'pinia';
import { notificationService } from '../services/notifications';
import { useToastStore } from './toast';

let timer = null;

export const useNotificationStore = defineStore('notification', {
    state: () => ({
        items: [],
        unreadCount: 0,
        loading: false,
        lastSeenId: null,
    }),

    actions: {
        async fetch() {
            this.loading = true;

            try {
                const { data } = await notificationService.list({ per_page: 10 });
                this.items = data.data ?? [];
                this.unreadCount = data.unread_count ?? 0;

                const newest = this.items[0];

                if (newest) {
                    if (this.lastSeenId !== null && newest.id !== this.lastSeenId && this.unreadCount > 0) {
                        const toast = useToastStore();
                        toast.success(`Notifikasi baru: ${newest.data?.title ?? 'Aktivitas baru'}`);
                    }

                    this.lastSeenId = newest.id;
                }
            } finally {
                this.loading = false;
            }
        },

        async refreshUnread() {
            try {
                const { data } = await notificationService.unreadCount();
                this.unreadCount = data.unread_count ?? 0;
            } catch {
                // abaikan, polling akan mencoba lagi
            }
        },

        async markAsRead(id) {
            try {
                await notificationService.markAsRead(id);
                const notification = this.items.find((item) => item.id === id);

                if (notification && !notification.read_at) {
                    notification.read_at = new Date().toISOString();
                    this.unreadCount = Math.max(0, this.unreadCount - 1);
                }
            } catch {
                // abaikan
            }
        },

        async markAllAsRead() {
            try {
                await notificationService.markAllAsRead();
                this.items.forEach((item) => {
                    item.read_at = item.read_at ?? new Date().toISOString();
                });
                this.unreadCount = 0;
            } catch {
                // abaikan
            }
        },

        startPolling() {
            if (timer) return;

            this.fetch();
            timer = setInterval(() => this.fetch(), 30000);
        },

        stopPolling() {
            if (timer) {
                clearInterval(timer);
                timer = null;
            }
        },
    },
});
