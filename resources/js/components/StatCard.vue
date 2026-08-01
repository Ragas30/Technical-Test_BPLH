<script setup>
    defineProps({
        label: { type: String, required: true },
        value: { type: [String, Number], required: true },
        tone: { type: String, default: 'text-primary' },
    });
</script>

<template>
    <div class="stat border border-base-300 bg-base-100 shadow-sm">
        <div class="stat-figure" :class="tone">
            <slot name="figure" />
        </div>
        <div class="stat-title">{{ label }}</div>
        <div class="stat-value">{{ value }}</div>
        <div class="stat-desc">
            <slot />
        </div>
    </div>
</template>
