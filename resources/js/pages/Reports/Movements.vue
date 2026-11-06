<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    ArrowDownToLine,
    ArrowLeftRight,
    ArrowUpDown,
    ArrowUpFromLine,
    CalendarDays,
    ClipboardList,
    RotateCcw,
    Search,
} from '@lucide/vue';
import { ref } from 'vue';
import Pagination from '@/components/inventory/Pagination.vue';
import StatCard from '@/components/inventory/StatCard.vue';
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

type Movement = {
    id: number;
    type: 'in' | 'out';
    quantity: number;
    note: string | null;
    moved_at: string;
    item: { id: number; name: string; code: string; unit: string } | null;
    user: { id: number; name: string } | null;
};

type Filters = {
    type: string;
    item_id: string;
    search: string;
    from: string;
    to: string;
    sort: string;
    direction: 'asc' | 'desc';
};

const props = defineProps<{
    movements: Paginated<Movement>;
    items: { id: number; name: string; code: string }[];
    filters: Filters;
    summary: {
        total_in: number;
        total_out: number;
        net: number;
        transactions: number;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Reports', href: '/reports/movements' },
            { title: 'In / Out Report', href: '/reports/movements' },
        ],
    },
});

const type = ref(props.filters.type);
const itemId = ref(props.filters.item_id);
const search = ref(props.filters.search);
const from = ref(props.filters.from);
const to = ref(props.filters.to);
const sort = ref(props.filters.sort);
const direction = ref<'asc' | 'desc'>(props.filters.direction);

function applyFilters(): void {
    router.get(
        '/reports/movements',
        {
            type: type.value || undefined,
            item_id: itemId.value || undefined,
            search: search.value || undefined,
            from: from.value || undefined,
            to: to.value || undefined,
            sort: sort.value,
            direction: direction.value,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function resetFilters(): void {
    type.value = '';
    itemId.value = '';
    search.value = '';
    from.value = '';
    to.value = '';
    sort.value = 'moved_at';
    direction.value = 'desc';
    applyFilters();
}

function sortBy(column: string): void {
    if (sort.value === column) {
        direction.value = direction.value === 'asc' ? 'desc' : 'asc';
    } else {
        sort.value = column;
        direction.value = 'desc';
    }

    applyFilters();
}

function formatDateTime(value: string): string {
    return new Date(value.replace(' ', 'T')).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

const selectClass =
    'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50';
</script>

<template>
    <Head title="In / Out Report" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">
                In / Out report
            </h1>
            <p class="text-sm text-muted-foreground">
                Review every stock movement with date, item and type filters.
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard
                title="Total stock in"
                :value="summary.total_in.toLocaleString()"
                :icon="ArrowDownToLine"
                tone="emerald"
            />
            <StatCard
                title="Total stock out"
                :value="summary.total_out.toLocaleString()"
                :icon="ArrowUpFromLine"
                tone="amber"
            />
            <StatCard
                title="Net movement"
                :value="summary.net.toLocaleString()"
                hint="Stock in minus stock out"
                :icon="ArrowLeftRight"
                :tone="summary.net < 0 ? 'red' : 'sky'"
            />
            <StatCard
                title="Transactions"
                :value="summary.transactions.toLocaleString()"
                :icon="ClipboardList"
            />
        </div>

        <Card class="gap-0 py-0">
            <CardHeader class="border-b px-4 py-3">
                <CardTitle>Movement report</CardTitle>
                <CardDescription>
                    {{ summary.transactions }} movement(s) match the current
                    filters.
                </CardDescription>
            </CardHeader>

            <div class="flex flex-wrap items-end gap-2 border-b px-4 py-3">
                <div class="relative min-w-48 flex-1">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="search"
                        class="pl-8"
                        placeholder="Search item"
                        @keyup.enter="applyFilters"
                    />
                </div>
                <div class="w-36">
                    <Label class="mb-1 block text-xs text-muted-foreground"
                        >Type</Label
                    >
                    <select
                        v-model="type"
                        :class="selectClass"
                        @change="applyFilters"
                    >
                        <option value="">All types</option>
                        <option value="in">Stock in</option>
                        <option value="out">Stock out</option>
                    </select>
                </div>
                <div class="w-44">
                    <Label class="mb-1 block text-xs text-muted-foreground"
                        >Item</Label
                    >
                    <select
                        v-model="itemId"
                        :class="selectClass"
                        @change="applyFilters"
                    >
                        <option value="">All items</option>
                        <option
                            v-for="item in items"
                            :key="item.id"
                            :value="item.id"
                        >
                            {{ item.name }}
                        </option>
                    </select>
                </div>
                <div class="w-36">
                    <Label class="mb-1 block text-xs text-muted-foreground"
                        >From</Label
                    >
                    <Input v-model="from" type="date" />
                </div>
                <div class="w-36">
                    <Label class="mb-1 block text-xs text-muted-foreground"
                        >To</Label
                    >
                    <Input v-model="to" type="date" />
                </div>
                <Button variant="outline" @click="applyFilters">
                    <CalendarDays class="size-4" />
                    Apply
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
                                        @click="sortBy('moved_at')"
                                    >
                                        Date
                                        <ArrowUpDown class="size-3" />
                                    </button>
                                </th>
                                <th class="px-4 py-3 font-medium">
                                    <button
                                        class="inline-flex items-center gap-1 hover:text-foreground"
                                        @click="sortBy('item')"
                                    >
                                        Item
                                        <ArrowUpDown class="size-3" />
                                    </button>
                                </th>
                                <th class="px-4 py-3 font-medium">Type</th>
                                <th class="px-4 py-3 font-medium">
                                    <button
                                        class="inline-flex items-center gap-1 hover:text-foreground"
                                        @click="sortBy('quantity')"
                                    >
                                        Qty
                                        <ArrowUpDown class="size-3" />
                                    </button>
                                </th>
                                <th class="px-4 py-3 font-medium">Recorded by</th>
                                <th class="px-4 py-3 font-medium">Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="movement in movements.data"
                                :key="movement.id"
                                class="border-b last:border-0 hover:bg-muted/40"
                            >
                                <td
                                    class="px-4 py-3 whitespace-nowrap text-muted-foreground"
                                >
                                    {{ formatDateTime(movement.moved_at) }}
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-medium">
                                        {{ movement.item?.name }}
                                    </p>
                                    <p
                                        class="font-mono text-xs text-muted-foreground"
                                    >
                                        {{ movement.item?.code }}
                                    </p>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium',
                                            movement.type === 'in'
                                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400'
                                                : 'bg-orange-100 text-orange-700 dark:bg-orange-500/15 dark:text-orange-400',
                                        ]"
                                    >
                                        <ArrowDownToLine
                                            v-if="movement.type === 'in'"
                                            class="size-3"
                                        />
                                        <ArrowUpFromLine
                                            v-else
                                            class="size-3"
                                        />
                                        {{ movement.type === 'in' ? 'In' : 'Out' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="font-semibold">
                                        {{ movement.quantity }}
                                    </span>
                                    <span class="text-xs text-muted-foreground">
                                        {{ movement.item?.unit }}</span
                                    >
                                </td>
                                <td
                                    class="px-4 py-3 whitespace-nowrap text-muted-foreground"
                                >
                                    {{ movement.user?.name ?? 'System' }}
                                </td>
                                <td
                                    class="max-w-56 truncate px-4 py-3 text-muted-foreground"
                                >
                                    {{ movement.note || '—' }}
                                </td>
                            </tr>
                            <tr v-if="movements.data.length === 0">
                                <td
                                    colspan="6"
                                    class="px-4 py-10 text-center text-sm text-muted-foreground"
                                >
                                    No movements match your filters.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <Pagination
                    :links="movements.links"
                    :from="movements.from"
                    :to="movements.to"
                    :total="movements.total"
                />
            </CardContent>
        </Card>
    </div>
</template>
