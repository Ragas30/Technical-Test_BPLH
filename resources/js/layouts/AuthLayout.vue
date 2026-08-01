<script setup>
    import ToastHost from '../components/ToastHost.vue';
</script>

<template>
    <div class="min-h-screen flex flex-col items-center justify-center bg-base-200 px-4">
        <div class="mb-8 text-center">
            <div class="text-4xl font-semibold tracking-tight">Doc<span class="text-primary">Flow</span></div>
            <p class="mt-2 text-sm text-base-content/60">Document Submission &amp; Approval Management System</p>
        </div>

        <div class="card w-full max-w-md bg-base-100 shadow-xl">
            <div class="card-body">
                <slot />
            </div>
        </div>

        <p class="mt-8 text-xs text-base-content/40">BPLH · 2026</p>
        <ToastHost />
    </div>
</template>
