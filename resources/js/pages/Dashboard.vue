<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowDownToLine,
    ArrowRight,
    ArrowUpFromLine,
    Boxes,
    Package,
    PackageCheck,
    PackageX,
    TrendingUp,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import StatCard from '@/components/inventory/StatCard.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';

type ItemAlert = {
    id: number;
    code: string;
    name: string;
    type: string;
    unit: string;
    stock: number;
    minimum_stock: number;
};

type TrendPoint = {
    date: string;
    label: string;
    in: number;
    out: number;
};

type Movement = {
    id: number;
    type: 'in' | 'out';
    quantity: number;
    note: string | null;
    moved_at: string;
    item: { id: number; name: string; code: string; unit: string } | null;
    user: { id: number; name: string } | null;
};

type Stats = {
    total_stock: number;
    total_items: number;
    out_of_stock: number;
    low_stock: number;
    total_users: number;
    admin_users: number;
    movements_this_month: number;
};

const props = defineProps<{
    stats: Stats;
    trend: TrendPoint[];
    recentMovements: Movement[];
    outOfStockItems: ItemAlert[];
    lowStockItems: ItemAlert[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

const maxTrend = computed(() =>
    Math.max(1, ...props.trend.flatMap((point) => [point.in, point.out])),
);

function barHeight(value: number): string {
    return `${Math.max(4, (value / maxTrend.value) * 100)}%`;
}

function formatDateTime(value: string): string {
    return new Date(value.replace(' ', 'T')).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Inventory dashboard
                </h1>
                <p class="text-sm text-muted-foreground">
                    A quick overview of stock levels, activity and your team.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <Button as-child size="sm">
                    <Link href="/stock-in">
                        <ArrowDownToLine class="size-4" />
                        Stock in
                    </Link>
                </Button>
                <Button as-child size="sm" variant="outline">
                    <Link href="/stock-out">
                        <ArrowUpFromLine class="size-4" />
                        Stock out
                    </Link>
                </Button>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard
                title="Total stock"
                :value="stats.total_stock.toLocaleString()"
                hint="Units across all items"
                :icon="Boxes"
                tone="sky"
            />
            <StatCard
                title="Items tracked"
                :value="stats.total_items"
                :hint="`${stats.low_stock} low stock item(s)`"
                :icon="Package"
                tone="default"
            />
            <StatCard
                title="Out of stock"
                :value="stats.out_of_stock"
                hint="Needs restocking"
                :icon="PackageX"
                tone="red"
            />
            <StatCard
                title="Total users"
                :value="stats.total_users"
                :hint="`${stats.admin_users} administrator(s)`"
                :icon="Users"
                tone="emerald"
            />
        </div>

        <div
            v-if="outOfStockItems.length || lowStockItems.length"
            class="flex flex-wrap items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200"
        >
            <PackageX class="size-5 shrink-0" />
            <p class="flex-1">
                <span class="font-semibold">Stock alert.</span>
                {{ outOfStockItems.length }} out of stock and
                {{ lowStockItems.length }} low stock item(s) need attention.
            </p>
            <Button as-child size="sm" variant="outline">
                <Link href="/items?status=low">Review items</Link>
            </Button>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <Card class="lg:col-span-2">
                <CardHeader
                    class="flex flex-row flex-wrap items-center justify-between gap-2"
                >
                    <div class="space-y-1">
                        <CardTitle class="flex items-center gap-2">
                            <TrendingUp class="size-4" />
                            Stock movement (last 7 days)
                        </CardTitle>
                        <CardDescription>
                            Quantity moved in and out of the warehouse.
                        </CardDescription>
                    </div>
                    <div class="flex items-center gap-4 text-xs">
                        <span class="flex items-center gap-1.5">
                            <span class="size-2.5 rounded-full bg-emerald-500" />
                            In
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="size-2.5 rounded-full bg-orange-500" />
                            Out
                        </span>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="flex h-48 items-end gap-3">
                        <div
                            v-for="point in trend"
                            :key="point.date"
                            class="flex h-full flex-1 flex-col items-center justify-end gap-2"
                        >
                            <div
                                class="flex h-full w-full items-end justify-center gap-1"
                            >
                                <div
                                    class="w-2.5 rounded-t bg-emerald-500/80 transition-all"
                                    :style="{ height: barHeight(point.in) }"
                                    :title="`${point.in} in`"
                                />
                                <div
                                    class="w-2.5 rounded-t bg-orange-500/80 transition-all"
                                    :style="{ height: barHeight(point.out) }"
                                    :title="`${point.out} out`"
                                />
                            </div>
                            <span class="text-xs text-muted-foreground">
                                {{ point.label }}
                            </span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <PackageX class="size-4 text-red-500" />
                        Out of stock
                    </CardTitle>
                    <CardDescription>
                        Items that cannot be issued right now.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div
                        v-if="outOfStockItems.length === 0"
                        class="flex flex-col items-center gap-2 rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"
                    >
                        <PackageCheck class="size-8 text-emerald-500" />
                        All items are stocked.
                    </div>
                    <template v-else>
                        <div
                            v-for="item in outOfStockItems.slice(0, 6)"
                            :key="item.id"
                            class="flex items-center justify-between gap-2 text-sm"
                        >
                            <div class="min-w-0">
                                <p class="truncate font-medium">
                                    {{ item.name }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ item.code }}
                                </p>
                            </div>
                            <span
                                class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium whitespace-nowrap text-red-700 dark:bg-red-500/15 dark:text-red-400"
                            >
                                0 {{ item.unit }}
                            </span>
                        </div>
                        <Button
                            as-child
                            class="w-full"
                            size="sm"
                            variant="outline"
                        >
                            <Link href="/items?status=out">
                                View all out of stock
                                <ArrowRight class="size-4" />
                            </Link>
                        </Button>
                    </template>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader
                class="flex flex-row flex-wrap items-center justify-between gap-2"
            >
                <div class="space-y-1">
                    <CardTitle>Recent stock activity</CardTitle>
                    <CardDescription>
                        {{ stats.movements_this_month }} movement(s) recorded
                        this month.
                    </CardDescription>
                </div>
                <Button as-child size="sm" variant="outline">
                    <Link href="/reports/movements">Full report</Link>
                </Button>
            </CardHeader>
            <CardContent>
                <div
                    v-if="recentMovements.length === 0"
                    class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground"
                >
                    No stock movements recorded yet.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr
                                class="border-b text-left text-xs tracking-wide text-muted-foreground uppercase"
                            >
                                <th class="px-2 py-2 font-medium">Item</th>
                                <th class="px-2 py-2 font-medium">Type</th>
                                <th class="px-2 py-2 font-medium">Qty</th>
                                <th class="px-2 py-2 font-medium">By</th>
                                <th class="px-2 py-2 font-medium">When</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="movement in recentMovements"
                                :key="movement.id"
                                class="border-b last:border-0"
                            >
                                <td class="px-2 py-2">
                                    <p class="font-medium">
                                        {{ movement.item?.name ?? 'Deleted item' }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ movement.note || movement.item?.code }}
                                    </p>
                                </td>
                                <td class="px-2 py-2">
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
                                <td class="px-2 py-2 font-medium">
                                    {{ movement.type === 'in' ? '+' : '-'
                                    }}{{ movement.quantity }}
                                    <span class="text-xs text-muted-foreground">{{
                                        movement.item?.unit
                                    }}</span>
                                </td>
                                <td class="px-2 py-2 text-muted-foreground">
                                    {{ movement.user?.name ?? 'System' }}
                                </td>
                                <td class="px-2 py-2 whitespace-nowrap text-muted-foreground">
                                    {{ formatDateTime(movement.moved_at) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
