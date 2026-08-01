<script setup>
    import { computed, onMounted, reactive, ref } from 'vue';
    import { useRoute, useRouter } from 'vue-router';
    import AppLayout from '../../layouts/AppLayout.vue';
    import ConfirmModal from '../../components/ConfirmModal.vue';
    import UserFormModal from '../../components/UserFormModal.vue';
    import { userService } from '../../services/users';
    import { roleService } from '../../services/roles';
    import { useToastStore } from '../../stores/toast';
    import { useAuthStore } from '../../stores/auth';
    import { roleLabel } from '../../constants/roles';
    import { formatDate, initials } from '../../utils/format';

    const route = useRoute();
    const router = useRouter();
    const toast = useToastStore();
    const auth = useAuthStore();

    const loading = ref(true);
    const savingRoles = ref(false);
    const savingPermissions = ref(false);
    const editModalOpen = ref(false);
    const user = ref(null);
    const roles = ref([]);
    const permissions = ref([]);

    const confirmModalOpen = ref(false);
    const confirmModalRef = ref(null);
    const confirmAction = ref(null);

    const selectedRoles = reactive([]);
    const selectedPermissions = reactive([]);

    const isSelf = computed(() => user.value?.id === auth.user?.id);

    function toggleArrayItem(array, value) {
        const index = array.indexOf(value);

        if (index === -1) {
            array.push(value);
        } else {
            array.splice(index, 1);
        }
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
        try {
            const { data } = await userService.show(route.params.id);
            user.value = data.data;
            selectedRoles.splice(0, selectedRoles.length, ...data.data.roles);
            selectedPermissions.splice(0, selectedPermissions.length, ...data.data.permissions);
        } catch {
            toast.error('Gagal memuat data pengguna.');
            router.push({ name: 'users.index' });
        } finally {
            loading.value = false;
        }
    }

    async function saveRoles() {
        savingRoles.value = true;

        try {
            const { data } = await userService.assignRoles(route.params.id, [...selectedRoles]);
            user.value = data.data;
            toast.success('Role berhasil diperbarui.');
        } catch (error) {
            toast.error(
                error?.response?.data?.errors?.roles?.[0] ?? error?.response?.data?.message ?? 'Gagal memperbarui role.'
            );
        } finally {
            savingRoles.value = false;
        }
    }

    async function savePermissions() {
        savingPermissions.value = true;

        try {
            const { data } = await userService.assignPermissions(route.params.id, [...selectedPermissions]);
            user.value = data.data;
            toast.success('Permission berhasil diperbarui.');
        } catch (error) {
            toast.error(error?.response?.data?.message ?? 'Gagal memperbarui permission.');
        } finally {
            savingPermissions.value = false;
        }
    }

    async function removeUser() {
        try {
            await userService.destroy(route.params.id);
            toast.success('Pengguna berhasil dihapus.');
            router.push({ name: 'users.index' });
        } catch (error) {
            toast.error(
                error?.response?.data?.errors?.id?.[0] ?? error?.response?.data?.message ?? 'Gagal menghapus pengguna.'
            );
        }
    }

    function openRemove() {
        confirmAction.value = {
            title: 'Hapus Pengguna',
            message: `Hapus pengguna "${user.value?.name}"? Tindakan ini tidak dapat dibatalkan.`,
            confirmLabel: 'Hapus',
            confirmClass: 'btn-error',
            run: removeUser,
        };
        confirmModalOpen.value = true;
    }

    async function handleConfirm() {
        const action = confirmAction.value;
        confirmModalOpen.value = false;
        confirmAction.value = null;

        try {
            await action.run();
        } catch (error) {
            toast.error(error?.response?.data?.message ?? 'Terjadi kesalahan, coba lagi.');
        }
    }

    onMounted(async () => {
        await Promise.all([loadMeta(), loadUser()]);
    });
</script>

<template>
    <AppLayout>
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-semibold">Detail Pengguna</h2>
            <div class="flex gap-2">
                <RouterLink :to="{ name: 'users.index' }" class="btn btn-ghost btn-sm">Kembali</RouterLink>
                <button type="button" class="btn btn-primary btn-sm" @click="editModalOpen = true">Edit</button>
            </div>
        </div>

        <div v-if="loading" class="flex justify-center py-24">
            <span class="loading loading-spinner loading-lg text-primary" />
        </div>

        <div v-else-if="user" class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6">
                <div class="card border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body items-center text-center">
                        <div class="avatar placeholder">
                            <div
                                class="flex h-20 w-20 items-center justify-center rounded-full bg-primary/15 text-2xl font-semibold text-primary"
                            >
                                {{ initials(user.name) }}
                            </div>
                        </div>
                        <h3 class="card-title">{{ user.name }}</h3>
                        <p class="text-sm text-base-content/60">{{ user.email }}</p>

                        <div class="mt-2 flex flex-wrap justify-center gap-1">
                            <span v-for="role in user.roles" :key="role" class="badge badge-outline">{{
                                roleLabel(role)
                            }}</span>
                            <span v-if="user.roles.length === 0" class="text-xs text-base-content/50">Tanpa role</span>
                        </div>

                        <div class="mt-4 flex flex-wrap justify-center gap-2">
                            <span class="badge badge-sm" :class="user.is_active ? 'badge-success' : 'badge-ghost'">
                                {{ user.is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                            <span v-if="user.deleted_at" class="badge badge-error badge-sm">Terhapus</span>
                        </div>
                    </div>
                </div>

                <div class="card border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body">
                        <h3 class="card-title text-base">Informasi</h3>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-base-content/60">ID</dt>
                                <dd class="font-mono">{{ user.id.slice(0, 13) }}…</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-base-content/60">Dibuat</dt>
                                <dd>{{ formatDate(user.created_at) }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-base-content/60">Diperbarui</dt>
                                <dd>{{ formatDate(user.updated_at) }}</dd>
                            </div>
                        </dl>

                        <div class="divider my-2" />

                        <button class="btn btn-outline btn-error btn-sm" :disabled="isSelf" @click="openRemove">
                            Hapus Pengguna
                        </button>
                    </div>
                </div>
            </div>

            <div class="space-y-6 lg:col-span-2">
                <div class="card border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body">
                        <div class="flex items-center justify-between">
                            <h3 class="card-title text-base">Role</h3>
                            <button class="btn btn-primary btn-sm" :disabled="savingRoles" @click="saveRoles">
                                <span v-if="savingRoles" class="loading loading-spinner loading-sm" />
                                Simpan Role
                            </button>
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
                                    :checked="selectedRoles.includes(role.name)"
                                    @change="toggleArrayItem(selectedRoles, role.name)"
                                />
                                <span class="label-text text-sm">{{ roleLabel(role.name) }}</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="card border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="card-title text-base">Permission</h3>
                                <p class="text-xs text-base-content/50">
                                    Hak akses langsung yang ditambahkan secara manual.
                                </p>
                            </div>
                            <button
                                class="btn btn-primary btn-sm"
                                :disabled="savingPermissions"
                                @click="savePermissions"
                            >
                                <span v-if="savingPermissions" class="loading loading-spinner loading-sm" />
                                Simpan Permission
                            </button>
                        </div>

                        <div v-if="permissions.length === 0" class="py-6 text-center text-sm text-base-content/50">
                            Memuat daftar permission...
                        </div>
                        <div v-else class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                            <label
                                v-for="permission in permissions"
                                :key="permission.name"
                                class="label cursor-pointer justify-start gap-2 border border-base-300 rounded-box px-3"
                            >
                                <input
                                    type="checkbox"
                                    class="checkbox checkbox-sm"
                                    :checked="selectedPermissions.includes(permission.name)"
                                    @change="toggleArrayItem(selectedPermissions, permission.name)"
                                />
                                <span class="label-text text-sm font-mono">{{ permission.name }}</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <UserFormModal v-model="editModalOpen" :user-id="route.params.id" @saved="loadUser" />

        <ConfirmModal
            ref="confirmModalRef"
            v-model="confirmModalOpen"
            :title="confirmAction?.title ?? 'Konfirmasi'"
            :message="confirmAction?.message ?? ''"
            :confirm-label="confirmAction?.confirmLabel ?? 'Konfirmasi'"
            :confirm-class="confirmAction?.confirmClass ?? 'btn-primary'"
            @confirm="handleConfirm"
        />
    </AppLayout>
</template>
