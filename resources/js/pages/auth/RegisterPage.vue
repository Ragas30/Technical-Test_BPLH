<script setup>
    import { reactive, ref } from 'vue';
    import { useRouter } from 'vue-router';
    import AuthLayout from '../../layouts/AuthLayout.vue';
    import { useAuthStore } from '../../stores/auth';
    import { useToastStore } from '../../stores/toast';
    import { useApiErrors } from '../../composables/useApiErrors';

    const router = useRouter();
    const auth = useAuthStore();
    const toast = useToastStore();
    const { setErrors, resetErrors, fieldErrors } = useApiErrors();

    const form = reactive({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submitting = ref(false);

    async function submit() {
        submitting.value = true;
        resetErrors();

        try {
            await auth.register(form);
            toast.success('Registrasi berhasil. Selamat datang!');
            router.push('/dashboard');
        } catch (error) {
            setErrors(error);
            if (Object.keys(fieldErrors('name')).length === 0) {
                toast.error(error?.response?.data?.message ?? 'Registrasi gagal.');
            }
        } finally {
            submitting.value = false;
        }
    }
</script>

<template>
    <AuthLayout>
        <h2 class="card-title justify-center text-2xl">Daftar Akun</h2>

        <form @submit.prevent="submit" class="mt-6 space-y-4">
            <label class="form-control w-full">
                <div class="label">
                    <span class="label-text">Nama Lengkap</span>
                </div>
                <input
                    v-model="form.name"
                    type="text"
                    placeholder="Nama lengkap"
                    class="input input-bordered w-full"
                    :class="{ 'input-error': fieldErrors('name').length }"
                    autocomplete="name"
                    required
                />
                <div v-if="fieldErrors('name').length" class="label">
                    <span class="label-text-alt text-error">{{ fieldErrors('name')[0] }}</span>
                </div>
            </label>

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
                    placeholder="Minimal 8 karakter"
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
                    placeholder="Ulangi password"
                    class="input input-bordered w-full"
                    autocomplete="new-password"
                    required
                />
            </label>

            <button type="submit" class="btn btn-primary w-full" :disabled="submitting">
                <span v-if="submitting" class="loading loading-spinner loading-sm" />
                Daftar
            </button>
        </form>

        <p class="mt-6 text-center text-sm">
            Sudah punya akun?
            <RouterLink :to="{ name: 'login' }" class="link link-primary">Masuk</RouterLink>
        </p>
    </AuthLayout>
</template>
