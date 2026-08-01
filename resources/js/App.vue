<script setup>
    import { onMounted, onUnmounted } from 'vue';
    import { useRouter } from 'vue-router';
    import { useAuthStore } from './stores/auth';

    const router = useRouter();
    const auth = useAuthStore();

    function handleUnauthorized() {
        auth.clearSession();
        router.push({ name: 'login' });
    }

    onMounted(() => window.addEventListener('docflow:unauthorized', handleUnauthorized));
    onUnmounted(() => window.removeEventListener('docflow:unauthorized', handleUnauthorized));
</script>

<template>
    <RouterView />
</template>
