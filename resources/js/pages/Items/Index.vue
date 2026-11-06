<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    ArrowUpDown,
    Boxes,
    Package,
    PackageX,
    Pencil,
    Plus,
    RotateCcw,
    Search,
    Trash2,
    TriangleAlert,
} from '@lucide/vue';
import Swal from 'sweetalert2';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
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
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Paginated } from '@/types';

type Item = {
    id: number;
    code: string;
    name: string;
    type: 'tool' | 'ingredient';
    unit: string;
    stock: number;
    minimum_stock: number;
    location: string | null;
    description: string | null;
    status: 'out' | 'low' | 'available';
    status_label: string;
    created_at: string | null;
};

type Filters = {
    search: string;
    type: string;
    status: string;
    sort: string;
    direction: 'asc' | 'desc';
};

const props = defineProps<{
    items: Paginated<Item>;
    filters: Filters;
    summary: {
        total_items: number;
        total_stock: number;
        out_of_stock: number;
        low_stock: number;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Items', href: '/items' }],
    },
});

const search = ref(props.filters.search);
const type = ref(props.filters.type);
const status = ref(props.filters.status);
const sort = ref(props.filters.sort);
const direction = ref<'asc' | 'desc'>(props.filters.direction);

const showDialog = ref(false);
const editing = ref<Item | null>(null);

const form = useForm({
    code: '',
    name: '',
    type: 'tool',
    unit: 'pcs',
    minimum_stock: 0,
    initial_stock: 0,
    location: '',
    description: '',
});

function applyFilters(): void {
    router.get(
        '/items',
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

function openCreate(): void {
    editing.value = null;
    form.reset();
    form.clearErrors();
    form.type = 'tool';
    form.unit = 'pcs';
    form.minimum_stock = 0;
    form.initial_stock = 0;
    showDialog.value = true;
}

function openEdit(item: Item): void {
    editing.value = item;
    form.clearErrors();
    form.code = item.code;
    form.name = item.name;
    form.type = item.type;
    form.unit = item.unit;
    form.minimum_stock = item.minimum_stock;
    form.initial_stock = 0;
    form.location = item.location ?? '';
    form.description = item.description ?? '';
    showDialog.value = true;
}

function submit(): void {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            showDialog.value = false;
            form.reset();
        },
    };

    if (editing.value) {
        form.put(`/items/${editing.value.id}`, options);
    } else {
        form.post('/items', options);
    }
}

async function destroy(item: Item): Promise<void> {
    const result = await Swal.fire({
        icon: 'warning',
        title: 'Delete this item?',
        text: `${item.name} (${item.code}) and its movement history will be removed.`,
        showCancelButton: true,
        confirmButtonText: 'Delete',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
    });

    if (result.isConfirmed) {
        router.delete(`/items/${item.id}`, { preserveScroll: true });
    }
}

function typeLabel(value: string): string {
    return value === 'tool' ? 'Tool' : 'Ingredient';
}

function typeClass(value: string): string {
    return value === 'tool'
        ? 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-400'
        : 'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-400';
}

const inputClass =
    'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50';
</script>

<template>
    <Head title="Items" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Items</h1>
                <p class="text-sm text-muted-foreground">
                    Manage tools and ingredients, stock thresholds and storage
                    locations.
                </p>
            </div>
            <Button size="sm" @click="openCreate">
                <Plus class="size-4" />
                Add item
            </Button>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard
                title="Items tracked"
                :value="summary.total_items"
                :icon="Package"
            />
            <StatCard
                title="Total stock"
                :value="summary.total_stock.toLocaleString()"
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
                <CardTitle>Item list</CardTitle>
                <CardDescription>
                    Use filters and column headers to find and sort items.
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
                    <select v-model="type" :class="inputClass" @change="applyFilters">
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
                        :class="inputClass"
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
                                <th class="px-4 py-3 font-medium">
                                    <button
                                        class="inline-flex items-center gap-1 hover:text-foreground"
                                        @click="sortBy('minimum_stock')"
                                    >
                                        Min
                                        <ArrowUpDown class="size-3" />
                                    </button>
                                </th>
                                <th class="px-4 py-3 font-medium">Status</th>
                                <th class="px-4 py-3 text-right font-medium">
                                    Actions
                                </th>
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
                                <td class="px-4 py-3">
                                    <StatusBadge
                                        :status="item.status"
                                        :label="item.status_label"
                                    />
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-1">
                                        <Button
                                            size="icon-sm"
                                            variant="ghost"
                                            title="Edit item"
                                            @click="openEdit(item)"
                                        >
                                            <Pencil class="size-4" />
                                        </Button>
                                        <Button
                                            size="icon-sm"
                                            variant="ghost"
                                            class="text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-500/10"
                                            title="Delete item"
                                            @click="destroy(item)"
                                        >
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="items.data.length === 0">
                                <td
                                    colspan="7"
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

    <Dialog v-model:open="showDialog">
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-xl">
            <DialogHeader>
                <DialogTitle>
                    {{ editing ? 'Edit item' : 'Add item' }}
                </DialogTitle>
                <DialogDescription>
                    {{
                        editing
                            ? 'Update the item details. Stock levels are changed through stock in / stock out.'
                            : 'Create a new tool or ingredient. Initial stock is recorded as an incoming movement.'
                    }}
                </DialogDescription>
            </DialogHeader>

            <form class="grid gap-4" @submit.prevent="submit">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-1.5">
                        <Label for="item-code">Code</Label>
                        <Input
                            id="item-code"
                            v-model="form.code"
                            placeholder="TOL-009"
                            required
                        />
                        <InputError :message="form.errors.code" />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="item-name">Name</Label>
                        <Input
                            id="item-name"
                            v-model="form.name"
                            placeholder="Item name"
                            required
                        />
                        <InputError :message="form.errors.name" />
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-1.5">
                        <Label for="item-type">Type</Label>
                        <select
                            id="item-type"
                            v-model="form.type"
                            :class="inputClass"
                        >
                            <option value="tool">Tool</option>
                            <option value="ingredient">Ingredient</option>
                        </select>
                        <InputError :message="form.errors.type" />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="item-unit">Unit</Label>
                        <Input
                            id="item-unit"
                            v-model="form.unit"
                            placeholder="pcs, kg, liter"
                            required
                        />
                        <InputError :message="form.errors.unit" />
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-1.5">
                        <Label for="item-minimum">Minimum stock</Label>
                        <Input
                            id="item-minimum"
                            v-model="form.minimum_stock"
                            type="number"
                            min="0"
                            required
                        />
                        <InputError :message="form.errors.minimum_stock" />
                    </div>
                    <div v-if="!editing" class="grid gap-1.5">
                        <Label for="item-initial">Initial stock</Label>
                        <Input
                            id="item-initial"
                            v-model="form.initial_stock"
                            type="number"
                            min="0"
                        />
                        <InputError :message="form.errors.initial_stock" />
                    </div>
                    <div v-else class="grid gap-1.5">
                        <Label>Current stock</Label>
                        <div
                            class="flex h-9 items-center rounded-md border border-dashed px-3 text-sm text-muted-foreground"
                        >
                            {{ editing.stock }} {{ editing.unit }} (use stock
                            in/out to adjust)
                        </div>
                    </div>
                </div>

                <div class="grid gap-1.5">
                    <Label for="item-location">Location</Label>
                    <Input
                        id="item-location"
                        v-model="form.location"
                        placeholder="Warehouse A"
                    />
                    <InputError :message="form.errors.location" />
                </div>

                <div class="grid gap-1.5">
                    <Label for="item-description">Description</Label>
                    <textarea
                        id="item-description"
                        v-model="form.description"
                        rows="3"
                        :class="inputClass"
                        class="h-auto py-2"
                        placeholder="Optional notes about this item"
                    />
                    <InputError :message="form.errors.description" />
                </div>

                <DialogFooter class="gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="showDialog = false"
                    >
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ editing ? 'Save changes' : 'Add item' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
