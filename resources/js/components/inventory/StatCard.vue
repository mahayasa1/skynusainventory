<script setup lang="ts">
import type { Component } from 'vue';
import { computed } from 'vue';
import { Card, CardContent } from '@/components/ui/card';

type Tone = 'default' | 'emerald' | 'amber' | 'red' | 'sky';

const props = withDefaults(
    defineProps<{
        title: string;
        value: number | string;
        hint?: string;
        icon?: Component;
        tone?: Tone;
    }>(),
    {
        tone: 'default',
    },
);

const toneClasses: Record<Tone, string> = {
    default: 'bg-muted text-foreground',
    emerald:
        'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
    amber:
        'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
    red: 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-400',
    sky: 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-400',
};

const iconClass = computed(() => toneClasses[props.tone]);
</script>

<template>
    <Card class="gap-0 py-0">
        <CardContent class="flex items-start justify-between gap-3 p-5">
            <div class="space-y-1">
                <p class="text-sm font-medium text-muted-foreground">
                    {{ title }}
                </p>
                <p class="text-2xl font-semibold tracking-tight">{{ value }}</p>
                <p v-if="hint" class="text-xs text-muted-foreground">
                    {{ hint }}
                </p>
            </div>
            <div
                v-if="icon"
                :class="[
                    'flex size-10 shrink-0 items-center justify-center rounded-lg',
                    iconClass,
                ]"
            >
                <component :is="icon" class="size-5" />
            </div>
        </CardContent>
    </Card>
</template>
