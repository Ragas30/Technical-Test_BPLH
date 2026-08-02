<script setup>
    import { computed } from 'vue';

    const props = defineProps({
        meta: {
            type: Object,
            required: true,
        },
        itemLabel: {
            type: String,
            default: 'data',
        },
    });

    const emit = defineEmits(['page-change']);

    const pages = computed(() => {
        const { current_page: current = 1, last_page: last = 1 } = props.meta;

        if (last <= 7) {
            return Array.from({ length: last }, (_, i) => i + 1);
        }

        const set = new Set([1, 2, current - 1, current, current + 1, last - 1, last]);
        const sorted = [...set].filter((page) => page >= 1 && page <= last).sort((a, b) => a - b);

        const items = [];
        let prev = 0;
        for (const page of sorted) {
            if (page - prev > 1) {
                items.push('…');
            }
            items.push(page);
            prev = page;
        }

        return items;
    });

    function go(page) {
        if (!page || page === props.meta.current_page) return;
        emit('page-change', page);
    }
</script>

<template>
    <div class="mt-4 flex flex-col items-center gap-3">
        <p class="text-sm text-base-content/60">
            Menampilkan {{ meta.from ?? 0 }}-{{ meta.to ?? 0 }} dari {{ meta.total ?? 0 }} {{ itemLabel }}
        </p>

        <div v-if="meta.last_page > 1" class="join">
            <button
                type="button"
                class="btn btn-sm join-item"
                :disabled="!meta.prev_page_url || meta.current_page <= 1"
                @click="go(meta.current_page - 1)"
            >
                «
            </button>

            <template v-for="(page, index) in pages" :key="`${page}-${index}`">
                <button
                    v-if="page !== '…'"
                    type="button"
                    class="btn btn-sm join-item"
                    :class="{ 'btn-active btn-primary': page === meta.current_page }"
                    @click="go(page)"
                >
                    {{ page }}
                </button>
                <span v-else class="btn btn-sm join-item btn-disabled">{{ page }}</span>
            </template>

            <button
                type="button"
                class="btn btn-sm join-item"
                :disabled="!meta.next_page_url || meta.current_page >= meta.last_page"
                @click="go(meta.current_page + 1)"
            >
                »
            </button>
        </div>
    </div>
</template>
