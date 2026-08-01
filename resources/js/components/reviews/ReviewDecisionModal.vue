<script setup>
    import { computed, ref, watch } from 'vue';

    const props = defineProps({
        modelValue: { type: Boolean, default: false },
        type: { type: String, default: 'approve' },
        title: { type: String, default: '' },
        confirmLabel: { type: String, default: '' },
    });

    const emit = defineEmits(['update:modelValue', 'submitted']);

    const notes = ref('');
    const submitting = ref(false);
    const error = ref('');

    const titles = {
        approve: 'Setujui Review',
        reject: 'Tolak Review',
        revision: 'Minta Revisi',
        comment: 'Tambahkan Komentar',
    };

    const confirmLabels = {
        approve: 'Setujui',
        reject: 'Tolak',
        revision: 'Minta Revisi',
        comment: 'Kirim Komentar',
    };

    const placeholders = {
        approve: 'Catatan keputusan (opsional)',
        reject: 'Alasan penolakan',
        revision: 'Jelaskan dokumen yang perlu direvisi',
        comment: 'Tulis komentar untuk tim',
    };

    const buttonClasses = {
        approve: 'btn-primary',
        reject: 'btn-error',
        revision: 'btn-warning',
        comment: 'btn-info',
    };

    const modalTitle = computed(() => props.title || titles[props.type] || 'Konfirmasi');
    const buttonLabel = computed(() => props.confirmLabel || confirmLabels[props.type] || 'Simpan');
    const placeholder = computed(() => placeholders[props.type] ?? 'Catatan');
    const notesRequired = computed(() => ['reject', 'revision', 'comment'].includes(props.type));
    const buttonClass = computed(() => buttonClasses[props.type] ?? 'btn-primary');

    function close() {
        if (submitting.value) return;
        emit('update:modelValue', false);
    }

    function submit() {
        if (notesRequired.value && !notes.value.trim()) {
            error.value = 'Catatan wajib diisi.';
            return;
        }

        error.value = '';
        emit('submitted', { notes: notes.value.trim() || null });
    }

    watch(
        () => props.modelValue,
        (open) => {
            if (!open) return;

            notes.value = '';
            error.value = '';
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
        <div class="modal-box max-w-xl overflow-y-auto">
            <button
                type="button"
                class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3"
                aria-label="Tutup"
                :disabled="submitting"
                @click="close"
            >
                ✕
            </button>

            <h3 class="text-lg font-semibold">{{ modalTitle }}</h3>

            <form class="mt-4 space-y-4" @submit.prevent="submit">
                <label class="form-control w-full">
                    <div class="label">
                        <span class="label-text">{{ notesRequired ? 'Catatan' : 'Catatan (opsional)' }}</span>
                    </div>
                    <textarea
                        v-model="notes"
                        class="textarea textarea-bordered w-full"
                        :class="{ 'textarea-error': error }"
                        :placeholder="placeholder"
                        rows="5"
                        :required="notesRequired"
                    />
                    <div v-if="error" class="label">
                        <span class="label-text-alt text-error">{{ error }}</span>
                    </div>
                </label>

                <div class="modal-action">
                    <button type="button" class="btn" :disabled="submitting" @click="close">Batal</button>
                    <button type="submit" class="btn" :class="buttonClass" :disabled="submitting">
                        <span v-if="submitting" class="loading loading-spinner loading-sm" />
                        {{ buttonLabel }}
                    </button>
                </div>
            </form>
        </div>

        <form method="dialog" class="modal-backdrop">
            <button type="button" :disabled="submitting" @click="close">tutup</button>
        </form>
    </dialog>
</template>
