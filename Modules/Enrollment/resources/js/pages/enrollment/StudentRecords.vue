<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    type ColumnDef,
    type SortingState,
    type VisibilityState,
    FlexRender,
    getCoreRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    useVueTable,
} from '@tanstack/vue-table';
import {
    Archive,
    ArchiveRestore,
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    BadgeCheck,
    ChevronsLeft,
    ChevronsRight,
    ChevronLeft,
    ChevronRight,
    ClipboardCheck,
    FileWarning,
    GraduationCap,
    MoreHorizontal,
    Search,
    ShieldCheck,
    UserRoundPlus,
    UsersRound,
} from 'lucide-vue-next';
import {
    ArcElement,
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Legend,
    LinearScale,
    Tooltip,
} from 'chart.js';
import { computed, ref } from 'vue';
import { Bar, Doughnut } from 'vue-chartjs';
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
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { InputGroup, InputGroupInput } from '@/components/ui/input-group';
import { NativeSelect } from '@/components/ui/native-select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { create as createStudent, destroy, edit, index, restore, show } from '@/routes/admin/students';
import type { AppPageProps, BreadcrumbItem } from '@/types';

ChartJS.register(CategoryScale, LinearScale, BarElement, ArcElement, Tooltip, Legend);

type StudentRow = {
    id: number;
    fullName: string;
    studentNumber?: string | null;
    email?: string | null;
    phone?: string | null;
    status: string;
    enrollmentsCount: number;
    guardiansCount: number;
    primaryGuardian?: string | null;
    deletedAt?: string | null;
    documentSummary: {
        missing: string[];
        verifiedCount: number;
        requiredCount: number;
        ready: boolean;
    };
    transferSummary: {
        openEvaluations: number;
        creditedSubjects: number;
    };
    currentEnrollment?: {
        id: number;
        status: string;
        classification?: string | null;
        period?: string | null;
        term?: string | null;
        academicYear?: string | null;
        program?: string | null;
        curriculum?: string | null;
        section?: string | null;
    } | null;
    can: {
        view: boolean;
        edit: boolean;
        delete: boolean;
        restore: boolean;
    };
};
type SelectOption = { value: string; label: string; required?: boolean };
type Option = { id: number; name: string; code?: string | null };
type ChartPoint = { label: string; value: number };
type PaginatedStudents = {
    data: StudentRow[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
};

const props = defineProps<{
    students: PaginatedStudents;
    filters: Record<string, string | null>;
    summary: {
        total: number;
        activeEnrollments: number;
        waiting: number;
        documentGaps: number;
        documentReady: number;
        transferReviews: number;
        archived: number;
    };
    charts: {
        profileStatuses: ChartPoint[];
        enrollmentStatuses: ChartPoint[];
        documentReadiness: ChartPoint[];
        transferWorkload: ChartPoint[];
    };
    options: {
        statuses: string[];
        enrollmentStatuses: SelectOption[];
        classifications: SelectOption[];
        documentTypes: SelectOption[];
        programs: (Option & { curricula: Option[] })[];
        sections: (Option & { programId: number; termId: number })[];
        terms: Option[];
    };
}>();

const page = usePage<AppPageProps>();
const campusSlug = computed(() => page.props.currentCampus!.slug);
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Student Records' }];

const filterForm = ref({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    enrollment_status: props.filters.enrollment_status ?? '',
    term: props.filters.term ?? '',
    program: props.filters.program ?? '',
    section: props.filters.section ?? '',
    view: props.filters.view ?? 'all',
});
const sorting = ref<SortingState>([]);
const columnVisibility = ref<VisibilityState>({});
const rowSelection = ref({});
const pendingLifecycleAction = ref<{ student: StudentRow; action: 'archive' | 'restore' } | null>(null);

const lifecycleViews = computed(() => [
    { value: 'all', label: 'All active', count: props.summary.total },
    { value: 'document_gaps', label: 'Document gaps', count: props.summary.documentGaps },
    { value: 'transfer_reviews', label: 'Transfer reviews', count: props.summary.transferReviews },
    { value: 'archived', label: 'Archived', count: props.summary.archived },
]);

const statCards = computed(() => [
    { label: 'Active records', value: props.summary.total, icon: UsersRound },
    { label: 'Pending or approved', value: props.summary.activeEnrollments, icon: ClipboardCheck },
    { label: 'Draft or waitlisted', value: props.summary.waiting, icon: GraduationCap },
    { label: 'Document gaps', value: props.summary.documentGaps, icon: FileWarning },
    { label: 'Transfer reviews', value: props.summary.transferReviews, icon: ShieldCheck },
    { label: 'Archived', value: props.summary.archived, icon: Archive },
]);

const chartColors = ['#2563eb', '#16a34a', '#f59e0b', '#dc2626', '#7c3aed', '#0891b2'];
const profileStatusChart = computed(() => ({
    labels: props.charts.profileStatuses.map((item) => item.label),
    datasets: [
        {
            label: 'Students',
            data: props.charts.profileStatuses.map((item) => item.value),
            backgroundColor: chartColors,
            borderRadius: 6,
        },
    ],
}));
const enrollmentStatusChart = computed(() => ({
    labels: props.charts.enrollmentStatuses.map((item) => item.label),
    datasets: [
        {
            label: 'Enrollments',
            data: props.charts.enrollmentStatuses.map((item) => item.value),
            backgroundColor: chartColors,
            borderRadius: 6,
        },
    ],
}));
const documentReadinessChart = computed(() => ({
    labels: props.charts.documentReadiness.map((item) => item.label),
    datasets: [
        {
            data: props.charts.documentReadiness.map((item) => item.value),
            backgroundColor: ['#16a34a', '#f59e0b'],
            borderWidth: 0,
        },
    ],
}));
const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
    },
};

const columns: ColumnDef<StudentRow>[] = [
    { id: 'select', header: '', enableSorting: false },
    { accessorKey: 'fullName', header: 'Student', enableSorting: true },
    { accessorKey: 'status', header: 'Profile', enableSorting: true },
    { id: 'enrollment', header: 'Current enrollment', enableSorting: false },
    { id: 'documents', header: 'Documents', enableSorting: false },
    { id: 'transfer', header: 'Transfer', enableSorting: false },
    { id: 'guardians', header: 'Guardians', enableSorting: false },
    { id: 'actions', header: '', enableSorting: false },
];

const table = useVueTable({
    data: computed(() => props.students.data),
    columns,
    state: {
        get sorting() {
            return sorting.value;
        },
        get columnVisibility() {
            return columnVisibility.value;
        },
        get rowSelection() {
            return rowSelection.value;
        },
    },
    enableRowSelection: true,
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    onSortingChange: (updater) => {
        sorting.value = typeof updater === 'function' ? updater(sorting.value) : updater;
    },
    onColumnVisibilityChange: (updater) => {
        columnVisibility.value = typeof updater === 'function' ? updater(columnVisibility.value) : updater;
    },
    onRowSelectionChange: (updater) => {
        rowSelection.value = typeof updater === 'function' ? updater(rowSelection.value) : updater;
    },
    initialState: {
        pagination: {
            pageSize: 15,
        },
    },
});

function statusLabel(value?: string | null) {
    return value ? value.replaceAll('_', ' ') : 'Not recorded';
}

function statusVariant(status?: string | null): 'default' | 'secondary' | 'outline' | 'destructive' {
    if (['approved', 'active', 'completed', 'verified', 'credited', 'enrolled'].includes(status ?? '')) {
        return 'default';
    }

    if (['pending', 'waitlisted', 'draft', 'in_review'].includes(status ?? '')) {
        return 'secondary';
    }

    if (['cancelled', 'inactive', 'transferred'].includes(status ?? '')) {
        return 'outline';
    }

    return 'secondary';
}

function applyFilters() {
    const query = Object.fromEntries(Object.entries(filterForm.value).filter(([, value]) => value !== ''));

    router.get(index.url({ campus: campusSlug.value }, { query }), {}, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
}

function setView(view: string) {
    filterForm.value.view = view;
    applyFilters();
}

function clearFilters() {
    filterForm.value = {
        search: '',
        status: '',
        enrollment_status: '',
        term: '',
        program: '',
        section: '',
        view: 'all',
    };
    applyFilters();
}

function goToPage(url: string | null) {
    if (!url) {
        return;
    }

    router.get(url, {}, { preserveScroll: true, preserveState: true });
}

function confirmLifecycleAction() {
    if (!pendingLifecycleAction.value) {
        return;
    }

    const student = pendingLifecycleAction.value.student;

    if (pendingLifecycleAction.value.action === 'archive') {
        router.delete(destroy.url({ campus: campusSlug.value, student: student.id }), {
            preserveScroll: true,
            onFinish: () => {
                pendingLifecycleAction.value = null;
            },
        });

        return;
    }

    router.post(restore.url({ campus: campusSlug.value, student: student.id }), {}, {
        preserveScroll: true,
        onFinish: () => {
            pendingLifecycleAction.value = null;
        },
    });
}
</script>

<template>
    <Head title="Student Records" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-[1600px] flex-col gap-6 p-4 sm:p-6 lg:p-8">
            <header class="flex flex-col gap-5 border-b pb-6 xl:flex-row xl:items-start xl:justify-between">
                <div>
                    <p class="text-sm font-medium text-primary">Registrar command center</p>
                    <h1 class="mt-1 text-2xl font-semibold tracking-tight sm:text-3xl">Student records</h1>
                    <p class="mt-2 max-w-3xl text-sm text-muted-foreground">
                        Track intake readiness, document gaps, enrollment status, transfer reviews, and archived student records for the active campus.
                    </p>
                </div>

                <Button as-child class="gap-2">
                    <Link :href="createStudent.url({ campus: campusSlug })">
                        <UserRoundPlus data-icon="inline-start" />
                        New student
                    </Link>
                </Button>
            </header>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                <Card v-for="card in statCards" :key="card.label">
                    <CardHeader>
                        <div class="flex items-center justify-between gap-3">
                            <CardDescription>{{ card.label }}</CardDescription>
                            <component :is="card.icon" class="text-muted-foreground" />
                        </div>
                        <CardTitle class="text-3xl tabular-nums">{{ card.value }}</CardTitle>
                    </CardHeader>
                </Card>
            </section>

            <section class="grid gap-4 xl:grid-cols-[1fr_1fr_0.8fr]">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Profile status mix</CardTitle>
                        <CardDescription>Active student records by profile status.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="h-64">
                            <Bar :data="profileStatusChart" :options="chartOptions" />
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Enrollment lifecycle</CardTitle>
                        <CardDescription>All campus enrollments by current status.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="h-64">
                            <Bar :data="enrollmentStatusChart" :options="chartOptions" />
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Document readiness</CardTitle>
                        <CardDescription>Required document completion across active students.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="h-64">
                            <Doughnut :data="documentReadinessChart" :options="{ ...chartOptions, cutout: '70%' }" />
                        </div>
                    </CardContent>
                </Card>
            </section>

            <Card>
                <CardHeader class="gap-4">
                    <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                        <div>
                            <CardTitle>Lifecycle worklist</CardTitle>
                            <CardDescription>
                                Showing {{ students.from ?? 0 }}-{{ students.to ?? 0 }} of {{ students.total }} matching records.
                            </CardDescription>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <Button
                                v-for="view in lifecycleViews"
                                :key="view.value"
                                type="button"
                                size="sm"
                                :variant="filterForm.view === view.value ? 'default' : 'outline'"
                                @click="setView(view.value)"
                            >
                                {{ view.label }}
                                <Badge variant="secondary" class="ml-1">{{ view.count }}</Badge>
                            </Button>
                        </div>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-[1.4fr_repeat(5,minmax(0,1fr))_auto]">
                        <InputGroup>
                            <Search class="ml-2 text-muted-foreground" />
                            <InputGroupInput v-model="filterForm.search" placeholder="Search name, number, email, phone" @keyup.enter="applyFilters" />
                        </InputGroup>
                        <NativeSelect v-model="filterForm.status" class="w-full">
                            <option value="">Any profile</option>
                            <option v-for="status in options.statuses" :key="status" :value="status">{{ status }}</option>
                        </NativeSelect>
                        <NativeSelect v-model="filterForm.enrollment_status" class="w-full">
                            <option value="">Any enrollment</option>
                            <option v-for="status in options.enrollmentStatuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                        </NativeSelect>
                        <NativeSelect v-model="filterForm.term" class="w-full">
                            <option value="">Any term</option>
                            <option v-for="term in options.terms" :key="term.id" :value="String(term.id)">{{ term.name }}</option>
                        </NativeSelect>
                        <NativeSelect v-model="filterForm.program" class="w-full">
                            <option value="">Any program</option>
                            <option v-for="program in options.programs" :key="program.id" :value="String(program.id)">{{ program.code ?? program.name }}</option>
                        </NativeSelect>
                        <NativeSelect v-model="filterForm.section" class="w-full">
                            <option value="">Any section</option>
                            <option v-for="section in options.sections" :key="section.id" :value="String(section.id)">{{ section.code ?? section.name }}</option>
                        </NativeSelect>
                        <div class="flex gap-2">
                            <Button type="button" size="sm" @click="applyFilters">Apply</Button>
                            <Button type="button" size="sm" variant="ghost" @click="clearFilters">Reset</Button>
                        </div>
                    </div>
                </CardHeader>

                <CardContent>
                    <div class="overflow-hidden rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                                    <TableHead
                                        v-for="header in headerGroup.headers"
                                        :key="header.id"
                                        :class="header.column.getCanSort() ? 'cursor-pointer select-none' : ''"
                                        @click="header.column.getCanSort() && header.column.toggleSorting()"
                                    >
                                        <template v-if="header.column.id === 'select'">
                                            <Checkbox
                                                :checked="table.getIsAllPageRowsSelected() || (table.getIsSomePageRowsSelected() && 'indeterminate')"
                                                aria-label="Select all rows"
                                                @update:checked="(value) => table.toggleAllPageRowsSelected(!!value)"
                                            />
                                        </template>
                                        <div v-else class="flex items-center gap-1">
                                            <FlexRender v-if="!header.isPlaceholder" :render="header.column.columnDef.header" :props="header.getContext()" />
                                            <template v-if="header.column.getCanSort()">
                                                <ArrowUp v-if="header.column.getIsSorted() === 'asc'" />
                                                <ArrowDown v-else-if="header.column.getIsSorted() === 'desc'" />
                                                <ArrowUpDown v-else class="text-muted-foreground" />
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
                                        :data-state="row.getIsSelected() && 'selected'"
                                    >
                                        <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                                            <template v-if="cell.column.id === 'select'">
                                                <Checkbox
                                                    :checked="row.getIsSelected()"
                                                    :aria-label="`Select ${row.original.fullName}`"
                                                    @update:checked="(value) => row.toggleSelected(!!value)"
                                                />
                                            </template>
                                            <template v-else-if="cell.column.id === 'fullName'">
                                                <div class="flex items-center gap-3">
                                                    <div class="flex size-10 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary">
                                                        <UsersRound />
                                                    </div>
                                                    <div class="min-w-0">
                                                        <Link
                                                            :href="show.url({ campus: campusSlug, student: row.original.id })"
                                                            class="truncate font-medium hover:text-primary"
                                                        >
                                                            {{ row.original.fullName }}
                                                        </Link>
                                                        <p class="truncate text-xs text-muted-foreground">
                                                            {{ row.original.studentNumber ?? 'No student number' }}
                                                            <span v-if="row.original.email"> / {{ row.original.email }}</span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </template>
                                            <template v-else-if="cell.column.id === 'status'">
                                                <Badge :variant="statusVariant(row.original.status)" class="capitalize">{{ statusLabel(row.original.status) }}</Badge>
                                            </template>
                                            <template v-else-if="cell.column.id === 'enrollment'">
                                                <div class="max-w-sm">
                                                    <p class="truncate text-sm font-medium">
                                                        {{ row.original.currentEnrollment?.program ?? 'No current enrollment' }}
                                                    </p>
                                                    <p class="truncate text-xs text-muted-foreground">
                                                        {{ row.original.currentEnrollment?.period ?? 'No period' }}
                                                        <span v-if="row.original.currentEnrollment?.section"> / {{ row.original.currentEnrollment.section }}</span>
                                                    </p>
                                                    <Badge
                                                        v-if="row.original.currentEnrollment"
                                                        :variant="statusVariant(row.original.currentEnrollment.status)"
                                                        class="mt-2 capitalize"
                                                    >
                                                        {{ statusLabel(row.original.currentEnrollment.status) }}
                                                    </Badge>
                                                </div>
                                            </template>
                                            <template v-else-if="cell.column.id === 'documents'">
                                                <div class="flex flex-col gap-2">
                                                    <Badge :variant="row.original.documentSummary.ready ? 'default' : 'secondary'" class="w-fit">
                                                        <BadgeCheck v-if="row.original.documentSummary.ready" class="mr-1 size-3" />
                                                        {{ row.original.documentSummary.verifiedCount }}/{{ row.original.documentSummary.requiredCount }} verified
                                                    </Badge>
                                                    <p v-if="row.original.documentSummary.missing.length" class="text-xs text-muted-foreground">
                                                        Missing {{ row.original.documentSummary.missing.length }}
                                                    </p>
                                                </div>
                                            </template>
                                            <template v-else-if="cell.column.id === 'transfer'">
                                                <p class="text-sm">{{ row.original.transferSummary.openEvaluations }} open</p>
                                                <p class="text-xs text-muted-foreground">{{ row.original.transferSummary.creditedSubjects }} credited subjects</p>
                                            </template>
                                            <template v-else-if="cell.column.id === 'guardians'">
                                                <p class="text-sm">{{ row.original.primaryGuardian ?? 'No primary guardian' }}</p>
                                                <p class="text-xs text-muted-foreground">{{ row.original.guardiansCount }} linked</p>
                                            </template>
                                            <template v-else-if="cell.column.id === 'actions'">
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger as-child>
                                                        <Button variant="ghost" size="icon">
                                                            <MoreHorizontal />
                                                            <span class="sr-only">Open row actions</span>
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end" class="w-48">
                                                        <DropdownMenuGroup>
                                                            <DropdownMenuItem as-child>
                                                                <Link :href="show.url({ campus: campusSlug, student: row.original.id })">View profile</Link>
                                                            </DropdownMenuItem>
                                                            <DropdownMenuItem v-if="row.original.can.edit" as-child>
                                                                <Link :href="edit.url({ campus: campusSlug, student: row.original.id })">Edit record</Link>
                                                            </DropdownMenuItem>
                                                        </DropdownMenuGroup>
                                                        <DropdownMenuSeparator />
                                                        <DropdownMenuItem
                                                            v-if="row.original.can.delete"
                                                            class="text-destructive focus:text-destructive"
                                                            @click="pendingLifecycleAction = { student: row.original, action: 'archive' }"
                                                        >
                                                            Archive record
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem
                                                            v-if="row.original.can.restore"
                                                            @click="pendingLifecycleAction = { student: row.original, action: 'restore' }"
                                                        >
                                                            Restore record
                                                        </DropdownMenuItem>
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </template>
                                            <template v-else>
                                                <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                                            </template>
                                        </TableCell>
                                    </TableRow>
                                </template>
                                <template v-else>
                                    <TableRow>
                                        <TableCell :colspan="columns.length" class="h-64">
                                            <Empty>
                                                <EmptyHeader>
                                                    <EmptyMedia variant="icon">
                                                        <UsersRound />
                                                    </EmptyMedia>
                                                    <EmptyTitle>No student records found</EmptyTitle>
                                                    <EmptyDescription>Adjust the filters or create a new student record.</EmptyDescription>
                                                </EmptyHeader>
                                                <EmptyContent>
                                                    <Button as-child>
                                                        <Link :href="createStudent.url({ campus: campusSlug })">New student</Link>
                                                    </Button>
                                                </EmptyContent>
                                            </Empty>
                                        </TableCell>
                                    </TableRow>
                                </template>
                            </TableBody>
                        </Table>
                    </div>

                    <div class="mt-4 flex flex-col items-center justify-between gap-4 sm:flex-row">
                        <p class="text-sm text-muted-foreground">
                            {{ Object.keys(rowSelection).length }} selected / page {{ students.current_page }} of {{ students.last_page }}
                        </p>
                        <div class="flex items-center gap-1">
                            <Button variant="outline" size="icon" :disabled="students.current_page <= 1" @click="goToPage(students.links[0]?.url ?? null)">
                                <ChevronsLeft />
                            </Button>
                            <Button
                                variant="outline"
                                size="icon"
                                :disabled="students.current_page <= 1"
                                @click="goToPage(students.links.find((link) => link.label === 'Previous')?.url ?? null)"
                            >
                                <ChevronLeft />
                            </Button>
                            <Button
                                v-for="link in students.links.filter((link) => !Number.isNaN(Number(link.label)))"
                                :key="link.label"
                                size="sm"
                                :variant="link.active ? 'default' : 'outline'"
                                :disabled="!link.url"
                                class="min-w-8"
                                @click="goToPage(link.url)"
                            >
                                {{ link.label }}
                            </Button>
                            <Button
                                variant="outline"
                                size="icon"
                                :disabled="students.current_page >= students.last_page"
                                @click="goToPage(students.links.find((link) => link.label === 'Next')?.url ?? null)"
                            >
                                <ChevronRight />
                            </Button>
                            <Button
                                variant="outline"
                                size="icon"
                                :disabled="students.current_page >= students.last_page"
                                @click="goToPage(students.links[students.links.length - 1]?.url ?? null)"
                            >
                                <ChevronsRight />
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <AlertDialog :open="!!pendingLifecycleAction" @update:open="(value) => !value && (pendingLifecycleAction = null)">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        {{ pendingLifecycleAction?.action === 'restore' ? 'Restore student record?' : 'Archive student record?' }}
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        {{
                            pendingLifecycleAction?.action === 'restore'
                                ? 'This returns the student to the active records list while preserving their existing lifecycle history.'
                                : 'This soft deletes the student record from active lists while preserving enrollment, document, guardian, and transfer history.'
                        }}
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction @click="confirmLifecycleAction">
                        <ArchiveRestore v-if="pendingLifecycleAction?.action === 'restore'" class="mr-2 size-4" />
                        {{ pendingLifecycleAction?.action === 'restore' ? 'Restore' : 'Archive' }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
