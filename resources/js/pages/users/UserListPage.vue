<script setup>
    import { onMounted, reactive, ref, watch } from 'vue';
    import AppLayout from '../../layouts/AppLayout.vue';
    import ConfirmModal from '../../components/ConfirmModal.vue';
    import UserFormModal from '../../components/UserFormModal.vue';
    import { userService } from '../../services/users';
    import { useToastStore } from '../../stores/toast';
    import { useInfiniteScroll } from '../../composables/useInfiniteScroll';
    import { ROLE_OPTIONS, roleLabel } from '../../constants/roles';
    import { formatDate, initials } from '../../utils/format';

    const toast = useToastStore();

    const query = reactive({
        search: '',
        role: '',
        is_active: '',
        sort_by: 'created_at',
        sort_dir: 'desc',
        page: 1,
        with_trashed: false,
    });

    const users = ref([]);
    const meta = ref(null);
    const loading = ref(false);
    const deletingId = ref(null);
    const restoringId = ref(null);
    const formModalOpen = ref(false);
    const editingUserId = ref(null);
    const confirmModalOpen = ref(false);
    const confirmModalRef = ref(null);
    const confirmAction = ref(null);

    async function fetchUsers(append = false) {
        if (!append) {
            loading.value = true;
        }

        const params = {
            search: query.search || undefined,
            role: query.role || undefined,
            is_active: query.is_active === '' ? undefined : query.is_active,
            sort_by: query.sort_by,
            sort_dir: query.sort_dir,
            page: query.page,
            per_page: 10,
            with_trashed: query.with_trashed ? 1 : undefined,
        };

        try {
            const { data } = await userService.list(params);
            users.value = append ? [...users.value, ...data.data] : data.data;
            meta.value = data.meta;
        } catch {
            toast.error('Gagal memuat data pengguna.');
            if (append && query.page > 1) {
                query.page -= 1;
            }
        } finally {
            loading.value = false;
        }
    }

    async function appendUsers() {
        if (!meta.value?.next_page_url) return;

        query.page += 1;
        await fetchUsers(true);
    }

    const { loadingMore, sentinel, loadMore } = useInfiniteScroll(appendUsers);

    function sortBy(field) {
        if (query.sort_by === field) {
            query.sort_dir = query.sort_dir === 'asc' ? 'desc' : 'asc';
        } else {
            query.sort_by = field;
            query.sort_dir = 'asc';
        }
        query.page = 1;
    }

    function sortIcon(field) {
        if (query.sort_by !== field) return '';
        return query.sort_dir === 'asc' ? '↑' : '↓';
    }

    function openRemove(user) {
        confirmAction.value = {
            title: 'Hapus Pengguna',
            message: `Hapus pengguna "${user.name}"? Tindakan ini tidak dapat dibatalkan.`,
            confirmLabel: 'Hapus',
            confirmClass: 'btn-error',
            run: () => removeUser(user),
        };
        confirmModalOpen.value = true;
    }

    function openRestore(user) {
        confirmAction.value = {
            title: 'Pulihkan Pengguna',
            message: `Pulihkan pengguna "${user.name}"?`,
            confirmLabel: 'Pulihkan',
            confirmClass: 'btn-success',
            run: () => restoreUser(user),
        };
        confirmModalOpen.value = true;
    }

    async function handleConfirm() {
        const action = confirmAction.value;
        if (!action) return;

        confirmModalRef.value?.setSubmitting(true);

        try {
            await action.run();
            confirmModalOpen.value = false;
        } catch (error) {
            confirmModalRef.value?.setSubmitting(false);
            toast.error(
                error?.response?.data?.errors?.id?.[0] ??
                    error?.response?.data?.errors?.name?.[0] ??
                    error?.response?.data?.message ??
                    'Gagal melakukan aksi.'
            );
        }
    }

    async function removeUser(user) {
        deletingId.value = user.id;

        try {
            await userService.destroy(user.id);
            toast.success('Pengguna berhasil dihapus.');
            if (users.value.length === 1 && query.page > 1) {
                query.page -= 1;
            }
            await fetchUsers();
        } finally {
            deletingId.value = null;
        }
    }

    async function restoreUser(user) {
        restoringId.value = user.id;

        try {
            await userService.restore(user.id);
            toast.success('Pengguna berhasil dipulihkan.');
            await fetchUsers();
        } finally {
            restoringId.value = null;
        }
    }

    function toggleTrashed() {
        query.with_trashed = !query.with_trashed;
        query.page = 1;
    }

    function openCreate() {
        editingUserId.value = null;
        formModalOpen.value = true;
    }

    function openEdit(userId) {
        editingUserId.value = userId;
        formModalOpen.value = true;
    }

    function onSaved() {
        fetchUsers();
    }

    let searchTimer = null;
    watch(
        () => query.search,
        () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                query.page = 1;
                fetchUsers();
            }, 300);
        }
    );

    watch(
        () => [query.role, query.is_active, query.sort_by, query.sort_dir, query.with_trashed],
        () => {
            query.page = 1;
            fetchUsers();
        }
    );

    onMounted(fetchUsers);
</script>

<template>
    <AppLayout>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-2xl font-semibold">Manajemen Pengguna</h2>
            <button type="button" class="btn btn-primary" @click="openCreate">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="2"
                    stroke="currentColor"
                    class="h-5 w-5"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Pengguna
            </button>
        </div>

        <div class="mt-6 flex flex-wrap items-end gap-3">
            <label class="form-control w-full max-w-xs">
                <div class="label">
                    <span class="label-text">Cari</span>
                </div>
                <input
                    v-model="query.search"
                    type="search"
                    placeholder="Nama atau email..."
                    class="input input-bordered input-sm w-full"
                />
            </label>

            <label class="form-control w-40">
                <div class="label">
                    <span class="label-text">Role</span>
                </div>
                <select v-model="query.role" class="select select-bordered select-sm w-full">
                    <option value="">Semua</option>
                    <option v-for="role in ROLE_OPTIONS" :key="role.value" :value="role.value">{{ role.label }}</option>
                </select>
            </label>

            <label class="form-control w-40">
                <div class="label">
                    <span class="label-text">Status</span>
                </div>
                <select v-model="query.is_active" class="select select-bordered select-sm w-full">
                    <option value="">Semua</option>
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </select>
            </label>

            <label class="label cursor-pointer gap-2 pb-1">
                <input
                    v-model="query.with_trashed"
                    type="checkbox"
                    class="checkbox checkbox-sm"
                    @change="toggleTrashed"
                />
                <span class="label-text">Termasuk terhapus</span>
            </label>
        </div>

        <div class="mt-4 overflow-x-auto rounded-box border border-base-300 bg-base-100 shadow-sm">
            <table class="table">
                <thead>
                    <tr class="text-sm">
                        <th>Pengguna</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th class="cursor-pointer select-none" @click="sortBy('created_at')">
                            Dibuat {{ sortIcon('created_at') }}
                        </th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="loading">
                        <td colspan="5" class="py-16 text-center">
                            <span class="loading loading-spinner loading-lg text-primary" />
                        </td>
                    </tr>
                    <tr v-else-if="users.length === 0">
                        <td colspan="5" class="py-12 text-center text-sm text-base-content/50">
                            Tidak ada data pengguna.
                        </td>
                    </tr>
                    <tr v-for="user in users" :key="user.id">
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="avatar placeholder">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/15 text-sm font-semibold text-primary"
                                    >
                                        {{ initials(user.name) }}
                                    </div>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 font-medium">
                                        {{ user.name }}
                                        <span v-if="user.deleted_at" class="badge badge-error badge-sm">Terhapus</span>
                                    </div>
                                    <div class="text-xs text-base-content/50">{{ user.email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="flex flex-wrap gap-1">
                                <span v-for="role in user.roles" :key="role" class="badge badge-outline badge-sm">{{
                                    roleLabel(role)
                                }}</span>
                                <span v-if="user.roles.length === 0" class="text-xs text-base-content/50">-</span>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-sm" :class="user.is_active ? 'badge-success' : 'badge-ghost'">
                                {{ user.is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="text-sm">{{ formatDate(user.created_at) }}</td>
                        <td>
                            <div class="flex justify-end gap-2">
                                <RouterLink
                                    :to="{ name: 'users.show', params: { id: user.id } }"
                                    class="btn btn-ghost btn-xs"
                                    >Detail</RouterLink
                                >
                                <button type="button" class="btn btn-ghost btn-xs" @click="openEdit(user.id)">
                                    Edit
                                </button>
                                <button
                                    v-if="user.deleted_at"
                                    class="btn btn-ghost btn-xs text-success"
                                    :disabled="restoringId === user.id"
                                    @click="openRestore(user)"
                                >
                                    Pulihkan
                                </button>
                                <button
                                    v-else
                                    class="btn btn-ghost btn-xs text-error"
                                    :disabled="deletingId === user.id"
                                    @click="openRemove(user)"
                                >
                                    Hapus
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="meta" ref="sentinel" class="mt-4 flex flex-col items-center gap-3">
            <p class="text-sm text-base-content/60">
                Menampilkan {{ meta.from ?? 0 }}-{{ meta.to ?? 0 }} dari {{ meta.total }} pengguna
            </p>
            <button
                v-if="meta.next_page_url"
                type="button"
                class="btn btn-sm btn-outline"
                :disabled="loadingMore"
                @click="loadMore"
            >
                <span v-if="loadingMore" class="loading loading-spinner loading-sm" />
                {{ loadingMore ? 'Memuat...' : 'Muat Lebih Banyak' }}
            </button>
            <p v-else-if="meta.total > 0" class="text-sm text-base-content/50">Semua data sudah dimuat.</p>
        </div>

        <UserFormModal v-model="formModalOpen" :user-id="editingUserId" @saved="onSaved" />

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
