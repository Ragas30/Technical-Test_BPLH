import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const routes = [
    {
        path: '/login',
        name: 'login',
        component: () => import('../pages/auth/LoginPage.vue'),
        meta: { guest: true, title: 'Masuk' },
    },
    {
        path: '/register',
        name: 'register',
        component: () => import('../pages/auth/RegisterPage.vue'),
        meta: { guest: true, title: 'Daftar' },
    },
    {
        path: '/forgot-password',
        name: 'forgot-password',
        component: () => import('../pages/auth/ForgotPasswordPage.vue'),
        meta: { guest: true, title: 'Lupa Password' },
    },
    {
        path: '/reset-password',
        name: 'reset-password',
        component: () => import('../pages/auth/ResetPasswordPage.vue'),
        meta: { guest: true, title: 'Reset Password' },
    },
    {
        path: '/dashboard',
        name: 'dashboard',
        component: () => import('../pages/dashboard/DashboardPage.vue'),
        meta: { requiresAuth: true, title: 'Dashboard' },
    },
    {
        path: '/projects',
        name: 'projects.index',
        component: () => import('../pages/projects/ProjectListPage.vue'),
        meta: { requiresAuth: true, title: 'Project' },
    },
    {
        path: '/projects/:id',
        name: 'projects.show',
        component: () => import('../pages/projects/ProjectDetailPage.vue'),
        meta: { requiresAuth: true, title: 'Detail Project' },
    },
    {
        path: '/reviews',
        name: 'reviews.index',
        component: () => import('../pages/reviews/ReviewListPage.vue'),
        meta: { requiresAuth: true, roles: ['admin', 'reviewer'], title: 'Review' },
    },
    {
        path: '/reviews/:id',
        name: 'reviews.show',
        component: () => import('../pages/reviews/ReviewDetailPage.vue'),
        meta: { requiresAuth: true, roles: ['admin', 'reviewer'], title: 'Detail Review' },
    },
    {
        path: '/users',
        name: 'users.index',
        component: () => import('../pages/users/UserListPage.vue'),
        meta: { requiresAuth: true, roles: ['admin'], title: 'Pengguna' },
    },
    {
        path: '/users/:id',
        name: 'users.show',
        component: () => import('../pages/users/UserDetailPage.vue'),
        meta: { requiresAuth: true, roles: ['admin'], title: 'Detail Pengguna' },
    },
    {
        path: '/:pathMatch(.*)*',
        redirect: { name: 'dashboard' },
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();

    if (to.meta.guest && auth.isAuthenticated) {
        return { name: 'dashboard' };
    }

    if (to.meta.requiresAuth) {
        if (!auth.isAuthenticated) {
            return { name: 'login', query: { redirect: to.fullPath } };
        }

        if (!auth.user) {
            try {
                await auth.fetchProfile();
            } catch {
                auth.clearSession();

                return { name: 'login' };
            }
        }

        if (to.meta.roles && !to.meta.roles.some((role) => auth.roles.includes(role))) {
            return { name: 'dashboard' };
        }
    }
});

router.afterEach((to) => {
    const title = to.meta.title ?? 'DocFlow';
    document.title = `${title} | DocFlow`;
});

export default router;
