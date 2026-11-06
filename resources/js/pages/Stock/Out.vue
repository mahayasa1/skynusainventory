<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import StockMovementPage from '@/components/inventory/StockMovementPage.vue';
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

defineProps<{
    movements: Paginated<Movement>;
    items: ItemOption[];
    filters: {
        search: string;
        from: string;
        to: string;
        sort: string;
        direction: 'asc' | 'desc';
    };
    summary: {
        total_quantity: number;
        transactions: number;
        items_received?: number;
        items_issued?: number;
    };
    defaultDate: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Stock Out', href: '/stock-out' }],
    },
});
</script>

<template>
    <Head title="Stock Out" />

    <StockMovementPage
        mode="out"
        title="Stock Out"
        description="Issue stock for operations and track every outgoing movement."
        action="/stock-out"
        :movements="movements"
        :items="items"
        :filters="filters"
        :summary="summary"
        :default-date="defaultDate"
    />
</template>
