<script setup>
    import { ref, watch } from 'vue';
    import { documentService } from '../services/documents';
    import { useToastStore } from '../stores/toast';
    import { useApiErrors } from '../composables/useApiErrors';
    import { ALLOWED_EXTENSIONS, MAX_UPLOAD_SIZE, formatBytes } from '../constants/documents';

    const props = defineProps({
        modelValue: { type: Boolean, default: false },
        documentId: { type: String, default: null },
        currentName: { type: String, default: '' },
    });

    const emit = defineEmits(['update:modelValue', 'replaced']);

    const toast = useToastStore();
    const { errors, setErrors, resetErrors } = useApiErrors();

    const submitting = ref(false);
    const fileInput = ref(null);
    const selectedFile = ref(null);

    function close() {
        if (submitting.value) return;
        emit('update:modelValue', false);
    }

    function resetState() {
        selectedFile.value = null;
        resetErrors();
    }

    function onFileChange(event) {
        const file = event.target.files?.[0];

        if (!file) {
            selectedFile.value = null;
            return;
        }

        const extension = file.name.split('.').pop()?.toLowerCase();

        if (!ALLOWED_EXTENSIONS.includes(extension)) {
            toast.error('Tipe berkas tidak didukung. Gunakan: ' + ALLOWED_EXTENSIONS.join(', '));
            fileInput.value.value = '';
            return;
        }

        if (file.size > MAX_UPLOAD_SIZE) {
            toast.error(`Ukuran berkas maksimal ${formatBytes(MAX_UPLOAD_SIZE)}.`);
            fileInput.value.value = '';
            return;
        }

        selectedFile.value = file;
    }

    async function submit() {
        if (!selectedFile.value) return;

        submitting.value = true;
        resetErrors();

        const formData = new FormData();
        formData.append('file', selectedFile.value);

        try {
            await documentService.replace(props.documentId, formData);
            toast.success('Dokumen berhasil diperbarui.');
            emit('replaced');
            close();
        } catch (error) {
            setErrors(error);
            if (Object.keys(errors.value).length === 0) {
                toast.error(error?.response?.data?.message ?? 'Gagal memperbarui dokumen.');
            }
        } finally {
            submitting.value = false;
        }
    }

    watch(
        () => props.modelValue,
        (open) => {
            if (open) resetState();
        }
    );
</script>

<template>
    <dialog class="modal" :class="{ 'modal-open': modelValue }">
        <div class="modal-box max-w-lg overflow-y-auto">
            <button
                type="button"
                class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3"
                aria-label="Tutup"
                :disabled="submitting"
                @click="close"
            >
                ✕
            </button>

            <h3 class="text-lg font-semibold">Ganti Dokumen</h3>
            <p class="mt-1 text-sm text-base-content/60">{{ currentName }}</p>

            <div class="mt-4">
                <div
                    class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-box border-2 border-dashed border-base-300 bg-base-200/50 px-6 py-8 text-center"
                    @click="fileInput?.click()"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                        class="h-8 w-8 text-base-content/40"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z"
                        />
                    </svg>
                    <p v-if="selectedFile" class="text-sm font-medium">{{ selectedFile.name }}</p>
                    <p v-else class="text-sm font-medium">Klik untuk pilih berkas pengganti</p>
                    <p class="text-xs text-base-content/50">
                        Format: {{ ALLOWED_EXTENSIONS.join(', ') }} · Maks {{ formatBytes(MAX_UPLOAD_SIZE) }}
                    </p>
                </div>

                <input
                    ref="fileInput"
                    type="file"
                    :accept="ALLOWED_EXTENSIONS.map((ext) => `.${ext}`).join(',')"
                    class="hidden"
                    @change="onFileChange"
                />

                <div v-if="Object.keys(errors).length" class="alert alert-error mt-4 py-2 text-sm">
                    <span>{{ Object.values(errors)[0]?.[0] }}</span>
                </div>
            </div>

            <div class="modal-action">
                <button type="button" class="btn" :disabled="submitting" @click="close">Batal</button>
                <button type="button" class="btn btn-primary" :disabled="submitting || !selectedFile" @click="submit">
                    <span v-if="submitting" class="loading loading-spinner loading-sm" />
                    {{ submitting ? 'Mengganti...' : 'Ganti' }}
                </button>
            </div>
        </div>

        <form method="dialog" class="modal-backdrop">
            <button type="button" :disabled="submitting" @click="close">tutup</button>
        </form>
    </dialog>
</template>
