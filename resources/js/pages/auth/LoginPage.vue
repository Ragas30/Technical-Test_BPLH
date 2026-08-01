<script setup>
    import { reactive, ref } from 'vue';
    import { useRoute, useRouter } from 'vue-router';
    import AuthLayout from '../../layouts/AuthLayout.vue';
    import { useAuthStore } from '../../stores/auth';
    import { useToastStore } from '../../stores/toast';
    import { useApiErrors } from '../../composables/useApiErrors';

    const route = useRoute();
    const router = useRouter();
    const auth = useAuthStore();
    const toast = useToastStore();
    const { errors, setErrors, resetErrors, fieldErrors } = useApiErrors();

    const form = reactive({
        email: '',
        password: '',
        remember: false,
    });

    const submitting = ref(false);

    async function submit() {
        submitting.value = true;
        resetErrors();

        try {
            await auth.login(form);
            toast.success('Login berhasil.');
            const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : '/dashboard';
            router.push(redirect);
        } catch (error) {
            setErrors(error);
            if (Object.keys(errors.value).length === 0) {
                toast.error(error?.response?.data?.message ?? 'Login gagal.');
            }
        } finally {
            submitting.value = false;
        }
    }
</script>

<template>
    <AuthLayout>
        <h2 class="card-title justify-center text-2xl">Masuk</h2>

        <form @submit.prevent="submit" class="mt-6 space-y-4">
            <label class="form-control w-full">
                <div class="label">
                    <span class="label-text">Email</span>
                </div>
                <input
                    v-model="form.email"
                    type="email"
                    placeholder="nama@contoh.com"
                    class="input input-bordered w-full"
                    :class="{ 'input-error': fieldErrors('email').length }"
                    autocomplete="email"
                    required
                />
                <div v-if="fieldErrors('email').length" class="label">
                    <span class="label-text-alt text-error">{{ fieldErrors('email')[0] }}</span>
                </div>
            </label>

            <label class="form-control w-full">
                <div class="label">
                    <span class="label-text">Password</span>
                </div>
                <input
                    v-model="form.password"
                    type="password"
                    placeholder="••••••••"
                    class="input input-bordered w-full"
                    :class="{ 'input-error': fieldErrors('password').length }"
                    autocomplete="current-password"
                    required
                />
                <div v-if="fieldErrors('password').length" class="label">
                    <span class="label-text-alt text-error">{{ fieldErrors('password')[0] }}</span>
                </div>
            </label>

            <div class="flex items-center justify-between">
                <label class="label cursor-pointer gap-2">
                    <input v-model="form.remember" type="checkbox" class="checkbox checkbox-sm" />
                    <span class="label-text">Ingat saya</span>
                </label>
                <RouterLink :to="{ name: 'forgot-password' }" class="link link-primary text-sm"
                    >Lupa password?</RouterLink
                >
            </div>

            <button type="submit" class="btn btn-primary w-full" :disabled="submitting">
                <span v-if="submitting" class="loading loading-spinner loading-sm" />
                Masuk
            </button>
        </form>

        <p class="mt-6 text-center text-sm">
            Belum punya akun?
            <RouterLink :to="{ name: 'register' }" class="link link-primary">Daftar</RouterLink>
        </p>
    </AuthLayout>
</template>
