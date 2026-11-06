<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowUpDown,
    Pencil,
    Plus,
    RotateCcw,
    Search,
    ShieldCheck,
    Trash2,
    UserRound,
    UsersRound,
} from '@lucide/vue';
import Swal from 'sweetalert2';
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

type ManagedUser = {
    id: number;
    name: string;
    email: string;
    role: 'admin' | 'staff';
    email_verified_at: string | null;
    created_at: string | null;
};

type Filters = {
    search: string;
    role: string;
    sort: string;
    direction: 'asc' | 'desc';
};

const props = defineProps<{
    users: Paginated<ManagedUser>;
    filters: Filters;
    summary: {
        total: number;
        admins: number;
        staff: number;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Users', href: '/users' }],
    },
});

const page = usePage();
const currentUserId = computed(() => page.props.auth.user.id);

const search = ref(props.filters.search);
const role = ref(props.filters.role);
const sort = ref(props.filters.sort);
const direction = ref<'asc' | 'desc'>(props.filters.direction);

const showDialog = ref(false);
const editing = ref<ManagedUser | null>(null);

const form = useForm({
    name: '',
    email: '',
    role: 'staff',
    password: '',
});

function applyFilters(): void {
    router.get(
        '/users',
        {
            search: search.value || undefined,
            role: role.value || undefined,
            sort: sort.value,
            direction: direction.value,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function resetFilters(): void {
    search.value = '';
    role.value = '';
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
    form.role = 'staff';
    showDialog.value = true;
}

function openEdit(user: ManagedUser): void {
    editing.value = user;
    form.clearErrors();
    form.name = user.name;
    form.email = user.email;
    form.role = user.role;
    form.password = '';
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
        form.put(`/users/${editing.value.id}`, options);
    } else {
        form.post('/users', options);
    }
}

async function destroy(user: ManagedUser): Promise<void> {
    const result = await Swal.fire({
        icon: 'warning',
        title: 'Delete this user?',
        text: `${user.name} (${user.email}) will lose access to the inventory system.`,
        showCancelButton: true,
        confirmButtonText: 'Delete',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
    });

    if (result.isConfirmed) {
        router.delete(`/users/${user.id}`, { preserveScroll: true });
    }
}

const selectClass =
    'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50';
</script>

<template>
    <Head title="Users" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Users</h1>
                <p class="text-sm text-muted-foreground">
                    Manage administrators and staff accounts. Only admins can
                    access this page.
                </p>
            </div>
            <Button size="sm" @click="openCreate">
                <Plus class="size-4" />
                Add user
            </Button>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <StatCard
                title="Total users"
                :value="summary.total"
                :icon="UsersRound"
                tone="sky"
            />
            <StatCard
                title="Administrators"
                :value="summary.admins"
                :icon="ShieldCheck"
                tone="emerald"
            />
            <StatCard
                title="Staff"
                :value="summary.staff"
                :icon="UserRound"
            />
        </div>

        <Card class="gap-0 py-0">
            <CardHeader class="border-b px-4 py-3">
                <CardTitle>User list</CardTitle>
                <CardDescription>
                    Filter by role or search by name and email.
                </CardDescription>
            </CardHeader>

            <div class="flex flex-wrap items-end gap-2 border-b px-4 py-3">
                <div class="relative min-w-56 flex-1">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="search"
                        class="pl-8"
                        placeholder="Search name or email"
                        @keyup.enter="applyFilters"
                    />
                </div>
                <div class="w-40">
                    <Label class="mb-1 block text-xs text-muted-foreground"
                        >Role</Label
                    >
                    <select
                        v-model="role"
                        :class="selectClass"
                        @change="applyFilters"
                    >
                        <option value="">All roles</option>
                        <option value="admin">Administrator</option>
                        <option value="staff">Staff</option>
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
                                        @click="sortBy('name')"
                                    >
                                        Name
                                        <ArrowUpDown class="size-3" />
                                    </button>
                                </th>
                                <th class="px-4 py-3 font-medium">
                                    <button
                                        class="inline-flex items-center gap-1 hover:text-foreground"
                                        @click="sortBy('email')"
                                    >
                                        Email
                                        <ArrowUpDown class="size-3" />
                                    </button>
                                </th>
                                <th class="px-4 py-3 font-medium">
                                    <button
                                        class="inline-flex items-center gap-1 hover:text-foreground"
                                        @click="sortBy('role')"
                                    >
                                        Role
                                        <ArrowUpDown class="size-3" />
                                    </button>
                                </th>
                                <th class="px-4 py-3 font-medium">Joined</th>
                                <th class="px-4 py-3 text-right font-medium">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="user in users.data"
                                :key="user.id"
                                class="border-b last:border-0 hover:bg-muted/40"
                            >
                                <td class="px-4 py-3">
                                    <p class="font-medium">
                                        {{ user.name }}
                                        <span
                                            v-if="user.id === currentUserId"
                                            class="ml-1 text-xs font-normal text-muted-foreground"
                                            >(you)</span
                                        >
                                    </p>
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">
                                    {{ user.email }}
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium',
                                            user.role === 'admin'
                                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400'
                                                : 'bg-slate-100 text-slate-700 dark:bg-slate-500/15 dark:text-slate-300',
                                        ]"
                                    >
                                        <ShieldCheck
                                            v-if="user.role === 'admin'"
                                            class="size-3"
                                        />
                                        <UserRound v-else class="size-3" />
                                        {{
                                            user.role === 'admin'
                                                ? 'Administrator'
                                                : 'Staff'
                                        }}
                                    </span>
                                </td>
                                <td
                                    class="px-4 py-3 whitespace-nowrap text-muted-foreground"
                                >
                                    {{ user.created_at }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-1">
                                        <Button
                                            size="icon-sm"
                                            variant="ghost"
                                            title="Edit user"
                                            @click="openEdit(user)"
                                        >
                                            <Pencil class="size-4" />
                                        </Button>
                                        <Button
                                            size="icon-sm"
                                            variant="ghost"
                                            class="text-red-600 hover:bg-red-50 hover:text-red-700 disabled:opacity-40 dark:text-red-400 dark:hover:bg-red-500/10"
                                            title="Delete user"
                                            :disabled="
                                                user.id === currentUserId
                                            "
                                            @click="destroy(user)"
                                        >
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="users.data.length === 0">
                                <td
                                    colspan="5"
                                    class="px-4 py-10 text-center text-sm text-muted-foreground"
                                >
                                    No users match your filters.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <Pagination
                    :links="users.links"
                    :from="users.from"
                    :to="users.to"
                    :total="users.total"
                />
            </CardContent>
        </Card>
    </div>

    <Dialog v-model:open="showDialog">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>
                    {{ editing ? 'Edit user' : 'Add user' }}
                </DialogTitle>
                <DialogDescription>
                    {{
                        editing
                            ? 'Update user details. Leave the password blank to keep the current password.'
                            : 'Create an administrator or staff account.'
                    }}
                </DialogDescription>
            </DialogHeader>

            <form class="grid gap-4" @submit.prevent="submit">
                <div class="grid gap-1.5">
                    <Label for="user-name">Name</Label>
                    <Input
                        id="user-name"
                        v-model="form.name"
                        placeholder="Full name"
                        required
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-1.5">
                    <Label for="user-email">Email</Label>
                    <Input
                        id="user-email"
                        v-model="form.email"
                        type="email"
                        placeholder="name@example.com"
                        required
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-1.5">
                        <Label for="user-role">Role</Label>
                        <select
                            id="user-role"
                            v-model="form.role"
                            :class="selectClass"
                        >
                            <option value="staff">Staff</option>
                            <option value="admin">Administrator</option>
                        </select>
                        <InputError :message="form.errors.role" />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="user-password">
                            Password
                            <span
                                v-if="editing"
                                class="font-normal text-muted-foreground"
                                >(optional)</span
                            >
                        </Label>
                        <Input
                            id="user-password"
                            v-model="form.password"
                            type="password"
                            :placeholder="
                                editing ? 'Leave blank to keep current' : 'Min. 8 characters'
                            "
                            :required="!editing"
                        />
                        <InputError :message="form.errors.password" />
                    </div>
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
                        {{ editing ? 'Save changes' : 'Add user' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
