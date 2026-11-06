<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    status: 'out' | 'low' | 'available';
    label: string;
}>();

const classes = computed(() => {
    switch (props.status) {
        case 'out':
            return 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-400';
        case 'low':
            return 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400';
        default:
            return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400';
    }
});
</script>

<template>
    <span
        :class="[
            'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap',
            classes,
        ]"
    >
        {{ label }}
    </span>
</template>
