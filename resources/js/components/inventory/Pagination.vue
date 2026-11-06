<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

defineProps<{
    links: PaginationLink[];
    from?: number | null;
    to?: number | null;
    total?: number;
}>();
</script>

<template>
    <div
        class="flex flex-col items-center justify-between gap-3 border-t px-4 py-3 text-sm sm:flex-row"
    >
        <p class="text-muted-foreground">
            <template v-if="total">
                Showing <span class="font-medium">{{ from }}</span> to
                <span class="font-medium">{{ to }}</span> of
                <span class="font-medium">{{ total }}</span> results
            </template>
            <template v-else>No results</template>
        </p>

        <nav v-if="links.length > 3" class="flex flex-wrap items-center gap-1">
            <template v-for="(link, index) in links" :key="index">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    preserve-scroll
                    :class="[
                        'inline-flex h-8 min-w-8 items-center justify-center rounded-md border px-2 text-xs font-medium transition-colors',
                        link.active
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'hover:bg-accent',
                    ]"
                >
                    <span v-html="link.label" />
                </Link>
                <span
                    v-else
                    class="inline-flex h-8 min-w-8 items-center justify-center rounded-md border px-2 text-xs text-muted-foreground opacity-60"
                >
                    <span v-html="link.label" />
                </span>
            </template>
        </nav>
    </div>
</template>
