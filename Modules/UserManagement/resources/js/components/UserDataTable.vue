<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import {
    type ColumnDef,
    type ColumnFiltersState,
    type SortingState,
    type VisibilityState,
    FlexRender,
    getCoreRowModel,
    getFilteredRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    useVueTable,
} from '@tanstack/vue-table';
import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    Check,
    ChevronDown,
    ChevronsLeft,
    ChevronsRight,
    ChevronLeft,
    ChevronRight,
    Circle,
    MailCheck,
    MoreHorizontal,
    PlusCircle,
    Search,
    UsersRound,
    X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { index } from '@/routes/admin/users';
import type { AppPageProps } from '@/types';

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

type FilterOption = {
    value: string;
    label: string;
    icon?: unknown;
};

const props = defineProps<{
    users: PaginatedUsers;
    filters: Record<string, string | null>;
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

const emit = defineEmits<{
    'select-user': [user: ManagedUser];
    'confirm-action': [payload: { action: string; user: ManagedUser }];
}>();

const page = usePage<AppPageProps>();
const campusSlug = computed(() => page.props.currentCampus!.slug);

const filterForm = ref({
    search: props.filters.search ?? '',
    role: props.filters.role ?? '',
    campus: props.filters.campus ?? '',
    verified: props.filters.verified ?? '',
    online: props.filters.online ?? '',
    mfa: props.filters.mfa ?? '',
    age: props.filters.age ?? '',
});

const sorting = ref<SortingState>([]);
const columnFilters = ref<ColumnFiltersState>([]);
const columnVisibility = ref<VisibilityState>({});
const rowSelection = ref({});

const columns: ColumnDef<ManagedUser>[] = [
    {
        accessorKey: 'name',
        header: 'Account',
        enableSorting: true,
    },
    {
        id: 'roles',
        header: 'Roles',
        enableSorting: false,
    },
    {
        id: 'status',
        header: 'Status',
        enableSorting: false,
    },
    {
        accessorKey: 'activeSessions',
        header: 'Sessions',
        enableSorting: true,
    },
    {
        accessorKey: 'lastSeenAt',
        header: 'Last seen',
        enableSorting: true,
    },
    {
        id: 'actions',
        header: '',
        enableSorting: false,
    },
];

const table = useVueTable({
    data: computed(() => props.users.data),
    columns,
    state: {
        get sorting() {
            return sorting.value;
        },
        get columnFilters() {
            return columnFilters.value;
        },
        get columnVisibility() {
            return columnVisibility.value;
        },
        get rowSelection() {
            return rowSelection.value;
        },
    },
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    onSortingChange: (updater) => {
        sorting.value =
            typeof updater === 'function' ? updater(sorting.value) : updater;
    },
    onColumnFiltersChange: (updater) => {
        columnFilters.value =
            typeof updater === 'function'
                ? updater(columnFilters.value)
                : updater;
    },
    onColumnVisibilityChange: (updater) => {
        columnVisibility.value =
            typeof updater === 'function'
                ? updater(columnVisibility.value)
                : updater;
    },
    onRowSelectionChange: (updater) => {
        rowSelection.value =
            typeof updater === 'function'
                ? updater(rowSelection.value)
                : updater;
    },
});

const roleOptions = computed<FilterOption[]>(() =>
    props.roles.map((role) => ({ value: role.value, label: role.label })),
);

const campusOptions = computed<FilterOption[]>(() =>
    props.campuses
        .filter(
            (campus) =>
                props.can.viewGlobal ||
                props.can.manageableCampusIds.includes(campus.id),
        )
        .map((campus) => ({ value: campus.slug, label: campus.name })),
);

const verifiedOptions: FilterOption[] = [
    { value: 'verified', label: 'Verified' },
    { value: 'unverified', label: 'Unverified' },
];

const onlineOptions: FilterOption[] = [
    { value: 'online', label: 'Online' },
    { value: 'offline', label: 'Offline' },
];

const mfaOptions: FilterOption[] = [
    { value: 'enabled', label: 'Enabled' },
    { value: 'disabled', label: 'Disabled' },
];

const ageOptions: FilterOption[] = [
    { value: '7', label: 'Last 7 days' },
    { value: '30', label: 'Last 30 days' },
    { value: 'older', label: 'Older' },
];

const activeFilters = computed(() => {
    const filters: {
        key: string;
        label: string;
        value: string;
        display: string;
    }[] = [];

    if (filterForm.value.search) {
        filters.push({
            key: 'search',
            label: 'Search',
            value: filterForm.value.search,
            display: filterForm.value.search,
        });
    }

    if (filterForm.value.role) {
        filters.push({
            key: 'role',
            label: 'Role',
            value: filterForm.value.role,
            display: roleLabel(filterForm.value.role),
        });
    }

    if (filterForm.value.campus) {
        const campus = props.campuses.find(
            (c) => c.slug === filterForm.value.campus,
        );
        filters.push({
            key: 'campus',
            label: 'Campus',
            value: filterForm.value.campus,
            display: campus?.name ?? filterForm.value.campus,
        });
    }

    if (filterForm.value.verified) {
        filters.push({
            key: 'verified',
            label: 'Verification',
            value: filterForm.value.verified,
            display: verifiedLabel(filterForm.value.verified),
        });
    }

    if (filterForm.value.online) {
        filters.push({
            key: 'online',
            label: 'Presence',
            value: filterForm.value.online,
            display: onlineLabel(filterForm.value.online),
        });
    }

    if (filterForm.value.mfa) {
        filters.push({
            key: 'mfa',
            label: 'MFA',
            value: filterForm.value.mfa,
            display: mfaLabel(filterForm.value.mfa),
        });
    }

    if (filterForm.value.age) {
        filters.push({
            key: 'age',
            label: 'Created',
            value: filterForm.value.age,
            display: ageLabel(filterForm.value.age),
        });
    }

    return filters;
});

function roleLabel(value: string) {
    return props.roles.find((role) => role.value === value)?.label ?? value;
}

function verifiedLabel(value: string) {
    return (
        verifiedOptions.find((option) => option.value === value)?.label ?? value
    );
}

function onlineLabel(value: string) {
    return (
        onlineOptions.find((option) => option.value === value)?.label ?? value
    );
}

function mfaLabel(value: string) {
    return mfaOptions.find((option) => option.value === value)?.label ?? value;
}

function ageLabel(value: string) {
    return ageOptions.find((option) => option.value === value)?.label ?? value;
}

function initials(name: string) {
    return name
        .split(' ')
        .map((part) => part.charAt(0))
        .slice(0, 2)
        .join('')
        .toUpperCase();
}

function formatDate(value?: string | null) {
    return value ? new Date(value).toLocaleString() : 'Never';
}

function statusBadgeVariant(online: boolean): 'default' | 'outline' {
    return online ? 'default' : 'outline';
}

function applyFilters() {
    const query = Object.fromEntries(
        Object.entries(filterForm.value).filter(([, value]) => value !== ''),
    );

    router.get(
        index.url({ campus: campusSlug.value }, { query }),
        {},
        { preserveState: true, replace: true },
    );
}

function setFilter(key: keyof typeof filterForm.value, value: string) {
    filterForm.value[key] = value;
    applyFilters();
}

function clearFilter(key: keyof typeof filterForm.value) {
    filterForm.value[key] = '';
    applyFilters();
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

function goToPage(url: string | null) {
    if (!url) {
        return;
    }

    router.get(url, {}, { preserveScroll: true, preserveState: true });
}

function updateSearch() {
    applyFilters();
}
</script>

<template>
    <div class="min-w-0 rounded-lg border bg-card">
        <div class="flex flex-col gap-4 border-b p-4">
            <div
                class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between"
            >
                <div class="relative max-w-sm min-w-[220px] flex-1">
                    <Search
                        class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="filterForm.search"
                        class="pl-9"
                        placeholder="Search users by name or email"
                        @keyup.enter="updateSearch"
                    />
                </div>

                <div class="flex flex-wrap items-end gap-2">
                    <Popover>
                        <PopoverTrigger as-child>
                            <Button
                                variant="outline"
                                size="sm"
                                class="h-8 border-dashed"
                            >
                                <PlusCircle class="mr-2 size-4" />
                                Role
                                <template v-if="filterForm.role">
                                    <Separator
                                        orientation="vertical"
                                        class="mx-2 h-4"
                                    />
                                    <Badge
                                        variant="secondary"
                                        class="rounded-sm px-1 font-normal"
                                    >
                                        {{ roleLabel(filterForm.role) }}
                                    </Badge>
                                </template>
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-[200px] p-0" align="start">
                            <Command>
                                <CommandInput placeholder="Search role" />
                                <CommandList>
                                    <CommandEmpty>No role found.</CommandEmpty>
                                    <CommandGroup>
                                        <CommandItem
                                            value=""
                                            @select="setFilter('role', '')"
                                        >
                                            <span class="ml-2">Any role</span>
                                        </CommandItem>
                                        <CommandItem
                                            v-for="option in roleOptions"
                                            :key="option.value"
                                            :value="option.value"
                                            @select="
                                                setFilter('role', option.value)
                                            "
                                        >
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <div
                                                    class="flex size-4 items-center justify-center rounded-sm border"
                                                >
                                                    <Check
                                                        v-if="
                                                            filterForm.role ===
                                                            option.value
                                                        "
                                                        class="size-3.5"
                                                    />
                                                </div>
                                                <span>{{ option.label }}</span>
                                            </div>
                                        </CommandItem>
                                    </CommandGroup>
                                </CommandList>
                            </Command>
                        </PopoverContent>
                    </Popover>

                    <Popover>
                        <PopoverTrigger as-child>
                            <Button
                                variant="outline"
                                size="sm"
                                class="h-8 border-dashed"
                            >
                                <PlusCircle class="mr-2 size-4" />
                                Campus
                                <template v-if="filterForm.campus">
                                    <Separator
                                        orientation="vertical"
                                        class="mx-2 h-4"
                                    />
                                    <Badge
                                        variant="secondary"
                                        class="rounded-sm px-1 font-normal"
                                    >
                                        {{
                                            campusOptions.find(
                                                (c) =>
                                                    c.value ===
                                                    filterForm.campus,
                                            )?.label ?? filterForm.campus
                                        }}
                                    </Badge>
                                </template>
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-[220px] p-0" align="start">
                            <Command>
                                <CommandInput placeholder="Search campus" />
                                <CommandList>
                                    <CommandEmpty
                                        >No campus found.</CommandEmpty
                                    >
                                    <CommandGroup>
                                        <CommandItem
                                            v-if="can.viewGlobal"
                                            value=""
                                            @select="setFilter('campus', '')"
                                        >
                                            <span class="ml-2"
                                                >All campuses</span
                                            >
                                        </CommandItem>
                                        <CommandItem
                                            v-for="option in campusOptions"
                                            :key="option.value"
                                            :value="option.value"
                                            @select="
                                                setFilter(
                                                    'campus',
                                                    option.value,
                                                )
                                            "
                                        >
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <div
                                                    class="flex size-4 items-center justify-center rounded-sm border"
                                                >
                                                    <Check
                                                        v-if="
                                                            filterForm.campus ===
                                                            option.value
                                                        "
                                                        class="size-3.5"
                                                    />
                                                </div>
                                                <span>{{ option.label }}</span>
                                            </div>
                                        </CommandItem>
                                    </CommandGroup>
                                </CommandList>
                            </Command>
                        </PopoverContent>
                    </Popover>

                    <Popover>
                        <PopoverTrigger as-child>
                            <Button
                                variant="outline"
                                size="sm"
                                class="h-8 border-dashed"
                            >
                                <PlusCircle class="mr-2 size-4" />
                                Verification
                                <template v-if="filterForm.verified">
                                    <Separator
                                        orientation="vertical"
                                        class="mx-2 h-4"
                                    />
                                    <Badge
                                        variant="secondary"
                                        class="rounded-sm px-1 font-normal"
                                    >
                                        {{ verifiedLabel(filterForm.verified) }}
                                    </Badge>
                                </template>
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-[200px] p-0" align="start">
                            <Command>
                                <CommandList>
                                    <CommandGroup>
                                        <CommandItem
                                            value=""
                                            @select="setFilter('verified', '')"
                                        >
                                            <span class="ml-2">Any</span>
                                        </CommandItem>
                                        <CommandItem
                                            v-for="option in verifiedOptions"
                                            :key="option.value"
                                            :value="option.value"
                                            @select="
                                                setFilter(
                                                    'verified',
                                                    option.value,
                                                )
                                            "
                                        >
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <div
                                                    class="flex size-4 items-center justify-center rounded-sm border"
                                                >
                                                    <Check
                                                        v-if="
                                                            filterForm.verified ===
                                                            option.value
                                                        "
                                                        class="size-3.5"
                                                    />
                                                </div>
                                                <span>{{ option.label }}</span>
                                            </div>
                                        </CommandItem>
                                    </CommandGroup>
                                </CommandList>
                            </Command>
                        </PopoverContent>
                    </Popover>

                    <Popover>
                        <PopoverTrigger as-child>
                            <Button
                                variant="outline"
                                size="sm"
                                class="h-8 border-dashed"
                            >
                                <PlusCircle class="mr-2 size-4" />
                                Presence
                                <template v-if="filterForm.online">
                                    <Separator
                                        orientation="vertical"
                                        class="mx-2 h-4"
                                    />
                                    <Badge
                                        variant="secondary"
                                        class="rounded-sm px-1 font-normal"
                                    >
                                        {{ onlineLabel(filterForm.online) }}
                                    </Badge>
                                </template>
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-[200px] p-0" align="start">
                            <Command>
                                <CommandList>
                                    <CommandGroup>
                                        <CommandItem
                                            value=""
                                            @select="setFilter('online', '')"
                                        >
                                            <span class="ml-2">Any</span>
                                        </CommandItem>
                                        <CommandItem
                                            v-for="option in onlineOptions"
                                            :key="option.value"
                                            :value="option.value"
                                            @select="
                                                setFilter(
                                                    'online',
                                                    option.value,
                                                )
                                            "
                                        >
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <div
                                                    class="flex size-4 items-center justify-center rounded-sm border"
                                                >
                                                    <Check
                                                        v-if="
                                                            filterForm.online ===
                                                            option.value
                                                        "
                                                        class="size-3.5"
                                                    />
                                                </div>
                                                <span>{{ option.label }}</span>
                                            </div>
                                        </CommandItem>
                                    </CommandGroup>
                                </CommandList>
                            </Command>
                        </PopoverContent>
                    </Popover>

                    <Popover>
                        <PopoverTrigger as-child>
                            <Button
                                variant="outline"
                                size="sm"
                                class="h-8 border-dashed"
                            >
                                <PlusCircle class="mr-2 size-4" />
                                MFA
                                <template v-if="filterForm.mfa">
                                    <Separator
                                        orientation="vertical"
                                        class="mx-2 h-4"
                                    />
                                    <Badge
                                        variant="secondary"
                                        class="rounded-sm px-1 font-normal"
                                    >
                                        {{ mfaLabel(filterForm.mfa) }}
                                    </Badge>
                                </template>
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-[200px] p-0" align="start">
                            <Command>
                                <CommandList>
                                    <CommandGroup>
                                        <CommandItem
                                            value=""
                                            @select="setFilter('mfa', '')"
                                        >
                                            <span class="ml-2">Any</span>
                                        </CommandItem>
                                        <CommandItem
                                            v-for="option in mfaOptions"
                                            :key="option.value"
                                            :value="option.value"
                                            @select="
                                                setFilter('mfa', option.value)
                                            "
                                        >
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <div
                                                    class="flex size-4 items-center justify-center rounded-sm border"
                                                >
                                                    <Check
                                                        v-if="
                                                            filterForm.mfa ===
                                                            option.value
                                                        "
                                                        class="size-3.5"
                                                    />
                                                </div>
                                                <span>{{ option.label }}</span>
                                            </div>
                                        </CommandItem>
                                    </CommandGroup>
                                </CommandList>
                            </Command>
                        </PopoverContent>
                    </Popover>

                    <Popover>
                        <PopoverTrigger as-child>
                            <Button
                                variant="outline"
                                size="sm"
                                class="h-8 border-dashed"
                            >
                                <PlusCircle class="mr-2 size-4" />
                                Created
                                <template v-if="filterForm.age">
                                    <Separator
                                        orientation="vertical"
                                        class="mx-2 h-4"
                                    />
                                    <Badge
                                        variant="secondary"
                                        class="rounded-sm px-1 font-normal"
                                    >
                                        {{ ageLabel(filterForm.age) }}
                                    </Badge>
                                </template>
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-[220px] p-0" align="start">
                            <Command>
                                <CommandList>
                                    <CommandGroup>
                                        <CommandItem
                                            value=""
                                            @select="setFilter('age', '')"
                                        >
                                            <span class="ml-2">Any time</span>
                                        </CommandItem>
                                        <CommandItem
                                            v-for="option in ageOptions"
                                            :key="option.value"
                                            :value="option.value"
                                            @select="
                                                setFilter('age', option.value)
                                            "
                                        >
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <div
                                                    class="flex size-4 items-center justify-center rounded-sm border"
                                                >
                                                    <Check
                                                        v-if="
                                                            filterForm.age ===
                                                            option.value
                                                        "
                                                        class="size-3.5"
                                                    />
                                                </div>
                                                <span>{{ option.label }}</span>
                                            </div>
                                        </CommandItem>
                                    </CommandGroup>
                                </CommandList>
                            </Command>
                        </PopoverContent>
                    </Popover>

                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button
                                variant="outline"
                                size="sm"
                                class="ml-auto h-8"
                            >
                                <ChevronDown class="mr-2 size-4" />
                                Columns
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-[150px]">
                            <DropdownMenuCheckboxItem
                                v-for="column in table
                                    .getAllColumns()
                                    .filter((column) => column.getCanHide())"
                                :key="column.id"
                                class="capitalize"
                                :model-value="column.getIsVisible()"
                                @update:model-value="
                                    (value) => column.toggleVisibility(!!value)
                                "
                            >
                                {{ column.id }}
                            </DropdownMenuCheckboxItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>

            <div
                v-if="activeFilters.length > 0"
                class="flex flex-wrap items-center gap-2"
            >
                <span class="text-sm text-muted-foreground"
                    >Active filters:</span
                >
                <Badge
                    v-for="filter in activeFilters"
                    :key="filter.key"
                    variant="secondary"
                    class="gap-1 pr-1 font-normal"
                >
                    <span class="text-xs text-muted-foreground"
                        >{{ filter.label }}:</span
                    >
                    {{ filter.display }}
                    <Button
                        variant="ghost"
                        size="icon"
                        class="size-4 hover:bg-transparent"
                        @click="
                            clearFilter(filter.key as keyof typeof filterForm)
                        "
                    >
                        <X class="size-3" />
                    </Button>
                </Badge>
                <Button
                    variant="ghost"
                    size="sm"
                    class="h-7 px-2 text-muted-foreground"
                    @click="resetFilters"
                    >Reset all</Button
                >
            </div>
        </div>

        <div class="overflow-x-auto">
            <Table>
                <TableHeader>
                    <TableRow
                        v-for="headerGroup in table.getHeaderGroups()"
                        :key="headerGroup.id"
                    >
                        <TableHead
                            v-for="header in headerGroup.headers"
                            :key="header.id"
                            :class="
                                header.column.getCanSort()
                                    ? 'cursor-pointer select-none'
                                    : ''
                            "
                            @click="
                                header.column.getCanSort() &&
                                header.column.toggleSorting()
                            "
                        >
                            <div class="flex items-center gap-1">
                                <FlexRender
                                    v-if="!header.isPlaceholder"
                                    :render="header.column.columnDef.header"
                                    :props="header.getContext()"
                                />
                                <template v-if="header.column.getCanSort()">
                                    <ArrowUp
                                        v-if="
                                            header.column.getIsSorted() ===
                                            'asc'
                                        "
                                        class="ml-1 size-3"
                                    />
                                    <ArrowDown
                                        v-else-if="
                                            header.column.getIsSorted() ===
                                            'desc'
                                        "
                                        class="ml-1 size-3"
                                    />
                                    <ArrowUpDown
                                        v-else
                                        class="ml-1 size-3 text-muted-foreground/60"
                                    />
                                </template>
                            </div>
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <template v-if="table.getRowModel().rows.length">
                        <TableRow
                            v-for="row in table.getRowModel().rows"
                            :key="row.id"
                            class="cursor-pointer"
                            :data-state="row.getIsSelected() && 'selected'"
                            @click="emit('select-user', row.original)"
                        >
                            <TableCell
                                v-for="cell in row.getVisibleCells()"
                                :key="cell.id"
                            >
                                <template v-if="cell.column.id === 'name'">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-9 w-9 items-center justify-center rounded-full border bg-primary/10 text-xs font-medium text-primary"
                                        >
                                            {{ initials(row.original.name) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate font-medium">
                                                {{ row.original.name }}
                                            </p>
                                            <p
                                                class="truncate text-xs text-muted-foreground"
                                            >
                                                {{ row.original.email }}
                                            </p>
                                        </div>
                                    </div>
                                </template>
                                <template
                                    v-else-if="cell.column.id === 'roles'"
                                >
                                    <div class="flex flex-wrap gap-1">
                                        <Badge
                                            v-for="membership in row.original.memberships.slice(
                                                0,
                                                2,
                                            )"
                                            :key="
                                                membership.id ??
                                                membership.campusId
                                            "
                                            variant="secondary"
                                            class="font-normal"
                                        >
                                            {{
                                                membership.roleLabel ??
                                                roleLabel(membership.role)
                                            }}
                                        </Badge>
                                        <Badge
                                            v-if="
                                                row.original.memberships
                                                    .length > 2
                                            "
                                            variant="outline"
                                            class="font-normal"
                                            >+{{
                                                row.original.memberships
                                                    .length - 2
                                            }}</Badge
                                        >
                                    </div>
                                </template>
                                <template
                                    v-else-if="cell.column.id === 'status'"
                                >
                                    <div class="flex flex-wrap gap-1.5">
                                        <Badge
                                            :variant="
                                                row.original.verified
                                                    ? 'default'
                                                    : 'secondary'
                                            "
                                            class="gap-1 font-normal"
                                        >
                                            <MailCheck
                                                v-if="row.original.verified"
                                                class="size-3"
                                            />
                                            {{
                                                row.original.verified
                                                    ? 'Verified'
                                                    : 'Unverified'
                                            }}
                                        </Badge>
                                        <Badge
                                            :variant="
                                                row.original.mfaEnabled
                                                    ? 'default'
                                                    : 'outline'
                                            "
                                            class="font-normal"
                                            >{{
                                                row.original.mfaEnabled
                                                    ? 'MFA'
                                                    : 'No MFA'
                                            }}</Badge
                                        >
                                        <Badge
                                            :variant="
                                                statusBadgeVariant(
                                                    row.original.online,
                                                )
                                            "
                                            class="gap-1 font-normal"
                                        >
                                            <Circle
                                                class="size-1.5"
                                                :class="
                                                    row.original.online
                                                        ? 'fill-emerald-500 text-emerald-500'
                                                        : 'fill-muted-foreground/40 text-muted-foreground/40'
                                                "
                                            />
                                            {{
                                                row.original.online
                                                    ? 'Online'
                                                    : 'Offline'
                                            }}
                                        </Badge>
                                    </div>
                                </template>
                                <template
                                    v-else-if="
                                        cell.column.id === 'activeSessions'
                                    "
                                >
                                    <span class="text-right tabular-nums">{{
                                        row.original.activeSessions
                                    }}</span>
                                </template>
                                <template
                                    v-else-if="cell.column.id === 'lastSeenAt'"
                                >
                                    <span class="text-muted-foreground">{{
                                        formatDate(row.original.lastSeenAt)
                                    }}</span>
                                </template>
                                <template
                                    v-else-if="cell.column.id === 'actions'"
                                >
                                    <DropdownMenu>
                                        <DropdownMenuTrigger
                                            as-child
                                            @click.stop
                                        >
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                class="size-8"
                                            >
                                                <MoreHorizontal
                                                    class="size-4"
                                                />
                                                <span class="sr-only"
                                                    >Open menu</span
                                                >
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent
                                            align="end"
                                            class="w-52"
                                        >
                                            <DropdownMenuItem
                                                @click.stop="
                                                    emit(
                                                        'select-user',
                                                        row.original,
                                                    )
                                                "
                                                >View details</DropdownMenuItem
                                            >
                                            <DropdownMenuItem
                                                v-if="
                                                    row.original.can.impersonate
                                                "
                                                @click.stop="
                                                    emit('confirm-action', {
                                                        action: 'impersonate',
                                                        user: row.original,
                                                    })
                                                "
                                                >Impersonate</DropdownMenuItem
                                            >
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem
                                                v-if="row.original.can.manage"
                                                @click.stop="
                                                    emit('confirm-action', {
                                                        action: row.original
                                                            .verified
                                                            ? 'unverify'
                                                            : 'verify',
                                                        user: row.original,
                                                    })
                                                "
                                            >
                                                {{
                                                    row.original.verified
                                                        ? 'Unverify email'
                                                        : 'Verify email'
                                                }}
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                v-if="row.original.can.manage"
                                                @click.stop="
                                                    emit('confirm-action', {
                                                        action: 'reset',
                                                        user: row.original,
                                                    })
                                                "
                                                >Send password
                                                reset</DropdownMenuItem
                                            >
                                            <DropdownMenuItem
                                                v-if="row.original.can.manage"
                                                class="text-destructive focus:text-destructive"
                                                @click.stop="
                                                    emit('confirm-action', {
                                                        action: 'logout',
                                                        user: row.original,
                                                    })
                                                "
                                                >Force logout</DropdownMenuItem
                                            >
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </template>
                                <template v-else>
                                    <FlexRender
                                        :render="cell.column.columnDef.cell"
                                        :props="cell.getContext()"
                                    />
                                </template>
                            </TableCell>
                        </TableRow>
                    </template>
                    <template v-else>
                        <TableRow>
                            <TableCell
                                :colspan="columns.length"
                                class="h-32 text-center"
                            >
                                <div
                                    class="flex flex-col items-center justify-center gap-2 text-muted-foreground"
                                >
                                    <UsersRound class="size-8 opacity-40" />
                                    <p>No users match the current filters.</p>
                                </div>
                            </TableCell>
                        </TableRow>
                    </template>
                </TableBody>
            </Table>
        </div>

        <div
            class="flex flex-col items-center justify-between gap-4 border-t p-4 sm:flex-row"
        >
            <p class="text-sm text-muted-foreground">
                Showing {{ users.from ?? 0 }}-{{ users.to ?? 0 }} of
                {{ users.total }}
            </p>
            <div class="flex items-center gap-1">
                <Button
                    variant="outline"
                    size="icon"
                    class="size-8"
                    :disabled="users.current_page <= 1"
                    @click="goToPage(users.links[0]?.url ?? null)"
                >
                    <ChevronsLeft class="size-4" />
                </Button>
                <Button
                    variant="outline"
                    size="icon"
                    class="size-8"
                    :disabled="users.current_page <= 1"
                    @click="
                        goToPage(
                            users.links.find(
                                (link) => link.label === 'Previous',
                            )?.url ?? null,
                        )
                    "
                >
                    <ChevronLeft class="size-4" />
                </Button>
                <div class="flex items-center gap-1 px-2">
                    <Button
                        v-for="link in users.links.filter(
                            (link) => !isNaN(Number(link.label)),
                        )"
                        :key="link.label"
                        size="sm"
                        :variant="link.active ? 'default' : 'outline'"
                        :disabled="!link.url"
                        class="min-w-8"
                        @click="goToPage(link.url)"
                    >
                        {{ link.label }}
                    </Button>
                </div>
                <Button
                    variant="outline"
                    size="icon"
                    class="size-8"
                    :disabled="users.current_page >= users.last_page"
                    @click="
                        goToPage(
                            users.links.find((link) => link.label === 'Next')
                                ?.url ?? null,
                        )
                    "
                >
                    <ChevronRight class="size-4" />
                </Button>
                <Button
                    variant="outline"
                    size="icon"
                    class="size-8"
                    :disabled="users.current_page >= users.last_page"
                    @click="
                        goToPage(
                            users.links[users.links.length - 1]?.url ?? null,
                        )
                    "
                >
                    <ChevronsRight class="size-4" />
                </Button>
            </div>
        </div>
    </div>
</template>
