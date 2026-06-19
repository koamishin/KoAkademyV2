<script setup lang="ts">
import { Head, router, useForm, usePage, usePoll } from '@inertiajs/vue3';
import {
    Activity,
    BarChart3,
    CheckCircle2,
    KeyRound,
    LogOut,
    MailCheck,
    MailQuestion,
    MonitorDot,
    Plus,
    ShieldCheck,
    UserPlus,
    UsersRound,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ScrollArea } from '@/components/ui/scroll-area';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    store,
    update,
    verifyEmail,
    unverifyEmail,
    sendPasswordReset,
    impersonate,
    forceLogout,
} from '@/routes/admin/users';
import type { AppPageProps, BreadcrumbItem } from '@/types';
import UserDataTable from '../../components/UserDataTable.vue';

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

const props = defineProps<{
    users: {
        data: ManagedUser[];
        links: { url: string | null; label: string; active: boolean }[];
        meta?: Record<string, unknown>;
        current_page: number;
        last_page: number;
        from: number | null;
        to: number | null;
        total: number;
    };
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

const selectedUser = ref<ManagedUser | null>(null);
const createDialogOpen = ref(false);
const confirming = ref<{ action: string; user: ManagedUser } | null>(null);

const createForm = useForm({
    name: '',
    email: '',
    email_verified: true,
    memberships: [
        {
            campus_id:
                props.can.manageableCampusIds[0] ??
                page.props.currentCampus!.id,
            role: props.can.manageableRoles.includes('student')
                ? 'student'
                : (props.can.manageableRoles[0] ?? 'student'),
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
        .filter((membership) =>
            props.can.manageableCampusIds.includes(membership.campusId),
        )
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
                          campus_id:
                              props.can.manageableCampusIds[0] ??
                              page.props.currentCampus!.id,
                          role: props.can.manageableRoles.includes('student')
                              ? 'student'
                              : (props.can.manageableRoles[0] ?? 'student'),
                          active: true,
                          is_default: true,
                      },
                  ],
    });

    editForm.reset();
});

const selectedUserVisible = computed({
    get: () => selectedUser.value !== null,
    set: (value) => {
        if (!value) {
            selectedUser.value = null;
        }
    },
});

const analyticsCards = computed(() => [
    {
        label: 'Total users',
        value: props.analytics.cards.totalUsers,
        icon: UsersRound,
    },
    {
        label: 'Online now',
        value: props.analytics.cards.onlineNow,
        icon: MonitorDot,
    },
    {
        label: 'Students',
        value: props.analytics.cards.students,
        icon: UsersRound,
    },
    {
        label: 'Teachers',
        value: props.analytics.cards.teachers,
        icon: ShieldCheck,
    },
    { label: 'Admins', value: props.analytics.cards.admins, icon: KeyRound },
    {
        label: 'Unverified',
        value: props.analytics.cards.unverified,
        icon: MailQuestion,
    },
    {
        label: 'MFA enabled',
        value: props.analytics.cards.mfaEnabled,
        icon: CheckCircle2,
    },
    {
        label: 'Inactive 30d',
        value: props.analytics.cards.inactive30Days,
        icon: Activity,
    },
]);

const visibleRoleBreakdown = computed(() =>
    props.analytics.roleBreakdown.filter((item) => item.count > 0),
);

function submitCreate() {
    createForm.post(store.url({ campus: campusSlug.value }), {
        preserveScroll: true,
        onSuccess: () => {
            createDialogOpen.value = false;
            createForm.reset();
        },
    });
}

function submitUpdate() {
    if (!selectedUser.value) {
        return;
    }

    editForm.patch(
        update.url({ campus: campusSlug.value, user: selectedUser.value.id }),
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedUser.value = null;
            },
        },
    );
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
        router.post(
            verifyEmail.url({ campus: campusSlug.value, user: user.id }),
            {},
            options,
        );
    }

    if (confirming.value.action === 'unverify') {
        router.post(
            unverifyEmail.url({ campus: campusSlug.value, user: user.id }),
            {},
            options,
        );
    }

    if (confirming.value.action === 'reset') {
        router.post(
            sendPasswordReset.url({ campus: campusSlug.value, user: user.id }),
            {},
            options,
        );
    }

    if (confirming.value.action === 'impersonate') {
        router.post(
            impersonate.url({ campus: campusSlug.value, user: user.id }),
            {},
            options,
        );
    }

    if (confirming.value.action === 'logout') {
        router.delete(
            forceLogout.url({ campus: campusSlug.value, user: user.id }),
            options,
        );
    }
}

function addMembership() {
    editForm.memberships.push({
        campus_id:
            props.can.manageableCampusIds[0] ?? page.props.currentCampus!.id,
        role: props.can.manageableRoles[0] ?? 'student',
        active: true,
        is_default: false,
    });
}

function addCreateMembership() {
    createForm.memberships.push({
        campus_id:
            props.can.manageableCampusIds[0] ?? page.props.currentCampus!.id,
        role: props.can.manageableRoles[0] ?? 'student',
        active: true,
        is_default: false,
    });
}

function removeMembership(index: number) {
    editForm.memberships.splice(index, 1);
}

function removeCreateMembership(index: number) {
    createForm.memberships.splice(index, 1);
}

function roleLabel(role: string) {
    return props.roles.find((item) => item.value === role)?.label ?? role;
}

function formatDate(value?: string | null) {
    return value ? new Date(value).toLocaleString() : 'Never';
}

function confirmationTitle() {
    const action = confirming.value?.action;

    return (
        {
            verify: 'Verify email',
            unverify: 'Unverify email',
            reset: 'Send password reset',
            impersonate: 'Start impersonation',
            logout: 'Force logout',
        }[action ?? ''] ?? 'Confirm action'
    );
}

function confirmationDescription() {
    const action = confirming.value?.action;
    const user = confirming.value?.user;

    if (!user) {
        return '';
    }

    return (
        {
            verify: `Mark ${user.name}'s email address as verified?`,
            unverify: `Mark ${user.name}'s email address as unverified?`,
            reset: `Send a password reset link to ${user.email}?`,
            impersonate: `You will be logged in as ${user.name}. Use this only for support or debugging.`,
            logout: `End all active sessions for ${user.name}? They will need to sign in again.`,
        }[action ?? ''] ??
        `Are you sure you want to perform this action on ${user.name}?`
    );
}
</script>

<template>
    <Head title="User Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="mx-auto flex w-full max-w-[1500px] flex-col gap-6 p-4 sm:p-6 lg:p-8"
        >
            <header
                class="flex flex-col gap-4 border-b pb-6 lg:flex-row lg:items-end lg:justify-between"
            >
                <div>
                    <p class="text-sm font-medium text-primary">
                        Admin operations
                    </p>
                    <h1
                        class="mt-1 text-2xl font-semibold tracking-tight sm:text-3xl"
                    >
                        User management
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ users.total }} accounts in view
                    </p>
                </div>

                <Dialog v-model:open="createDialogOpen">
                    <DialogTrigger as-child>
                        <Button v-if="can.create" class="gap-2">
                            <UserPlus class="size-4" />
                            New user
                        </Button>
                    </DialogTrigger>
                    <DialogContent
                        class="max-h-[90vh] overflow-y-auto sm:max-w-lg"
                    >
                        <DialogHeader>
                            <DialogTitle>Create user account</DialogTitle>
                            <DialogDescription
                                >A password setup link will be emailed to the
                                new user automatically.</DialogDescription
                            >
                        </DialogHeader>

                        <form
                            class="grid gap-5 py-4"
                            @submit.prevent="submitCreate"
                        >
                            <div class="grid gap-2">
                                <Label for="create-name">Full name</Label>
                                <Input
                                    id="create-name"
                                    v-model="createForm.name"
                                    placeholder="Full name"
                                    :disabled="createForm.processing"
                                />
                                <p
                                    v-if="createForm.errors.name"
                                    class="text-sm text-destructive"
                                >
                                    {{ createForm.errors.name }}
                                </p>
                            </div>

                            <div class="grid gap-2">
                                <Label for="create-email">Email address</Label>
                                <Input
                                    id="create-email"
                                    v-model="createForm.email"
                                    type="email"
                                    placeholder="Email address"
                                    :disabled="createForm.processing"
                                />
                                <p
                                    v-if="createForm.errors.email"
                                    class="text-sm text-destructive"
                                >
                                    {{ createForm.errors.email }}
                                </p>
                            </div>

                            <div
                                class="flex items-center gap-3 rounded-md border p-3"
                            >
                                <Switch
                                    id="create-verified"
                                    v-model:checked="createForm.email_verified"
                                    :disabled="createForm.processing"
                                />
                                <Label
                                    for="create-verified"
                                    class="cursor-pointer"
                                    >Email verified</Label
                                >
                            </div>

                            <div class="grid gap-3">
                                <div
                                    class="flex items-center justify-between gap-3"
                                >
                                    <Label>Campus access</Label>
                                    <Button
                                        v-if="can.viewGlobal"
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        class="gap-1"
                                        @click="addCreateMembership"
                                    >
                                        <Plus class="size-3.5" />
                                        Add campus
                                    </Button>
                                </div>

                                <div
                                    v-for="(
                                        membership, idx
                                    ) in createForm.memberships"
                                    :key="idx"
                                    class="grid gap-3 rounded-md border p-3"
                                >
                                    <div class="grid gap-2">
                                        <Label :for="`create-campus-${idx}`"
                                            >Campus</Label
                                        >
                                        <Select
                                            v-model="membership.campus_id"
                                            :disabled="createForm.processing"
                                        >
                                            <SelectTrigger
                                                :id="`create-campus-${idx}`"
                                            >
                                                <SelectValue
                                                    placeholder="Select campus"
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem
                                                    v-for="campus in campuses.filter(
                                                        (item) =>
                                                            can.manageableCampusIds.includes(
                                                                item.id,
                                                            ),
                                                    )"
                                                    :key="campus.id"
                                                    :value="campus.id"
                                                >
                                                    {{ campus.name }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div class="grid gap-2">
                                        <Label :for="`create-role-${idx}`"
                                            >Role</Label
                                        >
                                        <Select
                                            v-model="membership.role"
                                            :disabled="createForm.processing"
                                        >
                                            <SelectTrigger
                                                :id="`create-role-${idx}`"
                                            >
                                                <SelectValue
                                                    placeholder="Select role"
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem
                                                    v-for="role in roles.filter(
                                                        (item) =>
                                                            can.manageableRoles.includes(
                                                                item.value,
                                                            ),
                                                    )"
                                                    :key="role.value"
                                                    :value="role.value"
                                                >
                                                    {{ role.label }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div
                                        class="flex flex-wrap items-center justify-between gap-3 pt-1"
                                    >
                                        <div class="flex items-center gap-2">
                                            <Checkbox
                                                :id="`create-active-${idx}`"
                                                v-model:checked="
                                                    membership.active
                                                "
                                                :disabled="
                                                    createForm.processing
                                                "
                                            />
                                            <Label
                                                :for="`create-active-${idx}`"
                                                class="cursor-pointer text-sm"
                                                >Active</Label
                                            >
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <Checkbox
                                                :id="`create-default-${idx}`"
                                                v-model:checked="
                                                    membership.is_default
                                                "
                                                :disabled="
                                                    createForm.processing
                                                "
                                            />
                                            <Label
                                                :for="`create-default-${idx}`"
                                                class="cursor-pointer text-sm"
                                                >Default</Label
                                            >
                                        </div>
                                        <Button
                                            v-if="
                                                createForm.memberships.length >
                                                1
                                            "
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            class="text-destructive hover:text-destructive"
                                            @click="removeCreateMembership(idx)"
                                        >
                                            Remove
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                :disabled="createForm.processing"
                                @click="createDialogOpen = false"
                            >
                                Cancel
                            </Button>
                            <Button
                                :disabled="createForm.processing"
                                @click="submitCreate"
                            >
                                {{
                                    createForm.processing
                                        ? 'Creating...'
                                        : 'Create account'
                                }}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </header>

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <Card v-for="card in analyticsCards" :key="card.label">
                    <CardContent class="flex items-center justify-between p-5">
                        <div>
                            <p class="text-sm text-muted-foreground">
                                {{ card.label }}
                            </p>
                            <p class="mt-1 text-2xl font-semibold">
                                {{ card.value ?? 0 }}
                            </p>
                        </div>
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10"
                        >
                            <component
                                :is="card.icon"
                                class="size-5 text-primary"
                            />
                        </div>
                    </CardContent>
                </Card>
            </section>

            <section class="grid gap-6 xl:grid-cols-[1fr_360px]">
                <UserDataTable
                    :users="users"
                    :filters="filters"
                    :campuses="campuses"
                    :roles="roles"
                    :can="can"
                    @select-user="selectedUser = $event"
                    @confirm-action="confirming = $event"
                />

                <aside class="grid gap-5">
                    <Card>
                        <CardHeader class="pb-3">
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <CardTitle class="text-base"
                                    >Online now</CardTitle
                                >
                                <MonitorDot class="size-4 text-primary" />
                            </div>
                            <CardDescription
                                >Active sessions across visible
                                users</CardDescription
                            >
                        </CardHeader>
                        <CardContent>
                            <ScrollArea class="h-[260px] pr-3">
                                <div class="grid gap-3">
                                    <div
                                        v-for="user in onlineUsers"
                                        :key="user.id"
                                        class="flex items-start justify-between gap-3 rounded-md bg-muted/50 p-3"
                                    >
                                        <div class="min-w-0">
                                            <p
                                                class="truncate text-sm font-medium"
                                            >
                                                {{ user.name }}
                                            </p>
                                            <p
                                                class="truncate text-xs text-muted-foreground"
                                            >
                                                {{
                                                    user.userAgent ??
                                                    'Active session'
                                                }}
                                            </p>
                                        </div>
                                        <Badge
                                            variant="secondary"
                                            class="shrink-0"
                                            >{{ user.activeSessions }}</Badge
                                        >
                                    </div>
                                    <div
                                        v-if="onlineUsers.length === 0"
                                        class="flex flex-col items-center justify-center gap-2 py-8 text-center text-muted-foreground"
                                    >
                                        <MonitorDot class="size-8 opacity-40" />
                                        <p class="text-sm">
                                            No active sessions.
                                        </p>
                                    </div>
                                </div>
                            </ScrollArea>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="pb-3">
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <CardTitle class="text-base"
                                    >Role mix</CardTitle
                                >
                                <BarChart3 class="size-4 text-primary" />
                            </div>
                            <CardDescription
                                >Distribution by assigned role</CardDescription
                            >
                        </CardHeader>
                        <CardContent>
                            <div class="grid gap-4">
                                <div
                                    v-for="role in visibleRoleBreakdown"
                                    :key="role.role"
                                >
                                    <div class="flex justify-between text-sm">
                                        <span>{{ role.label }}</span>
                                        <span class="font-medium">{{
                                            role.count
                                        }}</span>
                                    </div>
                                    <div
                                        class="mt-1.5 h-2 overflow-hidden rounded-full bg-muted"
                                    >
                                        <div
                                            class="h-full bg-primary transition-all"
                                            :style="{
                                                width: `${Math.min(100, (role.count / Math.max(1, analytics.cards.totalUsers)) * 100)}%`,
                                            }"
                                        />
                                    </div>
                                </div>
                                <div
                                    v-if="visibleRoleBreakdown.length === 0"
                                    class="py-4 text-center text-sm text-muted-foreground"
                                >
                                    No role data available.
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="pb-3">
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <CardTitle class="text-base"
                                    >Recent activity</CardTitle
                                >
                                <Activity class="size-4 text-primary" />
                            </div>
                            <CardDescription
                                >Latest account events</CardDescription
                            >
                        </CardHeader>
                        <CardContent>
                            <ScrollArea class="h-[260px] pr-3">
                                <div class="grid gap-3">
                                    <div
                                        v-for="item in recentActivity"
                                        :key="item.id"
                                        class="rounded-md bg-muted/50 p-3"
                                    >
                                        <p
                                            class="text-sm font-medium capitalize"
                                        >
                                            {{ item.description }}
                                        </p>
                                        <p
                                            class="mt-1 text-xs text-muted-foreground"
                                        >
                                            {{ formatDate(item.createdAt) }}
                                        </p>
                                    </div>
                                    <div
                                        v-if="recentActivity.length === 0"
                                        class="flex flex-col items-center justify-center gap-2 py-8 text-center text-muted-foreground"
                                    >
                                        <Activity class="size-8 opacity-40" />
                                        <p class="text-sm">
                                            No account activity yet.
                                        </p>
                                    </div>
                                </div>
                            </ScrollArea>
                        </CardContent>
                    </Card>
                </aside>
            </section>
        </div>

        <Sheet
            v-model:open="selectedUserVisible"
            @update:open="(open) => !open && (selectedUser = null)"
        >
            <SheetContent class="w-full sm:max-w-md">
                <SheetHeader v-if="selectedUser" class="pb-4 text-left">
                    <SheetTitle>{{ selectedUser.name }}</SheetTitle>
                    <SheetDescription>{{
                        selectedUser.email
                    }}</SheetDescription>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <Badge
                            :variant="
                                selectedUser.online ? 'default' : 'outline'
                            "
                            >{{
                                selectedUser.online ? 'Online' : 'Offline'
                            }}</Badge
                        >
                        <Badge
                            :variant="
                                selectedUser.verified ? 'default' : 'secondary'
                            "
                            >{{
                                selectedUser.verified
                                    ? 'Verified'
                                    : 'Unverified'
                            }}</Badge
                        >
                        <Badge
                            :variant="
                                selectedUser.mfaEnabled ? 'default' : 'outline'
                            "
                            >{{
                                selectedUser.mfaEnabled
                                    ? 'MFA enabled'
                                    : 'MFA disabled'
                            }}</Badge
                        >
                    </div>
                </SheetHeader>

                <form
                    v-if="selectedUser"
                    class="grid gap-6 py-4"
                    @submit.prevent="submitUpdate"
                >
                    <div class="grid gap-3">
                        <h3
                            class="text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            Account
                        </h3>
                        <div class="grid gap-2">
                            <Label for="edit-name">Full name</Label>
                            <Input
                                id="edit-name"
                                v-model="editForm.name"
                                :disabled="
                                    !selectedUser.can.manage ||
                                    editForm.processing
                                "
                            />
                            <p
                                v-if="editForm.errors.name"
                                class="text-sm text-destructive"
                            >
                                {{ editForm.errors.name }}
                            </p>
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-email">Email address</Label>
                            <Input
                                id="edit-email"
                                v-model="editForm.email"
                                type="email"
                                :disabled="
                                    !selectedUser.can.manage ||
                                    editForm.processing
                                "
                            />
                            <p
                                v-if="editForm.errors.email"
                                class="text-sm text-destructive"
                            >
                                {{ editForm.errors.email }}
                            </p>
                        </div>
                        <div
                            class="flex items-center gap-3 rounded-md border p-3"
                        >
                            <Switch
                                id="edit-verified"
                                v-model:checked="editForm.email_verified"
                                :disabled="
                                    !selectedUser.can.manage ||
                                    editForm.processing
                                "
                            />
                            <Label for="edit-verified" class="cursor-pointer"
                                >Email verified</Label
                            >
                        </div>
                    </div>

                    <div class="grid gap-3">
                        <div class="flex items-center justify-between gap-3">
                            <h3
                                class="text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                Campus access
                            </h3>
                            <Button
                                v-if="selectedUser.can.manage && can.viewGlobal"
                                type="button"
                                size="sm"
                                variant="outline"
                                class="gap-1"
                                @click="addMembership"
                            >
                                <Plus class="size-3.5" />
                                Add
                            </Button>
                        </div>

                        <div
                            v-for="(membership, idx) in editForm.memberships"
                            :key="`${membership.campus_id}-${idx}`"
                            class="grid gap-3 rounded-md border p-3"
                        >
                            <div class="grid gap-2">
                                <Label :for="`edit-campus-${idx}`"
                                    >Campus</Label
                                >
                                <Select
                                    v-model="membership.campus_id"
                                    :disabled="
                                        !selectedUser.can.manage ||
                                        editForm.processing
                                    "
                                >
                                    <SelectTrigger :id="`edit-campus-${idx}`">
                                        <SelectValue
                                            placeholder="Select campus"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="campus in campuses.filter(
                                                (item) =>
                                                    can.manageableCampusIds.includes(
                                                        item.id,
                                                    ),
                                            )"
                                            :key="campus.id"
                                            :value="campus.id"
                                        >
                                            {{ campus.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="grid gap-2">
                                <Label :for="`edit-role-${idx}`">Role</Label>
                                <Select
                                    v-model="membership.role"
                                    :disabled="
                                        !selectedUser.can.manage ||
                                        editForm.processing
                                    "
                                >
                                    <SelectTrigger :id="`edit-role-${idx}`">
                                        <SelectValue
                                            placeholder="Select role"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="role in roles.filter(
                                                (item) =>
                                                    can.manageableRoles.includes(
                                                        item.value,
                                                    ),
                                            )"
                                            :key="role.value"
                                            :value="role.value"
                                        >
                                            {{ role.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div
                                class="flex flex-wrap items-center justify-between gap-3 pt-1"
                            >
                                <div class="flex items-center gap-2">
                                    <Checkbox
                                        :id="`edit-active-${idx}`"
                                        v-model:checked="membership.active"
                                        :disabled="
                                            !selectedUser.can.manage ||
                                            editForm.processing
                                        "
                                    />
                                    <Label
                                        :for="`edit-active-${idx}`"
                                        class="cursor-pointer text-sm"
                                        >Active</Label
                                    >
                                </div>
                                <div class="flex items-center gap-2">
                                    <Checkbox
                                        :id="`edit-default-${idx}`"
                                        v-model:checked="membership.is_default"
                                        :disabled="
                                            !selectedUser.can.manage ||
                                            editForm.processing
                                        "
                                    />
                                    <Label
                                        :for="`edit-default-${idx}`"
                                        class="cursor-pointer text-sm"
                                        >Default</Label
                                    >
                                </div>
                                <Button
                                    v-if="
                                        editForm.memberships.length > 1 &&
                                        selectedUser.can.manage
                                    "
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    class="text-destructive hover:text-destructive"
                                    @click="removeMembership(idx)"
                                >
                                    Remove
                                </Button>
                            </div>
                        </div>

                        <div
                            v-if="
                                selectedUser.memberships.some(
                                    (membership) =>
                                        !can.manageableCampusIds.includes(
                                            membership.campusId,
                                        ),
                                )
                            "
                            class="grid gap-2"
                        >
                            <p class="text-xs text-muted-foreground">
                                Other campuses (read-only)
                            </p>
                            <article
                                v-for="membership in selectedUser.memberships.filter(
                                    (item) =>
                                        !can.manageableCampusIds.includes(
                                            item.campusId,
                                        ),
                                )"
                                :key="membership.id"
                                class="rounded-md bg-muted/50 p-3 text-sm"
                            >
                                {{ membership.campusName }} ·
                                {{
                                    membership.roleLabel ??
                                    roleLabel(membership.role)
                                }}
                            </article>
                        </div>
                    </div>

                    <div class="grid gap-3">
                        <h3
                            class="text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            Sessions
                        </h3>
                        <ScrollArea class="h-[180px] pr-3">
                            <div class="grid gap-2">
                                <article
                                    v-for="session in selectedUser.sessions"
                                    :key="session.id"
                                    class="rounded-md bg-muted/50 p-3"
                                >
                                    <div
                                        class="flex items-start justify-between gap-3"
                                    >
                                        <div class="min-w-0">
                                            <p
                                                class="truncate text-sm font-medium"
                                            >
                                                {{ session.userAgent }}
                                            </p>
                                            <p
                                                class="mt-0.5 text-xs text-muted-foreground"
                                            >
                                                {{ session.ipAddress }} ·
                                                {{
                                                    formatDate(
                                                        session.lastSeenAt,
                                                    )
                                                }}
                                            </p>
                                        </div>
                                        <Badge
                                            :variant="
                                                session.online
                                                    ? 'default'
                                                    : 'outline'
                                            "
                                            class="shrink-0"
                                            >{{
                                                session.online
                                                    ? 'Online'
                                                    : 'Stale'
                                            }}</Badge
                                        >
                                    </div>
                                </article>
                                <p
                                    v-if="selectedUser.sessions.length === 0"
                                    class="py-4 text-center text-sm text-muted-foreground"
                                >
                                    No sessions recorded.
                                </p>
                            </div>
                        </ScrollArea>
                    </div>

                    <SheetFooter class="flex-col gap-2 sm:flex-col">
                        <Button
                            v-if="selectedUser.can.manage"
                            type="submit"
                            class="w-full"
                            :disabled="editForm.processing"
                            >Save changes</Button
                        >
                        <div class="grid grid-cols-2 gap-2">
                            <Button
                                v-if="selectedUser.can.manage"
                                type="button"
                                variant="outline"
                                class="gap-2"
                                :disabled="editForm.processing"
                                @click="
                                    confirming = {
                                        action: selectedUser.verified
                                            ? 'unverify'
                                            : 'verify',
                                        user: selectedUser,
                                    }
                                "
                            >
                                <MailCheck class="size-4" />
                                {{
                                    selectedUser.verified
                                        ? 'Unverify'
                                        : 'Verify'
                                }}
                            </Button>
                            <Button
                                v-if="selectedUser.can.manage"
                                type="button"
                                variant="outline"
                                class="gap-2"
                                :disabled="editForm.processing"
                                @click="
                                    confirming = {
                                        action: 'reset',
                                        user: selectedUser,
                                    }
                                "
                            >
                                <KeyRound class="size-4" />
                                Reset
                            </Button>
                            <Button
                                v-if="selectedUser.can.impersonate"
                                type="button"
                                variant="outline"
                                class="gap-2"
                                :disabled="editForm.processing"
                                @click="
                                    confirming = {
                                        action: 'impersonate',
                                        user: selectedUser,
                                    }
                                "
                            >
                                <ShieldCheck class="size-4" />
                                Impersonate
                            </Button>
                            <Button
                                v-if="selectedUser.can.manage"
                                type="button"
                                variant="outline"
                                class="gap-2 text-destructive hover:text-destructive"
                                :disabled="editForm.processing"
                                @click="
                                    confirming = {
                                        action: 'logout',
                                        user: selectedUser,
                                    }
                                "
                            >
                                <LogOut class="size-4" />
                                Log out
                            </Button>
                        </div>
                    </SheetFooter>
                </form>
            </SheetContent>
        </Sheet>

        <AlertDialog
            :open="confirming !== null"
            @update:open="(open) => !open && (confirming = null)"
        >
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>{{
                        confirmationTitle()
                    }}</AlertDialogTitle>
                    <AlertDialogDescription>{{
                        confirmationDescription()
                    }}</AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="confirming = null"
                        >Cancel</AlertDialogCancel
                    >
                    <AlertDialogAction @click="runConfirmedAction"
                        >Confirm</AlertDialogAction
                    >
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
