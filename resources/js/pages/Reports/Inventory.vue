<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    ArrowDownToLine,
    ArrowUpDown,
    ArrowUpFromLine,
    Boxes,
    Package,
    PackageX,
    RotateCcw,
    Search,
    TriangleAlert,
} from '@lucide/vue';
import { ref } from 'vue';
import Pagination from '@/components/inventory/Pagination.vue';
import StatCard from '@/components/inventory/StatCard.vue';
import StatusBadge from '@/components/inventory/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Paginated } from '@/types';

type InventoryItem = {
    id: number;
    code: string;
    name: string;
    type: 'tool' | 'ingredient';
    unit: string;
    stock: number;
    minimum_stock: number;
    location: string | null;
    status: 'out' | 'low' | 'available';
    status_label: string;
    total_in: number;
    total_out: number;
    last_movement_at: string | null;
};

type Filters = {
    search: string;
    type: string;
    status: string;
    sort: string;
    direction: 'asc' | 'desc';
};

const props = defineProps<{
    items: Paginated<InventoryItem>;
    filters: Filters;
    summary: {
        total_items: number;
        total_stock: number;
        out_of_stock: number;
        low_stock: number;
        total_in: number;
        total_out: number;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Reports', href: '/reports/inventory' },
            { title: 'Inventory Report', href: '/reports/inventory' },
        ],
    },
});

const search = ref(props.filters.search);
const type = ref(props.filters.type);
const status = ref(props.filters.status);
const sort = ref(props.filters.sort);
const direction = ref<'asc' | 'desc'>(props.filters.direction);

function applyFilters(): void {
    router.get(
        '/reports/inventory',
        {
            search: search.value || undefined,
            type: type.value || undefined,
            status: status.value || undefined,
            sort: sort.value,
            direction: direction.value,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function resetFilters(): void {
    search.value = '';
    type.value = '';
    status.value = '';
    sort.value = 'name';
    direction.value = 'asc';
    applyFilters();
}

function sortBy(column: string): void {
    if (sort.value === column) {
        direction.value = direction.value === 'asc' ? 'desc' : 'asc';
    } else {
        sort.value = column;
        direction.value = 'asc';
    }

    applyFilters();
}

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value.replace(' ', 'T')).toLocaleDateString(undefined, {
        dateStyle: 'medium',
    });
}

function typeLabel(value: string): string {
    return value === 'tool' ? 'Tool' : 'Ingredient';
}

function typeClass(value: string): string {
    return value === 'tool'
        ? 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-400'
        : 'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-400';
}

const selectClass =
    'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50';
</script>

<template>
    <Head title="Inventory Report" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">
                Inventory report
            </h1>
            <p class="text-sm text-muted-foreground">
                Current stock position for every item, including lifetime in and
                out totals.
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard
                title="Items tracked"
                :value="summary.total_items"
                :icon="Package"
            />
            <StatCard
                title="Total stock on hand"
                :value="summary.total_stock.toLocaleString()"
                :hint="`${summary.total_in.toLocaleString()} in / ${summary.total_out.toLocaleString()} out all time`"
                :icon="Boxes"
                tone="sky"
            />
            <StatCard
                title="Low stock"
                :value="summary.low_stock"
                :icon="TriangleAlert"
                tone="amber"
            />
            <StatCard
                title="Out of stock"
                :value="summary.out_of_stock"
                :icon="PackageX"
                tone="red"
            />
        </div>

        <Card class="gap-0 py-0">
            <CardHeader class="border-b px-4 py-3">
                <CardTitle>Stock on hand</CardTitle>
                <CardDescription>
                    Lifetime incoming and outgoing quantities per item.
                </CardDescription>
            </CardHeader>

            <div class="flex flex-wrap items-end gap-2 border-b px-4 py-3">
                <div class="relative min-w-52 flex-1">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="search"
                        class="pl-8"
                        placeholder="Search name, code or location"
                        @keyup.enter="applyFilters"
                    />
                </div>
                <div class="w-40">
                    <Label class="mb-1 block text-xs text-muted-foreground"
                        >Type</Label
                    >
                    <select
                        v-model="type"
                        :class="selectClass"
                        @change="applyFilters"
                    >
                        <option value="">All types</option>
                        <option value="tool">Tools</option>
                        <option value="ingredient">Ingredients</option>
                    </select>
                </div>
                <div class="w-40">
                    <Label class="mb-1 block text-xs text-muted-foreground"
                        >Status</Label
                    >
                    <select
                        v-model="status"
                        :class="selectClass"
                        @change="applyFilters"
                    >
                        <option value="">All statuses</option>
                        <option value="available">Available</option>
                        <option value="low">Low stock</option>
                        <option value="out">Out of stock</option>
                    </select>
                </div>
                <Button variant="outline" @click="applyFilters">
                    <Search class="size-4" />
                    Search
                </Button>
                <Button variant="ghost" @click="resetFilters">
                    <RotateCcw class="size-4" />
                    Reset
                </Button>
            </div>

            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr
                                class="border-b text-left text-xs tracking-wide text-muted-foreground uppercase"
                            >
                                <th class="px-4 py-3 font-medium">
                                    <button
                                        class="inline-flex items-center gap-1 hover:text-foreground"
                                        @click="sortBy('code')"
                                    >
                                        Code
                                        <ArrowUpDown class="size-3" />
                                    </button>
                                </th>
                                <th class="px-4 py-3 font-medium">
                                    <button
                                        class="inline-flex items-center gap-1 hover:text-foreground"
                                        @click="sortBy('name')"
                                    >
                                        Item
                                        <ArrowUpDown class="size-3" />
                                    </button>
                                </th>
                                <th class="px-4 py-3 font-medium">
                                    <button
                                        class="inline-flex items-center gap-1 hover:text-foreground"
                                        @click="sortBy('type')"
                                    >
                                        Type
                                        <ArrowUpDown class="size-3" />
                                    </button>
                                </th>
                                <th class="px-4 py-3 font-medium">
                                    <button
                                        class="inline-flex items-center gap-1 hover:text-foreground"
                                        @click="sortBy('stock')"
                                    >
                                        Stock
                                        <ArrowUpDown class="size-3" />
                                    </button>
                                </th>
                                <th class="px-4 py-3 font-medium">Min</th>
                                <th class="px-4 py-3 font-medium">In</th>
                                <th class="px-4 py-3 font-medium">Out</th>
                                <th class="px-4 py-3 font-medium">Last move</th>
                                <th class="px-4 py-3 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="item in items.data"
                                :key="item.id"
                                class="border-b last:border-0 hover:bg-muted/40"
                            >
                                <td class="px-4 py-3 font-mono text-xs">
                                    {{ item.code }}
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-medium">{{ item.name }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ item.location || 'No location' }}
                                    </p>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        :class="[
                                            'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                            typeClass(item.type),
                                        ]"
                                    >
                                        {{ typeLabel(item.type) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="font-semibold">{{
                                        item.stock.toLocaleString()
                                    }}</span>
                                    <span class="text-xs text-muted-foreground">
                                        {{ item.unit }}</span
                                    >
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">
                                    {{ item.minimum_stock }}
                                </td>
                                <td
                                    class="px-4 py-3 whitespace-nowrap text-emerald-600 dark:text-emerald-400"
                                >
                                    <span class="inline-flex items-center gap-1">
                                        <ArrowDownToLine class="size-3" />
                                        {{ item.total_in }}
                                    </span>
                                </td>
                                <td
                                    class="px-4 py-3 whitespace-nowrap text-orange-600 dark:text-orange-400"
                                >
                                    <span class="inline-flex items-center gap-1">
                                        <ArrowUpFromLine class="size-3" />
                                        {{ item.total_out }}
                                    </span>
                                </td>
                                <td
                                    class="px-4 py-3 whitespace-nowrap text-muted-foreground"
                                >
                                    {{ formatDate(item.last_movement_at) }}
                                </td>
                                <td class="px-4 py-3">
                                    <StatusBadge
                                        :status="item.status"
                                        :label="item.status_label"
                                    />
                                </td>
                            </tr>
                            <tr v-if="items.data.length === 0">
                                <td
                                    colspan="9"
                                    class="px-4 py-10 text-center text-sm text-muted-foreground"
                                >
                                    No items match your filters.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <Pagination
                    :links="items.links"
                    :from="items.from"
                    :to="items.to"
                    :total="items.total"
                />
            </CardContent>
        </Card>
    </div>
</template>
