<script setup>
    import { ref, watch } from 'vue';
    import { documentService } from '../services/documents';
    import { useToastStore } from '../stores/toast';
    import { useApiErrors } from '../composables/useApiErrors';
    import { ALLOWED_EXTENSIONS, MAX_FILES_PER_UPLOAD, MAX_UPLOAD_SIZE, formatBytes } from '../constants/documents';

    const props = defineProps({
        modelValue: { type: Boolean, default: false },
        projectId: { type: String, default: null },
    });

    const emit = defineEmits(['update:modelValue', 'uploaded']);

    const toast = useToastStore();
    const { errors, setErrors, resetErrors } = useApiErrors();

    const submitting = ref(false);
    const fileInput = ref(null);
    const selectedFiles = ref([]);

    function close() {
        if (submitting.value) return;
        emit('update:modelValue', false);
    }

    function resetState() {
        selectedFiles.value = [];
        resetErrors();
    }

    function onFilesChange(event) {
        const files = Array.from(event.target.files ?? []);

        if (!files.length) return;

        const invalid = files.filter((file) => !ALLOWED_EXTENSIONS.includes(file.name.split('.').pop()?.toLowerCase()));

        if (invalid.length) {
            toast.error('Tipe berkas tidak didukung. Gunakan: ' + ALLOWED_EXTENSIONS.join(', '));
            fileInput.value.value = '';
            return;
        }

        const oversized = files.filter((file) => file.size > MAX_UPLOAD_SIZE);

        if (oversized.length) {
            toast.error(`Ukuran berkas maksimal ${formatBytes(MAX_UPLOAD_SIZE)}.`);
            fileInput.value.value = '';
            return;
        }

        selectedFiles.value = [...selectedFiles.value, ...files].slice(0, MAX_FILES_PER_UPLOAD);

        if (selectedFiles.value.length >= MAX_FILES_PER_UPLOAD) {
            toast.info(`Maksimal ${MAX_FILES_PER_UPLOAD} berkas per unggahan.`);
        }

        fileInput.value.value = '';
    }

    function removeFile(index) {
        selectedFiles.value.splice(index, 1);
    }

    async function submit() {
        if (!selectedFiles.value.length) return;

        submitting.value = true;
        resetErrors();

        const formData = new FormData();
        selectedFiles.value.forEach((file) => formData.append('documents[]', file));

        try {
            await documentService.upload(props.projectId, formData);
            toast.success('Dokumen berhasil diunggah.');
            emit('uploaded');
            close();
        } catch (error) {
            setErrors(error);
            if (Object.keys(errors.value).length === 0) {
                toast.error(error?.response?.data?.message ?? 'Gagal mengunggah dokumen.');
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

            <h3 class="text-lg font-semibold">Unggah Dokumen</h3>

            <div class="mt-4">
                <div
                    class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-box border-2 border-dashed border-base-300 bg-base-200/50 px-6 py-10 text-center"
                    @click="fileInput?.click()"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                        class="h-10 w-10 text-base-content/40"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z"
                        />
                    </svg>
                    <p class="text-sm font-medium">Klik untuk pilih berkas</p>
                    <p class="text-xs text-base-content/50">
                        Format: {{ ALLOWED_EXTENSIONS.join(', ') }} · Maks {{ formatBytes(MAX_UPLOAD_SIZE) }} per berkas
                        · Maks {{ MAX_FILES_PER_UPLOAD }} berkas
                    </p>
                </div>

                <input
                    ref="fileInput"
                    type="file"
                    multiple
                    :accept="ALLOWED_EXTENSIONS.map((ext) => `.${ext}`).join(',')"
                    class="hidden"
                    @change="onFilesChange"
                />

                <ul v-if="selectedFiles.length" class="mt-4 space-y-2">
                    <li
                        v-for="(file, index) in selectedFiles"
                        :key="`${file.name}-${index}`"
                        class="flex items-center justify-between gap-3 rounded-box border border-base-300 bg-base-100 px-3 py-2 text-sm"
                    >
                        <div class="flex min-w-0 items-center gap-2">
                            <span class="badge badge-ghost font-mono text-xs">
                                .{{ file.name.split('.').pop()?.toLowerCase() }}
                            </span>
                            <span class="truncate">{{ file.name }}</span>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <span class="text-xs text-base-content/50">{{ formatBytes(file.size) }}</span>
                            <button
                                type="button"
                                class="btn btn-ghost btn-xs text-error"
                                :disabled="submitting"
                                aria-label="Hapus berkas"
                                @click="removeFile(index)"
                            >
                                ✕
                            </button>
                        </div>
                    </li>
                </ul>

                <div v-if="Object.keys(errors).length" class="alert alert-error mt-4 py-2 text-sm">
                    <span>{{ Object.values(errors)[0]?.[0] }}</span>
                </div>
            </div>

            <div class="modal-action">
                <button type="button" class="btn" :disabled="submitting" @click="close">Batal</button>
                <button
                    type="button"
                    class="btn btn-primary"
                    :disabled="submitting || !selectedFiles.length"
                    @click="submit"
                >
                    <span v-if="submitting" class="loading loading-spinner loading-sm" />
                    {{ submitting ? 'Mengunggah...' : 'Unggah' }}
                </button>
            </div>
        </div>

        <form method="dialog" class="modal-backdrop">
            <button type="button" :disabled="submitting" @click="close">tutup</button>
        </form>
    </dialog>
</template>
