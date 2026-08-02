<script setup>
    import { useRoute, useRouter } from 'vue-router';
    import { useAuthStore } from '../stores/auth';
    import { initials } from '../utils/format';
    import NotificationBell from '../components/NotificationBell.vue';
    import ToastHost from '../components/ToastHost.vue';

    const route = useRoute();
    const router = useRouter();
    const auth = useAuthStore();

    async function logout() {
        await auth.logout();
        router.push({ name: 'login' });
    }
</script>

<template>
    <div class="drawer lg:drawer-open">
        <input id="app-drawer" type="checkbox" class="drawer-toggle" />

        <div class="drawer-content flex min-h-screen flex-col">
            <header class="navbar sticky top-0 z-30 border-b border-base-300 bg-base-100">
                <div class="flex-none lg:hidden">
                    <label for="app-drawer" aria-label="Buka menu" class="btn btn-square btn-ghost">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            class="h-6 w-6 stroke-current"
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

                <div class="flex-none">
                    <NotificationBell />

                    <div class="dropdown dropdown-end">
                        <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar">
                            <div
                                class="flex w-9 items-center justify-center rounded-full bg-primary text-sm font-semibold text-primary-content"
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
                                <a @click="logout">Keluar</a>
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
                <div class="flex h-16 items-center px-6">
                    <span class="text-xl font-semibold tracking-tight">
                        Doc<span class="text-primary">Flow</span>
                    </span>
                </div>

                <ul class="menu p-4">
                    <li>
                        <RouterLink to="/dashboard">Dashboard</RouterLink>
                    </li>
                    <li>
                        <RouterLink to="/projects">Project</RouterLink>
                    </li>
                    <li v-if="auth.isReviewer || auth.isAdmin">
                        <RouterLink to="/reviews">Review</RouterLink>
                    </li>

                    <template v-if="auth.isAdmin">
                        <li class="menu-title mt-4">Manajemen</li>
                        <li>
                            <RouterLink to="/users">Pengguna</RouterLink>
                        </li>
                    </template>
                    <li>
                        <RouterLink to="/activity-logs">Aktivitas</RouterLink>
                    </li>
                </ul>
            </aside>
        </div>
    </div>

    <ToastHost />
</template>
