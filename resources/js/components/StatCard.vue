<script setup>
    defineProps({
        label: { type: String, required: true },
        value: { type: [String, Number], required: true },
        tone: { type: String, default: 'bg-primary/10 text-primary' },
    });
</script>

<template>
    <div class="flex items-start gap-4 rounded-2xl border border-base-300 bg-base-100 p-5 shadow-sm">
        <div v-if="$slots.figure" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl" :class="tone">
            <slot name="figure" />
        </div>
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-medium text-base-content/60">{{ label }}</p>
            <p class="mt-1 text-2xl font-bold tracking-tight">{{ value }}</p>
            <div class="mt-1 text-xs text-base-content/50">
                <slot />
            </div>
        </div>
    </div>
</template>
