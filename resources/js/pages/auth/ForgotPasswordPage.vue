<script setup>
    import { reactive, ref } from 'vue';
    import AuthLayout from '../../layouts/AuthLayout.vue';
    import { authService } from '../../services/auth';
    import { useToastStore } from '../../stores/toast';

    const toast = useToastStore();

    const form = reactive({ email: '' });
    const submitting = ref(false);
    const sent = ref(false);

    async function submit() {
        submitting.value = true;

        try {
            await authService.forgotPassword(form.email);
            sent.value = true;
        } catch (error) {
            toast.error(error?.response?.data?.message ?? 'Gagal mengirim link reset password.');
        } finally {
            submitting.value = false;
        }
    }
</script>

<template>
    <AuthLayout>
        <h2 class="card-title justify-center text-2xl">Lupa Password</h2>
        <p class="mt-2 text-center text-sm text-base-content/60">
            Masukkan email Anda dan kami akan mengirimkan link untuk mengatur ulang password.
        </p>

        <div v-if="sent" class="alert alert-success mt-6">
            <span>Link reset password telah dikirim ke email Anda.</span>
        </div>

        <form v-else @submit.prevent="submit" class="mt-6 space-y-4">
            <label class="form-control w-full">
                <div class="label">
                    <span class="label-text">Email</span>
                </div>
                <input
                    v-model="form.email"
                    type="email"
                    placeholder="nama@contoh.com"
                    class="input input-bordered w-full"
                    autocomplete="email"
                    required
                />
            </label>

            <button type="submit" class="btn btn-primary w-full" :disabled="submitting">
                <span v-if="submitting" class="loading loading-spinner loading-sm" />
                Kirim Link
            </button>
        </form>

        <p class="mt-6 text-center text-sm">
            <RouterLink :to="{ name: 'login' }" class="link link-primary">Kembali ke login</RouterLink>
        </p>
    </AuthLayout>
</template>
