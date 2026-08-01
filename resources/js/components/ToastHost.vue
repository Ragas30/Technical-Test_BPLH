<script setup>
    import { useToastStore } from '../stores/toast';

    const toastStore = useToastStore();
</script>

<template>
    <div class="toast toast-end z-[100]">
        <div
            v-for="toast in toastStore.toasts"
            :key="toast.id"
            class="alert"
            :class="toast.type === 'error' ? 'alert-error' : 'alert-success'"
        >
            <span>{{ toast.message }}</span>
        </div>
    </div>
</template>
