<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { computed, onMounted } from 'vue';

const page = usePage();
const alerts = computed(() => page.props.stockAlerts ?? []);

function escapeHtml(value: string): string {
    return value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

onMounted(() => {
    if (alerts.value.length === 0) {
        return;
    }

    const signature = alerts.value
        .map((item) => `${item.id}:${item.stock}`)
        .join('|');

    if (window.sessionStorage.getItem('stock-alert-signature') === signature) {
        return;
    }

    window.sessionStorage.setItem('stock-alert-signature', signature);

    const isDark = document.documentElement.classList.contains('dark');

    const rows = alerts.value
        .slice(0, 8)
        .map((item) => {
            const out = item.stock <= 0;
            const status = out
                ? '<span style="color:#dc2626;font-weight:600;">out of stock</span>'
                : `<span style="color:#d97706;font-weight:600;">${item.stock} ${escapeHtml(item.unit)} left</span>`;

            return `<li style="display:flex;justify-content:space-between;gap:12px;padding:6px 0;border-bottom:1px solid rgba(148,163,184,.25);">
                        <span style="text-align:left;">${escapeHtml(item.name)} <span style="opacity:.55;font-size:12px;">(${escapeHtml(item.code)})</span></span>
                        <span style="white-space:nowrap;font-size:13px;">${status}</span>
                    </li>`;
        })
        .join('');

    const more =
        alerts.value.length > 8
            ? `<p style="margin-top:8px;font-size:12px;opacity:.6;">+${alerts.value.length - 8} more item(s) need attention.</p>`
            : '';

    void Swal.fire({
        icon: 'warning',
        title: 'Stock alert',
        html: `<p style="margin-bottom:8px;">These items are at or below their minimum stock level:</p>
               <ul style="margin:0;padding:0;list-style:none;max-height:260px;overflow:auto;">${rows}</ul>${more}`,
        confirmButtonText: 'View items',
        showCancelButton: true,
        cancelButtonText: 'Dismiss',
        confirmButtonColor: '#0f172a',
        cancelButtonColor: '#64748b',
        background: isDark ? '#0a0a0a' : '#ffffff',
        color: isDark ? '#fafafa' : '#0a0a0a',
    }).then((result) => {
        if (result.isConfirmed) {
            router.visit('/items?status=low');
        }
    });
});
</script>

<template>
    <span class="hidden" aria-hidden="true" />
</template>
