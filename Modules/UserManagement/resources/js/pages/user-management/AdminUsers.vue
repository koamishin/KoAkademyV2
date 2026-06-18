<script setup lang="ts">
import { Head, router, useForm, usePage, usePoll } from '@inertiajs/vue3';
import {
    Activity,
    BarChart3,
    CheckCircle2,
    Circle,
    Clock3,
    KeyRound,
    LogOut,
    MailCheck,
    MailQuestion,
    MonitorDot,
    Search,
    ShieldCheck,
    UserPlus,
    UsersRound,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { index, store, update, verifyEmail, unverifyEmail, sendPasswordReset, impersonate, forceLogout } from '@/routes/admin/users';
import type { AppPageProps, BreadcrumbItem } from '@/types';

type Membership = {
    id?: number;
    campusId: number;
    campusName?: string | null;
    campusSlug?: string | null;
    role: string;
    roleLabel?: string;
    active: boolean;
    isDefault: boolean;
};

type ManagedUser = {
    id: number;
    name: string;
    email: string;
    personName?: string | null;
    verified: boolean;
    emailVerifiedAt?: string | null;
    createdAt?: string | null;
    mfaEnabled: boolean;
    online: boolean;
    activeSessions: number;
    lastSeenAt?: string | null;
    ipAddress?: string | null;
    userAgent?: string | null;
    memberships: Membership[];
    sessions: {
        id: string;
        ipAddress?: string | null;
        userAgent?: string | null;
        lastSeenAt?: string | null;
        online: boolean;
    }[];
    can: {
        manage: boolean;
        impersonate: boolean;
    };
};

type PaginatedUsers = {
    data: ManagedUser[];
    links: { url: string | null; label: string; active: boolean }[];
    meta?: Record<string, unknown>;
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
};

const props = defineProps<{
    users: PaginatedUsers;
    filters: Record<string, string | null>;
    analytics: {
        cards: Record<string, number>;
        roleBreakdown: { role: string; label: string; count: number }[];
    };
    onlineUsers: {
        id: number;
        name: string;
        email: string;
        roles: string[];
        activeSessions: number;
        lastSeenAt?: string | null;
        ipAddress?: string | null;
        userAgent?: string | null;
    }[];
    recentActivity: {
        id: number;
        description: string;
        event?: string | null;
        createdAt?: string | null;
        subjectType?: string | null;
        causerId?: number | null;
    }[];
    campuses: { id: number; name: string; code: string; slug: string }[];
    roles: { value: string; label: string }[];
    can: {
        create: boolean;
        manage: boolean;
        viewGlobal: boolean;
        manageableRoles: string[];
        manageableCampusIds: number[];
    };
}>();

const page = usePage<AppPageProps>();
const campusSlug = computed(() => page.props.currentCampus!.slug);
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Users' }];

const filterForm = ref({
    search: props.filters.search ?? '',
    role: props.filters.role ?? '',
    campus: props.filters.campus ?? '',
    verified: props.filters.verified ?? '',
    online: props.filters.online ?? '',
    mfa: props.filters.mfa ?? '',
    age: props.filters.age ?? '',
});

const selectedUser = ref<ManagedUser | null>(null);
const createOpen = ref(false);
const confirming = ref<{ action: string; user: ManagedUser } | null>(null);

const createForm = useForm({
    name: '',
    email: '',
    email_verified: true,
    memberships: [
        {
            campus_id: props.can.manageableCampusIds[0] ?? page.props.currentCampus!.id,
            role: props.can.manageableRoles.includes('student') ? 'student' : (props.can.manageableRoles[0] ?? 'student'),
            active: true,
            is_default: true,
        },
    ],
});

const editForm = useForm({
    name: '',
    email: '',
    email_verified: false,
    memberships: [] as {
        campus_id: number;
        role: string;
        active: boolean;
        is_default: boolean;
    }[],
});

usePoll(5000, {
    only: ['analytics', 'onlineUsers', 'recentActivity'],
    preserveState: true,
    preserveScroll: true,
});

watch(selectedUser, (user) => {
    if (!user) {
        return;
    }

    const editableMemberships = user.memberships
        .filter((membership) => props.can.manageableCampusIds.includes(membership.campusId))
        .map((membership) => ({
            campus_id: membership.campusId,
            role: membership.role,
            active: membership.active,
            is_default: membership.isDefault,
        }));

    editForm.defaults({
        name: user.name,
        email: user.email,
        email_verified: user.verified,
        memberships:
            editableMemberships.length > 0
                ? editableMemberships
                : [
                      {
                          campus_id: props.can.manageableCampusIds[0] ?? page.props.currentCampus!.id,
                          role: props.can.manageableRoles.includes('student') ? 'student' : (props.can.manageableRoles[0] ?? 'student'),
                          active: true,
                          is_default: true,
                      },
                  ],
    });

    editForm.reset();
});

const analyticsCards = computed(() => [
    { label: 'Total users', value: props.analytics.cards.totalUsers, icon: UsersRound },
    { label: 'Online now', value: props.analytics.cards.onlineNow, icon: MonitorDot },
    { label: 'Students', value: props.analytics.cards.students, icon: UsersRound },
    { label: 'Teachers', value: props.analytics.cards.teachers, icon: ShieldCheck },
    { label: 'Admins', value: props.analytics.cards.admins, icon: KeyRound },
    { label: 'Unverified', value: props.analytics.cards.unverified, icon: MailQuestion },
    { label: 'MFA enabled', value: props.analytics.cards.mfaEnabled, icon: CheckCircle2 },
    { label: 'Inactive 30d', value: props.analytics.cards.inactive30Days, icon: Clock3 },
]);

function applyFilters() {
    const query = Object.fromEntries(Object.entries(filterForm.value).filter(([, value]) => value !== ''));

    router.get(index.url({ campus: campusSlug.value }, { query }), {}, { preserveState: true, replace: true });
}

function resetFilters() {
    filterForm.value = {
        search: '',
        role: '',
        campus: props.can.viewGlobal ? '' : campusSlug.value,
        verified: '',
        online: '',
        mfa: '',
        age: '',
    };
    applyFilters();
}

function submitCreate() {
    createForm.post(store.url({ campus: campusSlug.value }), {
        preserveScroll: true,
        onSuccess: () => {
            createOpen.value = false;
            createForm.reset();
        },
    });
}

function submitUpdate() {
    if (!selectedUser.value) {
        return;
    }

    editForm.patch(update.url({ campus: campusSlug.value, user: selectedUser.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            selectedUser.value = null;
        },
    });
}

function runConfirmedAction() {
    if (!confirming.value) {
        return;
    }

    const user = confirming.value.user;
    const options = {
        preserveScroll: true,
        onFinish: () => {
            confirming.value = null;
        },
    };

    if (confirming.value.action === 'verify') {
        router.post(verifyEmail.url({ campus: campusSlug.value, user: user.id }), {}, options);
    }

    if (confirming.value.action === 'unverify') {
        router.post(unverifyEmail.url({ campus: campusSlug.value, user: user.id }), {}, options);
    }

    if (confirming.value.action === 'reset') {
        router.post(sendPasswordReset.url({ campus: campusSlug.value, user: user.id }), {}, options);
    }

    if (confirming.value.action === 'impersonate') {
        router.post(impersonate.url({ campus: campusSlug.value, user: user.id }), {}, options);
    }

    if (confirming.value.action === 'logout') {
        router.delete(forceLogout.url({ campus: campusSlug.value, user: user.id }), options);
    }
}

function roleLabel(role: string) {
    return props.roles.find((item) => item.value === role)?.label ?? role;
}

function campusName(id: number) {
    return props.campuses.find((campus) => campus.id === id)?.name ?? 'Campus';
}

function formatDate(value?: string | null) {
    return value ? new Date(value).toLocaleString() : 'Never';
}

function confirmationTitle() {
    const action = confirming.value?.action;

    return {
        verify: 'Verify email',
        unverify: 'Unverify email',
        reset: 'Send password reset',
        impersonate: 'Start impersonation',
        logout: 'Force logout',
    }[action ?? ''] ?? 'Confirm action';
}
</script>

<template>
    <Head title="User Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-[1500px] flex-col gap-5 p-4 sm:p-6 lg:p-8">
            <header class="flex flex-col gap-4 border-b pb-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-medium text-primary">Admin operations</p>
                    <h1 class="mt-1 text-2xl font-semibold tracking-tight">User management</h1>
                    <p class="mt-1 text-sm text-muted-foreground">{{ users.total }} accounts in view</p>
                </div>
                <Button v-if="can.create" class="gap-2" @click="createOpen = true">
                    <UserPlus class="size-4" />
                    New user
                </Button>
            </header>

            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <article v-for="card in analyticsCards" :key="card.label" class="rounded-lg border bg-card p-4">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm text-muted-foreground">{{ card.label }}</p>
                        <component :is="card.icon" class="size-4 text-primary" />
                    </div>
                    <p class="mt-3 text-2xl font-semibold">{{ card.value ?? 0 }}</p>
                </article>
            </section>

            <section class="grid gap-5 xl:grid-cols-[1fr_360px]">
                <div class="min-w-0 rounded-lg border bg-card">
                    <div class="grid gap-3 border-b p-4 lg:grid-cols-[minmax(220px,1fr)_repeat(6,minmax(130px,160px))_auto]">
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input v-model="filterForm.search" class="pl-9" placeholder="Search users" @keyup.enter="applyFilters" />
                        </div>
                        <select v-model="filterForm.role" class="h-9 rounded-md border bg-background px-3 text-sm">
                            <option value="">Any role</option>
                            <option v-for="role in roles" :key="role.value" :value="role.value">{{ role.label }}</option>
                        </select>
                        <select v-model="filterForm.campus" class="h-9 rounded-md border bg-background px-3 text-sm">
                            <option v-if="can.viewGlobal" value="">All campuses</option>
                            <option v-for="campus in campuses" :key="campus.id" :value="campus.slug">{{ campus.name }}</option>
                        </select>
                        <select v-model="filterForm.verified" class="h-9 rounded-md border bg-background px-3 text-sm">
                            <option value="">Verification</option>
                            <option value="verified">Verified</option>
                            <option value="unverified">Unverified</option>
                        </select>
                        <select v-model="filterForm.online" class="h-9 rounded-md border bg-background px-3 text-sm">
                            <option value="">Presence</option>
                            <option value="online">Online</option>
                            <option value="offline">Offline</option>
                        </select>
                        <select v-model="filterForm.mfa" class="h-9 rounded-md border bg-background px-3 text-sm">
                            <option value="">MFA</option>
                            <option value="enabled">Enabled</option>
                            <option value="disabled">Disabled</option>
                        </select>
                        <select v-model="filterForm.age" class="h-9 rounded-md border bg-background px-3 text-sm">
                            <option value="">Created</option>
                            <option value="7">Last 7 days</option>
                            <option value="30">Last 30 days</option>
                            <option value="older">Older</option>
                        </select>
                        <div class="flex gap-2">
                            <Button size="sm" @click="applyFilters">Apply</Button>
                            <Button size="sm" variant="outline" @click="resetFilters">Reset</Button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[980px] text-sm">
                            <thead class="border-b bg-muted/40 text-left text-xs uppercase text-muted-foreground">
                                <tr>
                                    <th class="px-4 py-3 font-medium">Account</th>
                                    <th class="px-4 py-3 font-medium">Roles</th>
                                    <th class="px-4 py-3 font-medium">Status</th>
                                    <th class="px-4 py-3 font-medium">Sessions</th>
                                    <th class="px-4 py-3 font-medium">Last seen</th>
                                    <th class="px-4 py-3 text-right font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="user in users.data" :key="user.id" class="border-b last:border-0 hover:bg-muted/30">
                                    <td class="px-4 py-3">
                                        <button class="text-left" @click="selectedUser = user">
                                            <span class="font-medium">{{ user.name }}</span>
                                            <span class="block text-xs text-muted-foreground">{{ user.email }}</span>
                                        </button>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap gap-1">
                                            <Badge v-for="membership in user.memberships" :key="membership.id ?? membership.campusId" variant="secondary">
                                                {{ membership.roleLabel ?? roleLabel(membership.role) }}
                                            </Badge>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap gap-1">
                                            <Badge :variant="user.verified ? 'default' : 'secondary'">
                                                {{ user.verified ? 'Verified' : 'Unverified' }}
                                            </Badge>
                                            <Badge :variant="user.mfaEnabled ? 'default' : 'outline'">
                                                {{ user.mfaEnabled ? 'MFA' : 'No MFA' }}
                                            </Badge>
                                            <Badge :variant="user.online ? 'default' : 'outline'" class="gap-1">
                                                <Circle class="size-2" :class="user.online ? 'fill-emerald-500 text-emerald-500' : 'fill-muted text-muted'" />
                                                {{ user.online ? 'Online' : 'Offline' }}
                                            </Badge>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">{{ user.activeSessions }}</td>
                                    <td class="px-4 py-3 text-muted-foreground">{{ formatDate(user.lastSeenAt) }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-2">
                                            <Button size="sm" variant="outline" @click="selectedUser = user">Open</Button>
                                            <Button
                                                v-if="user.can.impersonate"
                                                size="sm"
                                                variant="outline"
                                                @click="confirming = { action: 'impersonate', user }"
                                            >
                                                Impersonate
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="users.data.length === 0">
                                    <td colspan="6" class="px-4 py-10 text-center text-muted-foreground">No users match the current filters.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex flex-col gap-3 border-t p-4 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm text-muted-foreground">Showing {{ users.from ?? 0 }}-{{ users.to ?? 0 }} of {{ users.total }}</p>
                        <div class="flex flex-wrap gap-2">
                            <Button
                                v-for="link in users.links"
                                :key="link.label"
                                size="sm"
                                :variant="link.active ? 'default' : 'outline'"
                                :disabled="!link.url"
                                @click="link.url && router.get(link.url, {}, { preserveScroll: true, preserveState: true })"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>

                <aside class="grid gap-5">
                    <section class="rounded-lg border bg-card p-4">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <h2 class="font-semibold">Online now</h2>
                            <MonitorDot class="size-4 text-primary" />
                        </div>
                        <div class="grid gap-3">
                            <article v-for="user in onlineUsers" :key="user.id" class="rounded-md bg-muted/40 p-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-medium">{{ user.name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ user.userAgent ?? 'Active session' }}</p>
                                    </div>
                                    <Badge>{{ user.activeSessions }}</Badge>
                                </div>
                            </article>
                            <p v-if="onlineUsers.length === 0" class="text-sm text-muted-foreground">No active sessions.</p>
                        </div>
                    </section>

                    <section class="rounded-lg border bg-card p-4">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <h2 class="font-semibold">Role mix</h2>
                            <BarChart3 class="size-4 text-primary" />
                        </div>
                        <div class="grid gap-3">
                            <div v-for="role in analytics.roleBreakdown.filter((item) => item.count > 0)" :key="role.role">
                                <div class="flex justify-between text-sm">
                                    <span>{{ role.label }}</span>
                                    <span class="font-medium">{{ role.count }}</span>
                                </div>
                                <div class="mt-1 h-2 overflow-hidden rounded-full bg-muted">
                                    <div class="h-full bg-primary" :style="{ width: `${Math.min(100, (role.count / Math.max(1, analytics.cards.totalUsers)) * 100)}%` }" />
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-lg border bg-card p-4">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <h2 class="font-semibold">Recent activity</h2>
                            <Activity class="size-4 text-primary" />
                        </div>
                        <div class="grid gap-3">
                            <article v-for="item in recentActivity" :key="item.id" class="rounded-md bg-muted/40 p-3">
                                <p class="text-sm font-medium capitalize">{{ item.description }}</p>
                                <p class="text-xs text-muted-foreground">{{ formatDate(item.createdAt) }}</p>
                            </article>
                            <p v-if="recentActivity.length === 0" class="text-sm text-muted-foreground">No account activity yet.</p>
                        </div>
                    </section>
                </aside>
            </section>
        </div>

        <div v-if="selectedUser" class="fixed inset-0 z-50 bg-black/30" @click.self="selectedUser = null">
            <aside class="ml-auto flex h-full w-full max-w-xl flex-col overflow-y-auto border-l bg-background shadow-xl">
                <header class="border-b p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-semibold">{{ selectedUser.name }}</h2>
                            <p class="text-sm text-muted-foreground">{{ selectedUser.email }}</p>
                        </div>
                        <Button variant="outline" size="sm" @click="selectedUser = null">Close</Button>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <Badge :variant="selectedUser.online ? 'default' : 'outline'">{{ selectedUser.online ? 'Online' : 'Offline' }}</Badge>
                        <Badge :variant="selectedUser.verified ? 'default' : 'secondary'">{{ selectedUser.verified ? 'Verified' : 'Unverified' }}</Badge>
                        <Badge :variant="selectedUser.mfaEnabled ? 'default' : 'outline'">{{ selectedUser.mfaEnabled ? 'MFA enabled' : 'MFA disabled' }}</Badge>
                    </div>
                </header>

                <form class="grid gap-5 p-5" @submit.prevent="submitUpdate">
                    <section class="grid gap-3">
                        <h3 class="font-semibold">Account</h3>
                        <Input v-model="editForm.name" :disabled="!selectedUser.can.manage" />
                        <Input v-model="editForm.email" type="email" :disabled="!selectedUser.can.manage" />
                        <label class="flex items-center gap-2 text-sm">
                            <input v-model="editForm.email_verified" type="checkbox" :disabled="!selectedUser.can.manage" />
                            Email verified
                        </label>
                    </section>

                    <section class="grid gap-3">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="font-semibold">Campus access</h3>
                            <Button
                                v-if="selectedUser.can.manage && can.viewGlobal"
                                type="button"
                                size="sm"
                                variant="outline"
                                @click="
                                    editForm.memberships.push({
                                        campus_id: can.manageableCampusIds[0] ?? page.props.currentCampus!.id,
                                        role: can.manageableRoles[0] ?? 'student',
                                        active: true,
                                        is_default: false,
                                    })
                                "
                            >
                                Add
                            </Button>
                        </div>
                        <article v-for="(membership, index) in editForm.memberships" :key="`${membership.campus_id}-${index}`" class="grid gap-2 rounded-md border p-3">
                            <select v-model.number="membership.campus_id" class="h-9 rounded-md border bg-background px-3 text-sm" :disabled="!selectedUser.can.manage">
                                <option v-for="campus in campuses.filter((item) => can.manageableCampusIds.includes(item.id))" :key="campus.id" :value="campus.id">
                                    {{ campus.name }}
                                </option>
                            </select>
                            <select v-model="membership.role" class="h-9 rounded-md border bg-background px-3 text-sm" :disabled="!selectedUser.can.manage">
                                <option v-for="role in roles.filter((item) => can.manageableRoles.includes(item.value))" :key="role.value" :value="role.value">
                                    {{ role.label }}
                                </option>
                            </select>
                            <div class="flex flex-wrap gap-4 text-sm">
                                <label class="flex items-center gap-2">
                                    <input v-model="membership.active" type="checkbox" :disabled="!selectedUser.can.manage" />
                                    Active
                                </label>
                                <label class="flex items-center gap-2">
                                    <input v-model="membership.is_default" type="checkbox" :disabled="!selectedUser.can.manage" />
                                    Default
                                </label>
                            </div>
                        </article>
                        <div v-if="selectedUser.memberships.some((membership) => !can.manageableCampusIds.includes(membership.campusId))" class="grid gap-2">
                            <article
                                v-for="membership in selectedUser.memberships.filter((item) => !can.manageableCampusIds.includes(item.campusId))"
                                :key="membership.id"
                                class="rounded-md bg-muted/40 p-3 text-sm"
                            >
                                {{ membership.campusName }} · {{ membership.roleLabel ?? roleLabel(membership.role) }}
                            </article>
                        </div>
                    </section>

                    <section class="grid gap-3">
                        <h3 class="font-semibold">Sessions</h3>
                        <article v-for="session in selectedUser.sessions" :key="session.id" class="rounded-md bg-muted/40 p-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-medium">{{ session.userAgent }}</p>
                                    <p class="text-xs text-muted-foreground">{{ session.ipAddress }} · {{ formatDate(session.lastSeenAt) }}</p>
                                </div>
                                <Badge :variant="session.online ? 'default' : 'outline'">{{ session.online ? 'Online' : 'Stale' }}</Badge>
                            </div>
                        </article>
                        <p v-if="selectedUser.sessions.length === 0" class="text-sm text-muted-foreground">No sessions recorded.</p>
                    </section>

                    <footer class="grid gap-3 border-t pt-5">
                        <Button v-if="selectedUser.can.manage" type="submit" :disabled="editForm.processing">Save changes</Button>
                        <div class="grid grid-cols-2 gap-2">
                            <Button
                                v-if="selectedUser.can.manage"
                                type="button"
                                variant="outline"
                                class="gap-2"
                                @click="confirming = { action: selectedUser.verified ? 'unverify' : 'verify', user: selectedUser }"
                            >
                                <MailCheck class="size-4" />
                                {{ selectedUser.verified ? 'Unverify' : 'Verify' }}
                            </Button>
                            <Button v-if="selectedUser.can.manage" type="button" variant="outline" class="gap-2" @click="confirming = { action: 'reset', user: selectedUser }">
                                <KeyRound class="size-4" />
                                Reset
                            </Button>
                            <Button v-if="selectedUser.can.impersonate" type="button" variant="outline" class="gap-2" @click="confirming = { action: 'impersonate', user: selectedUser }">
                                <ShieldCheck class="size-4" />
                                Impersonate
                            </Button>
                            <Button v-if="selectedUser.can.manage" type="button" variant="outline" class="gap-2" @click="confirming = { action: 'logout', user: selectedUser }">
                                <LogOut class="size-4" />
                                Log out
                            </Button>
                        </div>
                    </footer>
                </form>
            </aside>
        </div>

        <div v-if="createOpen" class="fixed inset-0 z-50 grid place-items-center bg-black/30 p-4" @click.self="createOpen = false">
            <form class="w-full max-w-lg rounded-lg border bg-background p-5 shadow-xl" @submit.prevent="submitCreate">
                <h2 class="text-lg font-semibold">New user</h2>
                <div class="mt-5 grid gap-3">
                    <Input v-model="createForm.name" placeholder="Full name" />
                    <Input v-model="createForm.email" type="email" placeholder="Email address" />
                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="createForm.email_verified" type="checkbox" />
                        Email verified
                    </label>
                    <article v-for="(membership, index) in createForm.memberships" :key="index" class="grid gap-2 rounded-md border p-3">
                        <select v-model.number="membership.campus_id" class="h-9 rounded-md border bg-background px-3 text-sm">
                            <option v-for="campus in campuses.filter((item) => can.manageableCampusIds.includes(item.id))" :key="campus.id" :value="campus.id">
                                {{ campus.name }}
                            </option>
                        </select>
                        <select v-model="membership.role" class="h-9 rounded-md border bg-background px-3 text-sm">
                            <option v-for="role in roles.filter((item) => can.manageableRoles.includes(item.value))" :key="role.value" :value="role.value">
                                {{ role.label }}
                            </option>
                        </select>
                    </article>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <Button type="button" variant="outline" @click="createOpen = false">Cancel</Button>
                    <Button type="submit" :disabled="createForm.processing">Create</Button>
                </div>
            </form>
        </div>

        <div v-if="confirming" class="fixed inset-0 z-[60] grid place-items-center bg-black/30 p-4" @click.self="confirming = null">
            <div class="w-full max-w-md rounded-lg border bg-background p-5 shadow-xl">
                <h2 class="text-lg font-semibold">{{ confirmationTitle() }}</h2>
                <p class="mt-2 text-sm text-muted-foreground">{{ confirming.user.name }} · {{ confirming.user.email }}</p>
                <div class="mt-5 flex justify-end gap-2">
                    <Button variant="outline" @click="confirming = null">Cancel</Button>
                    <Button @click="runConfirmedAction">Confirm</Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
