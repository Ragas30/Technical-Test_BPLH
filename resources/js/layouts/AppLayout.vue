<script setup>
    import { computed } from 'vue';
    import { useRoute, useRouter } from 'vue-router';
    import { useAuthStore } from '../stores/auth';
    import { initials } from '../utils/format';
    import NotificationBell from '../components/NotificationBell.vue';
    import ToastHost from '../components/ToastHost.vue';

    const route = useRoute();
    const router = useRouter();
    const auth = useAuthStore();

    const NAV_ICONS = {
        dashboard:
            'M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z',
        folder: 'M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z',
        shield: 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z',
        users: 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z',
        clock: 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        logout: 'M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9',
    };

    const navItems = computed(() => {
        const items = [
            { to: '/dashboard', label: 'Dashboard', icon: NAV_ICONS.dashboard },
            { to: '/projects', label: 'Project', icon: NAV_ICONS.folder },
        ];

        if (auth.isReviewer || auth.isAdmin) {
            items.push({ to: '/reviews', label: 'Review', icon: NAV_ICONS.shield });
        }

        if (auth.isAdmin) {
            items.push({ to: '/users', label: 'Pengguna', icon: NAV_ICONS.users });
        }

        items.push({ to: '/activity-logs', label: 'Aktivitas', icon: NAV_ICONS.clock });

        return items;
    });

    function isActive(item) {
        return route.path === item.to || route.path.startsWith(`${item.to}/`);
    }

    async function logout() {
        await auth.logout();
        router.push({ name: 'login' });
    }
</script>

<template>
    <div class="drawer lg:drawer-open">
        <input id="app-drawer" type="checkbox" class="drawer-toggle" />

        <div class="drawer-content flex min-h-screen flex-col bg-base-200">
            <header class="navbar sticky top-0 z-30 border-b border-base-300 bg-base-100/80 backdrop-blur">
                <div class="flex-none lg:hidden">
                    <label for="app-drawer" aria-label="Buka menu" class="btn btn-square btn-ghost">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            class="h-5 w-5 stroke-current"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                        </svg>
                    </label>
                </div>

                <div class="flex-1">
                    <h1 class="text-lg font-semibold">{{ route.meta.title ?? 'DocFlow' }}</h1>
                </div>

                <div class="flex flex-none items-center gap-1">
                    <NotificationBell />

                    <div class="dropdown dropdown-end">
                        <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar">
                            <div
                                class="flex w-9 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 text-sm font-semibold text-white"
                            >
                                {{ initials(auth.user?.name) }}
                            </div>
                        </div>
                        <ul
                            tabindex="0"
                            class="menu dropdown-content z-[1] mt-2 w-64 rounded-box border border-base-300 bg-base-100 p-2 shadow"
                        >
                            <li>
                                <div class="flex flex-col items-start">
                                    <span class="font-semibold">{{ auth.user?.name }}</span>
                                    <span class="text-xs text-base-content/60">{{ auth.user?.email }}</span>
                                </div>
                            </li>
                            <div class="divider my-1" />
                            <li>
                                <a class="text-error" @click="logout">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="1.5"
                                        stroke="currentColor"
                                        class="h-4 w-4"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" :d="NAV_ICONS.logout" />
                                    </svg>
                                    Keluar
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>

            <main class="flex-1 p-4 lg:p-6">
                <div class="mx-auto w-full max-w-7xl">
                    <slot />
                </div>
            </main>
        </div>

        <div class="drawer-side z-40">
            <label for="app-drawer" aria-label="Tutup menu" class="drawer-overlay"></label>
            <aside class="flex min-h-full w-64 flex-col border-r border-base-300 bg-base-100">
                <div class="flex h-16 items-center gap-2.5 px-6">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-indigo-500 to-violet-600 text-white"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.8"
                            stroke="currentColor"
                            class="h-4.5 w-4.5"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"
                            />
                        </svg>
                    </div>
                    <span class="text-lg font-bold tracking-tight"> Doc<span class="text-primary">Flow</span> </span>
                </div>

                <nav class="flex-1 space-y-1 px-3 py-4">
                    <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-wider text-base-content/40">Menu</p>
                    <RouterLink
                        v-for="item in navItems"
                        :key="item.to"
                        :to="item.to"
                        class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors"
                        :class="
                            isActive(item)
                                ? 'bg-primary/10 text-primary'
                                : 'text-base-content/70 hover:bg-base-200 hover:text-base-content'
                        "
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.6"
                            stroke="currentColor"
                            class="h-5 w-5 shrink-0"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                        </svg>
                        {{ item.label }}
                    </RouterLink>
                </nav>

                <div class="border-t border-base-300 p-3">
                    <div class="flex items-center gap-3 rounded-xl bg-base-200 p-3">
                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 text-xs font-semibold text-white"
                        >
                            {{ initials(auth.user?.name) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ auth.user?.name }}</p>
                            <p class="truncate text-xs text-base-content/50">{{ auth.user?.email }}</p>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <ToastHost />
</template>
