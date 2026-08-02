<script setup>
    import { computed, reactive, ref, watch } from 'vue';
    import { projectService } from '../services/projects';
    import { documentService } from '../services/documents';
    import { useToastStore } from '../stores/toast';
    import { useApiErrors } from '../composables/useApiErrors';
    import { ALLOWED_EXTENSIONS, MAX_FILES_PER_UPLOAD, MAX_UPLOAD_SIZE, formatBytes } from '../constants/documents';

    const props = defineProps({
        modelValue: { type: Boolean, default: false },
        projectId: { type: String, default: null },
    });

    const emit = defineEmits(['update:modelValue', 'saved']);

    const toast = useToastStore();
    const { errors, setErrors, resetErrors, fieldErrors } = useApiErrors();

    const loading = ref(false);
    const submitting = ref(false);
    const fileInput = ref(null);
    const selectedFiles = ref([]);

    const form = reactive({
        title: '',
        description: '',
    });

    const isEdit = computed(() => Boolean(props.projectId));

    function close() {
        if (submitting.value) return;
        emit('update:modelValue', false);
    }

    function resetForm() {
        form.title = '';
        form.description = '';
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

    async function loadProject() {
        loading.value = true;

        try {
            const { data } = await projectService.show(props.projectId);
            form.title = data.data.title;
            form.description = data.data.description ?? '';
        } catch {
            toast.error('Gagal memuat data project.');
            close();
        } finally {
            loading.value = false;
        }
    }

    async function submit() {
        submitting.value = true;
        resetErrors();

        try {
            if (isEdit.value) {
                await projectService.update(props.projectId, form);
                toast.success('Project berhasil diperbarui.');
            } else {
                const { data } = await projectService.create(form);

                let uploadSucceeded = true;
                let uploadMessage = '';

                if (selectedFiles.value.length) {
                    const formData = new FormData();
                    selectedFiles.value.forEach((file) => formData.append('documents[]', file));

                    try {
                        await documentService.upload(data.data.id, formData);
                    } catch (error) {
                        uploadSucceeded = false;
                        uploadMessage = error?.response?.data?.message ?? 'gagal mengunggah dokumen';
                    }
                }

                toast.success(
                    uploadSucceeded ? 'Project berhasil dibuat.' : `Project berhasil dibuat, tetapi ${uploadMessage}.`
                );
            }

            emit('saved');
            close();
        } catch (error) {
            setErrors(error);
            if (Object.keys(errors.value).length === 0) {
                toast.error(error?.response?.data?.message ?? 'Gagal menyimpan project.');
            }
        } finally {
            submitting.value = false;
        }
    }

    watch(
        () => props.modelValue,
        (open) => {
            if (!open) return;

            resetForm();

            if (isEdit.value) {
                loadProject();
            }
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

            <h3 class="text-lg font-semibold">{{ isEdit ? 'Edit Project' : 'Buat Project Baru' }}</h3>

            <div v-if="loading" class="flex justify-center py-12">
                <span class="loading loading-spinner loading-lg text-primary" />
            </div>

            <form v-else @submit.prevent="submit" class="mt-4 space-y-4">
                <label class="form-control w-full">
                    <div class="label">
                        <span class="label-text">Judul</span>
                    </div>
                    <input
                        v-model="form.title"
                        type="text"
                        class="input input-bordered w-full"
                        :class="{ 'input-error': fieldErrors('title').length }"
                        placeholder="Judul project"
                        required
                    />
                    <div v-if="fieldErrors('title').length" class="label">
                        <span class="label-text-alt text-error">{{ fieldErrors('title')[0] }}</span>
                    </div>
                </label>

                <label class="form-control w-full">
                    <div class="label">
                        <span class="label-text">Deskripsi</span>
                    </div>
                    <textarea
                        v-model="form.description"
                        class="textarea textarea-bordered w-full"
                        :class="{ 'textarea-error': fieldErrors('description').length }"
                        rows="5"
                        placeholder="Deskripsi lengkap project (opsional)"
                    />
                    <div v-if="fieldErrors('description').length" class="label">
                        <span class="label-text-alt text-error">{{ fieldErrors('description')[0] }}</span>
                    </div>
                </label>

                <div v-if="!isEdit">
                    <div class="label">
                        <span class="label-text">Dokumen Permohonan (Opsional)</span>
                    </div>

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
                        <p class="text-sm font-medium">Klik untuk pilih berkas</p>
                        <p class="text-xs text-base-content/50">
                            Format: {{ ALLOWED_EXTENSIONS.join(', ') }} · Maks {{ formatBytes(MAX_UPLOAD_SIZE) }} per
                            berkas · Maks {{ MAX_FILES_PER_UPLOAD }} berkas
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
                    <button type="submit" class="btn btn-primary" :disabled="submitting">
                        <span v-if="submitting" class="loading loading-spinner loading-sm" />
                        {{ isEdit ? 'Simpan Perubahan' : 'Buat Project' }}
                    </button>
                </div>
            </form>
        </div>

        <form method="dialog" class="modal-backdrop">
            <button type="button" :disabled="submitting" @click="close">tutup</button>
        </form>
    </dialog>
</template>
