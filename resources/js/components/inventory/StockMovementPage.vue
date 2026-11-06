<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import {
    ArrowDownToLine,
    ArrowUpDown,
    ArrowUpFromLine,
    CalendarDays,
    ClipboardList,
    Package,
    RotateCcw,
    Search,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
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

type ItemOption = {
    id: number;
    name: string;
    code: string;
    unit: string;
    stock: number;
};

type Movement = {
    id: number;
    quantity: number;
    note: string | null;
    moved_at: string;
    item: { id: number; name: string; code: string; unit: string } | null;
    user: { id: number; name: string } | null;
};

type Filters = {
    search: string;
    from: string;
    to: string;
    sort: string;
    direction: 'asc' | 'desc';
};

const props = defineProps<{
    mode: 'in' | 'out';
    title: string;
    description: string;
    action: string;
    movements: Paginated<Movement>;
    items: ItemOption[];
    filters: Filters;
    summary: {
        total_quantity: number;
        transactions: number;
        items_received?: number;
        items_issued?: number;
    };
    defaultDate: string;
}>();

const isIn = computed(() => props.mode === 'in');
const itemsAffected = computed(
    () => props.summary.items_received ?? props.summary.items_issued ?? 0,
);

const search = ref(props.filters.search);
const from = ref(props.filters.from);
const to = ref(props.filters.to);
const sort = ref(props.filters.sort);
const direction = ref<'asc' | 'desc'>(props.filters.direction);

const form = useForm({
    item_id: '' as string | number,
    quantity: 1 as string | number,
    moved_at: props.defaultDate,
    note: '',
});

const selectedItem = computed(() =>
    props.items.find((item) => String(item.id) === String(form.item_id)),
);

function applyFilters(): void {
    router.get(
        props.action,
        {
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

function submit(): void {
    form.post(props.action, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('item_id', 'quantity', 'note');
            form.moved_at = props.defaultDate;
        },
    });
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
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ title }}
                </h1>
                <p class="text-sm text-muted-foreground">{{ description }}</p>
            </div>
            <div
                :class="[
                    'flex items-center gap-2 rounded-full px-3 py-1 text-sm font-medium',
                    isIn
                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400'
                        : 'bg-orange-100 text-orange-700 dark:bg-orange-500/15 dark:text-orange-400',
                ]"
            >
                <ArrowDownToLine v-if="isIn" class="size-4" />
                <ArrowUpFromLine v-else class="size-4" />
                {{ isIn ? 'Incoming stock' : 'Outgoing stock' }}
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <StatCard
                :title="isIn ? 'Quantity received' : 'Quantity issued'"
                :value="summary.total_quantity.toLocaleString()"
                :icon="isIn ? ArrowDownToLine : ArrowUpFromLine"
                :tone="isIn ? 'emerald' : 'amber'"
            />
            <StatCard
                title="Transactions"
                :value="summary.transactions"
                :icon="ClipboardList"
            />
            <StatCard
                title="Items affected"
                :value="itemsAffected"
                :icon="Package"
                tone="sky"
            />
        </div>

        <div class="grid gap-4 xl:grid-cols-[380px_1fr]">
            <Card class="gap-0 self-start py-0">
                <CardHeader class="border-b px-4 py-3">
                    <CardTitle class="flex items-center gap-2">
                        <component
                            :is="isIn ? ArrowDownToLine : ArrowUpFromLine"
                            class="size-4"
                        />
                        Record {{ isIn ? 'stock in' : 'stock out' }}
                    </CardTitle>
                    <CardDescription>
                        {{
                            isIn
                                ? 'Add received quantity to an item.'
                                : 'Issue quantity from available stock.'
                        }}
                    </CardDescription>
                </CardHeader>
                <CardContent class="p-4">
                    <form class="grid gap-4" @submit.prevent="submit">
                        <div class="grid gap-1.5">
                            <Label for="movement-item">Item</Label>
                            <select
                                id="movement-item"
                                v-model="form.item_id"
                                :class="selectClass"
                                required
                            >
                                <option value="" disabled>
                                    Select an item
                                </option>
                                <option
                                    v-for="item in items"
                                    :key="item.id"
                                    :value="item.id"
                                >
                                    {{ item.name }} ({{ item.code }})
                                </option>
                            </select>
                            <InputError :message="form.errors.item_id" />
                            <p
                                v-if="selectedItem"
                                class="text-xs text-muted-foreground"
                            >
                                Available stock:
                                <span class="font-medium text-foreground"
                                    >{{ selectedItem.stock }}
                                    {{ selectedItem.unit }}</span
                                >
                            </p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-1.5">
                                <Label for="movement-quantity">Quantity</Label>
                                <Input
                                    id="movement-quantity"
                                    v-model="form.quantity"
                                    type="number"
                                    min="1"
                                    :max="
                                        !isIn && selectedItem
                                            ? selectedItem.stock
                                            : undefined
                                    "
                                    required
                                />
                                <InputError :message="form.errors.quantity" />
                            </div>
                            <div class="grid gap-1.5">
                                <Label for="movement-date">Date</Label>
                                <Input
                                    id="movement-date"
                                    v-model="form.moved_at"
                                    type="date"
                                    required
                                />
                                <InputError :message="form.errors.moved_at" />
                            </div>
                        </div>

                        <div class="grid gap-1.5">
                            <Label for="movement-note">Note</Label>
                            <textarea
                                id="movement-note"
                                v-model="form.note"
                                rows="3"
                                :class="selectClass"
                                class="h-auto py-2"
                                :placeholder="
                                    isIn
                                        ? 'Supplier, invoice or delivery note'
                                        : 'Destination, requester or reason'
                                "
                            />
                            <InputError :message="form.errors.note" />
                        </div>

                        <Button
                            type="submit"
                            :disabled="form.processing"
                            :class="
                                isIn
                                    ? 'bg-emerald-600 hover:bg-emerald-600/90'
                                    : 'bg-orange-600 hover:bg-orange-600/90'
                            "
                        >
                            <component
                                :is="isIn ? ArrowDownToLine : ArrowUpFromLine"
                                class="size-4"
                            />
                            {{ isIn ? 'Record stock in' : 'Record stock out' }}
                        </Button>
                    </form>
                </CardContent>
            </Card>

            <Card class="gap-0 py-0">
                <CardHeader class="border-b px-4 py-3">
                    <CardTitle>History</CardTitle>
                    <CardDescription>
                        Filter and sort recorded {{ isIn ? 'in' : 'out' }}
                        movements.
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
                    <div class="w-40">
                        <Label class="mb-1 block text-xs text-muted-foreground"
                            >From</Label
                        >
                        <Input v-model="from" type="date" />
                    </div>
                    <div class="w-40">
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
                                    <th class="px-4 py-3 font-medium">
                                        <button
                                            class="inline-flex items-center gap-1 hover:text-foreground"
                                            @click="sortBy('quantity')"
                                        >
                                            Qty
                                            <ArrowUpDown class="size-3" />
                                        </button>
                                    </th>
                                    <th class="px-4 py-3 font-medium">
                                        Recorded by
                                    </th>
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
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span
                                            :class="[
                                                'font-semibold',
                                                isIn
                                                    ? 'text-emerald-600 dark:text-emerald-400'
                                                    : 'text-orange-600 dark:text-orange-400',
                                            ]"
                                        >
                                            {{ isIn ? '+' : '-'
                                            }}{{ movement.quantity }}
                                        </span>
                                        <span
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ movement.item?.unit }}
                                        </span>
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
                                        colspan="5"
                                        class="px-4 py-10 text-center text-sm text-muted-foreground"
                                    >
                                        No {{ isIn ? 'stock in' : 'stock out' }}
                                        records found.
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
    </div>
</template>
