<script setup>
    import { computed, reactive, ref, watch } from 'vue';
    import { projectService } from '../services/projects';
    import { useToastStore } from '../stores/toast';
    import { useApiErrors } from '../composables/useApiErrors';

    const props = defineProps({
        modelValue: { type: Boolean, default: false },
        projectId: { type: String, default: null },
    });

    const emit = defineEmits(['update:modelValue', 'saved']);

    const toast = useToastStore();
    const { errors, setErrors, resetErrors, fieldErrors } = useApiErrors();

    const loading = ref(false);
    const submitting = ref(false);

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
        resetErrors();
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
                await projectService.create(form);
                toast.success('Project berhasil dibuat.');
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

                <div class="modal-action">
                    <button type="button" class="btn" :disabled="submitting" @click="close">Batal</button>
                    <button type="submit" class="btn btn-primary" :disabled="submitting">
                        <span v-if="submitting" class="loading loading-spinner loading-sm" />
                        {{ isEdit ? 'Simpan Perubahan' : 'Simpan' }}
                    </button>
                </div>
            </form>
        </div>

        <form method="dialog" class="modal-backdrop">
            <button type="button" :disabled="submitting" @click="close">tutup</button>
        </form>
    </dialog>
</template>
