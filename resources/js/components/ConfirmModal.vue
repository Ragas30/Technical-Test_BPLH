<script setup>
    import { ref, watch } from 'vue';

    const props = defineProps({
        modelValue: { type: Boolean, default: false },
        title: { type: String, default: 'Konfirmasi' },
        message: { type: String, default: '' },
        confirmLabel: { type: String, default: 'Konfirmasi' },
        confirmClass: { type: String, default: 'btn-primary' },
    });

    const emit = defineEmits(['update:modelValue', 'confirm']);

    const submitting = ref(false);

    function close() {
        if (submitting.value) return;
        emit('update:modelValue', false);
    }

    function confirm() {
        emit('confirm');
    }

    watch(
        () => props.modelValue,
        (open) => {
            if (!open) {
                submitting.value = false;
            }
        }
    );

    defineExpose({
        setSubmitting(value) {
            submitting.value = value;
        },
    });
</script>

<template>
    <dialog class="modal" :class="{ 'modal-open': modelValue }">
        <div class="modal-box">
            <button
                type="button"
                class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3"
                aria-label="Tutup"
                :disabled="submitting"
                @click="close"
            >
                ✕
            </button>

            <h3 class="text-lg font-semibold">{{ title }}</h3>
            <p class="mt-2 whitespace-pre-line text-sm text-base-content/70">{{ message }}</p>

            <div class="modal-action">
                <button type="button" class="btn" :disabled="submitting" @click="close">Batal</button>
                <button type="button" class="btn" :class="confirmClass" :disabled="submitting" @click="confirm">
                    <span v-if="submitting" class="loading loading-spinner loading-sm" />
                    {{ confirmLabel }}
                </button>
            </div>
        </div>

        <form method="dialog" class="modal-backdrop">
            <button type="button" :disabled="submitting" @click="close">tutup</button>
        </form>
    </dialog>
</template>
