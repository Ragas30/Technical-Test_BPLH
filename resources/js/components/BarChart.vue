<script setup>
    import { computed } from 'vue';

    const props = defineProps({
        data: { type: Array, default: () => [] },
    });

    const max = computed(() => Math.max(...props.data.map((item) => item.total), 1));
</script>

<template>
    <div class="flex h-44 items-end gap-2">
        <div v-for="item in data" :key="item.month" class="flex h-full flex-1 flex-col items-center gap-2">
            <span class="text-xs font-medium text-base-content/60">{{ item.total }}</span>
            <div
                class="w-full max-w-10 rounded-full bg-gradient-to-t from-indigo-600 to-violet-400 transition-all"
                :style="{ height: `${Math.max((item.total / max) * 100, 4)}%` }"
                :title="`${item.month}: ${item.total}`"
            />
            <span class="text-xs text-base-content/50">{{ item.month.slice(5) }}</span>
        </div>
    </div>
</template>
