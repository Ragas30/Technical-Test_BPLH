<script setup>
    import { reactive, ref } from 'vue';
    import { useRoute, useRouter } from 'vue-router';
    import AuthLayout from '../../layouts/AuthLayout.vue';
    import { authService } from '../../services/auth';
    import { useToastStore } from '../../stores/toast';
    import { useApiErrors } from '../../composables/useApiErrors';

    const route = useRoute();
    const router = useRouter();
    const toast = useToastStore();
    const { setErrors, resetErrors, fieldErrors } = useApiErrors();

    const form = reactive({
        email: route.query.email ?? '',
        token: route.query.token ?? '',
        password: '',
        password_confirmation: '',
    });

    const submitting = ref(false);

    async function submit() {
        submitting.value = true;
        resetErrors();

        try {
            await authService.resetPassword(form);
            toast.success('Password berhasil direset. Silakan login kembali.');
            router.push({ name: 'login' });
        } catch (error) {
            setErrors(error);
            if (Object.keys(fieldErrors('email')).length === 0) {
                toast.error(error?.response?.data?.message ?? 'Gagal mereset password.');
            }
        } finally {
            submitting.value = false;
        }
    }
</script>

<template>
    <AuthLayout>
        <h2 class="card-title justify-center text-2xl">Reset Password</h2>

        <form @submit.prevent="submit" class="mt-6 space-y-4">
            <label class="form-control w-full">
                <div class="label">
                    <span class="label-text">Email</span>
                </div>
                <input
                    v-model="form.email"
                    type="email"
                    class="input input-bordered w-full"
                    :class="{ 'input-error': fieldErrors('email').length }"
                    required
                />
                <div v-if="fieldErrors('email').length" class="label">
                    <span class="label-text-alt text-error">{{ fieldErrors('email')[0] }}</span>
                </div>
            </label>

            <label class="form-control w-full">
                <div class="label">
                    <span class="label-text">Token</span>
                </div>
                <input
                    v-model="form.token"
                    type="text"
                    class="input input-bordered w-full"
                    :class="{ 'input-error': fieldErrors('token').length }"
                    required
                />
                <div v-if="fieldErrors('token').length" class="label">
                    <span class="label-text-alt text-error">{{ fieldErrors('token')[0] }}</span>
                </div>
            </label>

            <label class="form-control w-full">
                <div class="label">
                    <span class="label-text">Password Baru</span>
                </div>
                <input
                    v-model="form.password"
                    type="password"
                    class="input input-bordered w-full"
                    :class="{ 'input-error': fieldErrors('password').length }"
                    autocomplete="new-password"
                    required
                />
                <div v-if="fieldErrors('password').length" class="label">
                    <span class="label-text-alt text-error">{{ fieldErrors('password')[0] }}</span>
                </div>
            </label>

            <label class="form-control w-full">
                <div class="label">
                    <span class="label-text">Konfirmasi Password</span>
                </div>
                <input
                    v-model="form.password_confirmation"
                    type="password"
                    class="input input-bordered w-full"
                    autocomplete="new-password"
                    required
                />
            </label>

            <button type="submit" class="btn btn-primary w-full" :disabled="submitting">
                <span v-if="submitting" class="loading loading-spinner loading-sm" />
                Reset Password
            </button>
        </form>
    </AuthLayout>
</template>
