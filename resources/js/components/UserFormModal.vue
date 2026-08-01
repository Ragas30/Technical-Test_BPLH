<script setup>
    import { computed, reactive, ref, watch } from 'vue';
    import { userService } from '../services/users';
    import { roleService } from '../services/roles';
    import { useToastStore } from '../stores/toast';
    import { useApiErrors } from '../composables/useApiErrors';

    const props = defineProps({
        modelValue: { type: Boolean, default: false },
        userId: { type: String, default: null },
    });

    const emit = defineEmits(['update:modelValue', 'saved']);

    const toast = useToastStore();
    const { errors, setErrors, resetErrors, fieldErrors } = useApiErrors();

    const loading = ref(false);
    const submitting = ref(false);
    const roles = ref([]);
    const permissions = ref([]);

    const form = reactive({
        name: '',
        email: '',
        password: '',
        is_active: true,
        roles: [],
        permissions: [],
    });

    const isEdit = computed(() => Boolean(props.userId));

    function toggleArrayItem(array, value) {
        const index = array.indexOf(value);

        if (index === -1) {
            array.push(value);
        } else {
            array.splice(index, 1);
        }
    }

    function close() {
        if (submitting.value) return;
        emit('update:modelValue', false);
    }

    function resetForm() {
        form.name = '';
        form.email = '';
        form.password = '';
        form.is_active = true;
        form.roles = [];
        form.permissions = [];
        resetErrors();
    }

    async function loadMeta() {
        try {
            const [rolesResponse, permissionsResponse] = await Promise.all([
                roleService.list(),
                roleService.permissions(),
            ]);
            roles.value = rolesResponse.data.data;
            permissions.value = permissionsResponse.data.data;
        } catch {
            toast.error('Gagal memuat data role/permission.');
        }
    }

    async function loadUser() {
        loading.value = true;

        try {
            const { data } = await userService.show(props.userId);
            form.name = data.data.name;
            form.email = data.data.email;
            form.is_active = data.data.is_active;
            form.roles = [...data.data.roles];
            form.permissions = [...data.data.permissions];
        } catch {
            toast.error('Gagal memuat data pengguna.');
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
                await userService.update(props.userId, form);
                toast.success('Pengguna berhasil diperbarui.');
            } else {
                await userService.create(form);
                toast.success('Pengguna berhasil dibuat.');
            }

            emit('saved');
            close();
        } catch (error) {
            setErrors(error);
            if (Object.keys(errors.value).length === 0) {
                toast.error(error?.response?.data?.message ?? 'Gagal menyimpan pengguna.');
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
            loadMeta();

            if (isEdit.value) {
                loadUser();
            }
        }
    );
</script>

<template>
    <dialog class="modal" :class="{ 'modal-open': modelValue }">
        <div class="modal-box max-w-2xl overflow-y-auto">
            <button
                type="button"
                class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3"
                aria-label="Tutup"
                :disabled="submitting"
                @click="close"
            >
                ✕
            </button>

            <h3 class="text-lg font-semibold">{{ isEdit ? 'Edit Pengguna' : 'Tambah Pengguna' }}</h3>

            <div v-if="loading" class="flex justify-center py-12">
                <span class="loading loading-spinner loading-lg text-primary" />
            </div>

            <form v-else @submit.prevent="submit" class="mt-4 space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <label class="form-control w-full sm:col-span-2">
                        <div class="label">
                            <span class="label-text">Nama Lengkap</span>
                        </div>
                        <input
                            v-model="form.name"
                            type="text"
                            class="input input-bordered w-full"
                            :class="{ 'input-error': fieldErrors('name').length }"
                            required
                        />
                        <div v-if="fieldErrors('name').length" class="label">
                            <span class="label-text-alt text-error">{{ fieldErrors('name')[0] }}</span>
                        </div>
                    </label>

                    <label class="form-control w-full sm:col-span-2">
                        <div class="label">
                            <span class="label-text">Email</span>
                        </div>
                        <input
                            v-model="form.email"
                            type="email"
                            class="input input-bordered w-full"
                            :class="{ 'input-error': fieldErrors('email').length }"
                            required
                        />
                        <div v-if="fieldErrors('email').length" class="label">
                            <span class="label-text-alt text-error">{{ fieldErrors('email')[0] }}</span>
                        </div>
                    </label>

                    <label class="form-control w-full">
                        <div class="label">
                            <span class="label-text">{{ isEdit ? 'Password Baru (opsional)' : 'Password' }}</span>
                        </div>
                        <input
                            v-model="form.password"
                            type="password"
                            class="input input-bordered w-full"
                            :class="{ 'input-error': fieldErrors('password').length }"
                            autocomplete="new-password"
                            :required="!isEdit"
                            placeholder="Minimal 8 karakter"
                        />
                        <div v-if="fieldErrors('password').length" class="label">
                            <span class="label-text-alt text-error">{{ fieldErrors('password')[0] }}</span>
                        </div>
                    </label>

                    <div class="form-control w-full">
                        <div class="label">
                            <span class="label-text">Status</span>
                        </div>
                        <div class="flex h-12 items-center justify-between rounded-box border border-base-300 px-3">
                            <span class="text-sm">Akun aktif</span>
                            <input v-model="form.is_active" type="checkbox" class="toggle toggle-success" />
                        </div>
                        <div v-if="fieldErrors('is_active').length" class="label">
                            <span class="label-text-alt text-error">{{ fieldErrors('is_active')[0] }}</span>
                        </div>
                    </div>

                    <div class="form-control w-full sm:col-span-2">
                        <div class="label">
                            <span class="label-text">Role</span>
                        </div>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                            <label
                                v-for="role in roles"
                                :key="role.name"
                                class="label cursor-pointer justify-start gap-2 border border-base-300 rounded-box px-3"
                            >
                                <input
                                    type="checkbox"
                                    class="checkbox checkbox-sm"
                                    :checked="form.roles.includes(role.name)"
                                    @change="toggleArrayItem(form.roles, role.name)"
                                />
                                <span class="label-text text-sm">{{ role.name }}</span>
                            </label>
                        </div>
                        <div v-if="fieldErrors('roles').length" class="label">
                            <span class="label-text-alt text-error">{{ fieldErrors('roles')[0] }}</span>
                        </div>
                    </div>

                    <div class="form-control w-full sm:col-span-2">
                        <div class="label">
                            <span class="label-text">Hak Akses Langsung (opsional)</span>
                        </div>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                            <label
                                v-for="permission in permissions"
                                :key="permission.name"
                                class="label cursor-pointer justify-start gap-2 border border-base-300 rounded-box px-3"
                            >
                                <input
                                    type="checkbox"
                                    class="checkbox checkbox-sm"
                                    :checked="form.permissions.includes(permission.name)"
                                    @change="toggleArrayItem(form.permissions, permission.name)"
                                />
                                <span class="label-text text-sm font-mono">{{ permission.name }}</span>
                            </label>
                        </div>
                    </div>
                </div>

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
