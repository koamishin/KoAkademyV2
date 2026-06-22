<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowRight,
    BadgeCheck,
    ClipboardCheck,
    FileWarning,
    GraduationCap,
    Search,
    ShieldCheck,
    UserRoundPlus,
    UsersRound,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Progress } from '@/components/ui/progress';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, show, store } from '@/routes/admin/students';
import type { AppPageProps, BreadcrumbItem } from '@/types';

type StudentRow = {
    id: number;
    fullName: string;
    studentNumber?: string | null;
    email?: string | null;
    phone?: string | null;
    status: string;
    enrollmentsCount: number;
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
};

type Option = { id: number; name: string; code?: string | null };
type SelectOption = { value: string; label: string; required?: boolean };
type IntakeDocument = {
    document_type: string;
    file: File | null;
    issued_on: string;
    expires_on: string;
    notes: string;
};

const props = defineProps<{
    students: {
        data: StudentRow[];
        links: { url: string | null; label: string; active: boolean }[];
        current_page: number;
        last_page: number;
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: Record<string, string | null>;
    summary: {
        total: number;
        activeEnrollments: number;
        waiting: number;
        documentGaps: number;
        transferReviews: number;
    };
    options: {
        statuses: string[];
        enrollmentStatuses: SelectOption[];
        classifications: SelectOption[];
        incomeBrackets: Record<string, string>;
        documentTypes: SelectOption[];
        programs: (Option & { curricula: Option[] })[];
        sections: (Option & { programId: number; termId: number })[];
        terms: Option[];
    };
}>();

const page = usePage<AppPageProps>();
const campusSlug = computed(() => page.props.currentCampus!.slug);
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Student Records' }];
const intakeOpen = ref(false);
const intakeStep = ref('identity');

const intakeSteps = [
    { key: 'identity', label: 'Identity' },
    { key: 'contact', label: 'Contacts' },
    { key: 'guardian', label: 'Guardians' },
    { key: 'academic', label: 'Academic' },
    { key: 'compliance', label: 'Reporting' },
    { key: 'documents', label: 'Documents' },
    { key: 'review', label: 'Review' },
];

const views = [
    { value: 'all', label: 'All students', count: props.summary.total },
    { value: 'document_gaps', label: 'Document gaps', count: props.summary.documentGaps },
    { value: 'transfer_reviews', label: 'Transfer reviews', count: props.summary.transferReviews },
];

const filterForm = ref({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    enrollment_status: props.filters.enrollment_status ?? '',
    term: props.filters.term ?? '',
    program: props.filters.program ?? '',
    section: props.filters.section ?? '',
    view: props.filters.view ?? 'all',
});

const createForm = useForm({
    first_name: '',
    middle_name: '',
    last_name: '',
    suffix: '',
    birth_date: '',
    sex: '',
    email: '',
    phone: '',
    address: '',
    status: 'active',
    student_number: '',
    metadata: {
        learner_reference_number: '',
        previous_school: '',
        emergency_contact: '',
    },
    profile: {
        psa_birth_certificate_number: '',
        learner_reference_number: '',
        nationality: 'Filipino',
        civil_status: '',
        religion: '',
        mother_tongue: '',
        is_indigenous_people: false,
        indigenous_community: '',
        has_disability: false,
        disability_type: '',
        is_4ps_beneficiary: false,
        four_ps_household_id: '',
        annual_family_income_bracket: '',
        household_gross_income: '',
        has_government_subsidy: false,
        subsidy_program: '',
        emergency_contact_name: '',
        emergency_contact_relationship: '',
        emergency_contact_phone: '',
        current_address: {
            house: '',
            barangay: '',
            city: '',
            province: '',
            country: 'Philippines',
            zip_code: '',
        },
        permanent_address: {
            house: '',
            barangay: '',
            city: '',
            province: '',
            country: 'Philippines',
            zip_code: '',
        },
        previous_school_name: '',
        previous_school_address: '',
        previous_school_type: '',
        last_grade_level_completed: '',
        last_school_year_attended: '',
        senior_high_school_strand: '',
        college_year_level: '',
        reporting_flags: {
            intake_classification: 'new',
            intended_program_id: '',
            intended_curriculum_id: '',
        },
    },
    guardians: [
        {
            first_name: '',
            last_name: '',
            email: '',
            phone: '',
            relationship: '',
            is_primary: true,
            has_portal_access: true,
        },
    ],
    documents: [
        { document_type: 'student_photo', file: null, issued_on: '', expires_on: '', notes: '' },
        { document_type: 'psa_birth_certificate', file: null, issued_on: '', expires_on: '', notes: '' },
        { document_type: 'form_138', file: null, issued_on: '', expires_on: '', notes: '' },
        { document_type: 'transfer_credential', file: null, issued_on: '', expires_on: '', notes: '' },
    ] as IntakeDocument[],
});

const selectedProgram = computed(() =>
    props.options.programs.find((program) => String(program.id) === createForm.profile.reporting_flags.intended_program_id),
);

const intakeProgress = computed(() => {
    const currentIndex = intakeSteps.findIndex((step) => step.key === intakeStep.value);

    return Math.round(((currentIndex + 1) / intakeSteps.length) * 100);
});

const classificationLabel = computed(() => {
    const value = createForm.profile.reporting_flags.intake_classification;

    if (value === 'continuing') {
        return 'Old / continuing';
    }

    return props.options.classifications.find((classification) => classification.value === value)?.label ?? 'New';
});

const statusClass = (status: string) => {
    if (['approved', 'active', 'completed', 'verified', 'credited'].includes(status)) {
        return 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300';
    }

    if (['pending', 'waitlisted', 'draft', 'in_review'].includes(status)) {
        return 'bg-amber-500/10 text-amber-700 dark:text-amber-300';
    }

    return 'bg-muted text-muted-foreground';
};

function applyFilters() {
    router.get(index.url({ campus: campusSlug.value }), filterForm.value, {
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

function addGuardian() {
    createForm.guardians.push({
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        relationship: '',
        is_primary: createForm.guardians.length === 0,
        has_portal_access: true,
    });
}

function removeGuardian(index: number) {
    createForm.guardians.splice(index, 1);
}

function addDocument() {
    createForm.documents.push({ document_type: 'custom', file: null, issued_on: '', expires_on: '', notes: '' });
}

function removeDocument(index: number) {
    createForm.documents.splice(index, 1);
}

function setDocumentFile(event: Event, index: number) {
    const input = event.target as HTMLInputElement;
    createForm.documents[index].file = input.files?.[0] ?? null;
}

function copyCurrentAddress() {
    createForm.profile.permanent_address = { ...createForm.profile.current_address };
}

function submitCreate() {
    createForm.metadata.learner_reference_number = createForm.profile.learner_reference_number;
    createForm.metadata.previous_school = createForm.profile.previous_school_name;
    createForm.metadata.emergency_contact = createForm.profile.emergency_contact_phone;

    createForm.post(store.url({ campus: campusSlug.value }), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            intakeOpen.value = false;
            intakeStep.value = 'identity';
            createForm.reset();
        },
    });
}
</script>

<template>
    <Head title="Student Records" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-[1600px] flex-col gap-6 p-4 sm:p-6 lg:p-8">
            <header class="grid gap-5 border-b pb-6 xl:grid-cols-[1fr_auto]">
                <div>
                    <p class="text-sm font-medium text-primary">Registrar command center</p>
                    <h1 class="mt-1 text-2xl font-semibold tracking-tight sm:text-3xl">Student records</h1>
                    <p class="mt-2 max-w-3xl text-sm text-muted-foreground">
                        Intake, compliance readiness, enrollment classification, and transfer-credit follow-up for the active campus.
                    </p>
                </div>

                <Sheet v-model:open="intakeOpen">
                    <SheetTrigger as-child>
                        <Button class="gap-2">
                            <UserRoundPlus class="size-4" />
                            Enroll student
                        </Button>
                    </SheetTrigger>
                    <SheetContent class="w-full overflow-y-auto sm:max-w-5xl">
                        <SheetHeader class="border-b px-6 py-5">
                            <SheetTitle>Enterprise student intake</SheetTitle>
                            <SheetDescription>
                                Create the student profile, compliance baseline, guardians, and first document packet.
                            </SheetDescription>
                        </SheetHeader>

                        <form class="grid gap-6 px-6 pb-8" @submit.prevent="submitCreate">
                            <div class="sticky top-0 z-10 grid gap-3 bg-background/95 py-4 backdrop-blur">
                                <div class="flex flex-wrap gap-2">
                                    <Button
                                        v-for="step in intakeSteps"
                                        :key="step.key"
                                        type="button"
                                        size="sm"
                                        :variant="intakeStep === step.key ? 'default' : 'outline'"
                                        @click="intakeStep = step.key"
                                    >
                                        {{ step.label }}
                                    </Button>
                                </div>
                                <Progress :model-value="intakeProgress" class="h-2" />
                            </div>

                            <section v-if="intakeStep === 'identity'" class="grid gap-5">
                                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                    <div class="grid gap-2">
                                        <Label for="first-name">First name</Label>
                                        <Input id="first-name" v-model="createForm.first_name" />
                                        <p v-if="createForm.errors.first_name" class="text-sm text-destructive">{{ createForm.errors.first_name }}</p>
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="middle-name">Middle name</Label>
                                        <Input id="middle-name" v-model="createForm.middle_name" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="last-name">Last name</Label>
                                        <Input id="last-name" v-model="createForm.last_name" />
                                        <p v-if="createForm.errors.last_name" class="text-sm text-destructive">{{ createForm.errors.last_name }}</p>
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="suffix">Suffix</Label>
                                        <Input id="suffix" v-model="createForm.suffix" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="student-number">Student number</Label>
                                        <Input id="student-number" v-model="createForm.student_number" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="birth-date">Birth date</Label>
                                        <Input id="birth-date" v-model="createForm.birth_date" type="date" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="sex">Sex</Label>
                                        <select id="sex" v-model="createForm.sex" class="h-9 rounded-md border bg-background px-3 text-sm">
                                            <option value="">Not recorded</option>
                                            <option value="female">Female</option>
                                            <option value="male">Male</option>
                                            <option value="self_described">Self-described</option>
                                        </select>
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="status">Profile status</Label>
                                        <select id="status" v-model="createForm.status" class="h-9 rounded-md border bg-background px-3 text-sm">
                                            <option v-for="status in options.statuses" :key="status" :value="status">{{ status }}</option>
                                        </select>
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="psa">PSA birth certificate no.</Label>
                                        <Input id="psa" v-model="createForm.profile.psa_birth_certificate_number" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="lrn">Learner reference number</Label>
                                        <Input id="lrn" v-model="createForm.profile.learner_reference_number" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="nationality">Nationality</Label>
                                        <Input id="nationality" v-model="createForm.profile.nationality" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="civil-status">Civil status</Label>
                                        <Input id="civil-status" v-model="createForm.profile.civil_status" />
                                    </div>
                                </div>
                            </section>

                            <section v-if="intakeStep === 'contact'" class="grid gap-6">
                                <div class="grid gap-4 md:grid-cols-2">
                                    <div class="grid gap-2">
                                        <Label for="email">Email</Label>
                                        <Input id="email" v-model="createForm.email" type="email" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="phone">Phone</Label>
                                        <Input id="phone" v-model="createForm.phone" />
                                    </div>
                                    <div class="grid gap-2 md:col-span-2">
                                        <Label for="address">General address</Label>
                                        <Input id="address" v-model="createForm.address" />
                                    </div>
                                </div>

                                <div class="grid gap-4 xl:grid-cols-2">
                                    <article class="grid gap-3 rounded-lg border p-4">
                                        <div class="flex items-center justify-between gap-3">
                                            <h2 class="font-semibold">Current address</h2>
                                        </div>
                                        <div class="grid gap-3 md:grid-cols-2">
                                            <Input v-model="createForm.profile.current_address.house" placeholder="House / street / sitio" />
                                            <Input v-model="createForm.profile.current_address.barangay" placeholder="Barangay" />
                                            <Input v-model="createForm.profile.current_address.city" placeholder="Municipality / city" />
                                            <Input v-model="createForm.profile.current_address.province" placeholder="Province" />
                                            <Input v-model="createForm.profile.current_address.country" placeholder="Country" />
                                            <Input v-model="createForm.profile.current_address.zip_code" placeholder="ZIP code" />
                                        </div>
                                    </article>

                                    <article class="grid gap-3 rounded-lg border p-4">
                                        <div class="flex items-center justify-between gap-3">
                                            <h2 class="font-semibold">Permanent address</h2>
                                            <Button type="button" size="sm" variant="outline" @click="copyCurrentAddress">Copy current</Button>
                                        </div>
                                        <div class="grid gap-3 md:grid-cols-2">
                                            <Input v-model="createForm.profile.permanent_address.house" placeholder="House / street / sitio" />
                                            <Input v-model="createForm.profile.permanent_address.barangay" placeholder="Barangay" />
                                            <Input v-model="createForm.profile.permanent_address.city" placeholder="Municipality / city" />
                                            <Input v-model="createForm.profile.permanent_address.province" placeholder="Province" />
                                            <Input v-model="createForm.profile.permanent_address.country" placeholder="Country" />
                                            <Input v-model="createForm.profile.permanent_address.zip_code" placeholder="ZIP code" />
                                        </div>
                                    </article>
                                </div>

                                <article class="grid gap-3 rounded-lg border p-4">
                                    <h2 class="font-semibold">Emergency contact</h2>
                                    <div class="grid gap-3 md:grid-cols-3">
                                        <Input v-model="createForm.profile.emergency_contact_name" placeholder="Full name" />
                                        <Input v-model="createForm.profile.emergency_contact_relationship" placeholder="Relationship" />
                                        <Input v-model="createForm.profile.emergency_contact_phone" placeholder="Phone" />
                                    </div>
                                </article>
                            </section>

                            <section v-if="intakeStep === 'guardian'" class="grid gap-4">
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <h2 class="font-semibold">Parents and guardians</h2>
                                        <p class="text-sm text-muted-foreground">Capture at least the primary contact for permissions and portal access.</p>
                                    </div>
                                    <Button type="button" variant="outline" size="sm" @click="addGuardian">Add guardian</Button>
                                </div>

                                <article
                                    v-for="(guardian, guardianIndex) in createForm.guardians"
                                    :key="guardianIndex"
                                    class="grid gap-3 rounded-lg border bg-muted/20 p-4 md:grid-cols-3"
                                >
                                    <Input v-model="guardian.first_name" placeholder="First name" />
                                    <Input v-model="guardian.last_name" placeholder="Last name" />
                                    <Input v-model="guardian.relationship" placeholder="Relationship" />
                                    <Input v-model="guardian.phone" placeholder="Phone" />
                                    <Input v-model="guardian.email" type="email" placeholder="Email" />
                                    <div class="flex items-center justify-between gap-3">
                                        <label class="flex items-center gap-2 text-sm">
                                            <input v-model="guardian.is_primary" type="checkbox" class="size-4" />
                                            Primary
                                        </label>
                                        <label class="flex items-center gap-2 text-sm">
                                            <input v-model="guardian.has_portal_access" type="checkbox" class="size-4" />
                                            Portal
                                        </label>
                                        <Button type="button" variant="ghost" size="sm" @click="removeGuardian(guardianIndex)">Remove</Button>
                                    </div>
                                </article>
                            </section>

                            <section v-if="intakeStep === 'academic'" class="grid gap-5">
                                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                    <div class="grid gap-2">
                                        <Label>Student type</Label>
                                        <select v-model="createForm.profile.reporting_flags.intake_classification" class="h-9 rounded-md border bg-background px-3 text-sm">
                                            <option v-for="classification in options.classifications" :key="classification.value" :value="classification.value">
                                                {{ classification.value === 'continuing' ? 'Old / continuing' : classification.label }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="grid gap-2">
                                        <Label>Program / course</Label>
                                        <select v-model="createForm.profile.reporting_flags.intended_program_id" class="h-9 rounded-md border bg-background px-3 text-sm">
                                            <option value="">Not selected</option>
                                            <option v-for="program in options.programs" :key="program.id" :value="String(program.id)">
                                                {{ program.code ?? program.name }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="grid gap-2">
                                        <Label>Curriculum</Label>
                                        <select v-model="createForm.profile.reporting_flags.intended_curriculum_id" class="h-9 rounded-md border bg-background px-3 text-sm">
                                            <option value="">Not selected</option>
                                            <option v-for="curriculum in selectedProgram?.curricula ?? []" :key="curriculum.id" :value="String(curriculum.id)">
                                                {{ curriculum.code ?? curriculum.name }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="grid gap-2">
                                        <Label>College year level</Label>
                                        <Input v-model="createForm.profile.college_year_level" type="number" min="1" max="20" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label>Last grade/year completed</Label>
                                        <Input v-model="createForm.profile.last_grade_level_completed" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label>Last school year attended</Label>
                                        <Input v-model="createForm.profile.last_school_year_attended" placeholder="2025-2026" />
                                    </div>
                                </div>

                                <article class="grid gap-3 rounded-lg border p-4">
                                    <h2 class="font-semibold">Previous school</h2>
                                    <div class="grid gap-3 md:grid-cols-2">
                                        <Input v-model="createForm.profile.previous_school_name" placeholder="School name" />
                                        <Input v-model="createForm.profile.previous_school_type" placeholder="Public, private, SUC, LUC, HEI" />
                                        <Input v-model="createForm.profile.previous_school_address" class="md:col-span-2" placeholder="School address" />
                                        <Input v-model="createForm.profile.senior_high_school_strand" placeholder="SHS strand, if applicable" />
                                    </div>
                                </article>
                            </section>

                            <section v-if="intakeStep === 'compliance'" class="grid gap-5">
                                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                    <div class="grid gap-2">
                                        <Label>Annual family income bracket</Label>
                                        <select v-model="createForm.profile.annual_family_income_bracket" class="h-9 rounded-md border bg-background px-3 text-sm">
                                            <option value="">Not recorded</option>
                                            <option v-for="(label, value) in options.incomeBrackets" :key="value" :value="value">{{ label }}</option>
                                        </select>
                                    </div>
                                    <div class="grid gap-2">
                                        <Label>Household gross income</Label>
                                        <Input v-model="createForm.profile.household_gross_income" type="number" min="0" step="0.01" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label>Subsidy / scholarship program</Label>
                                        <Input v-model="createForm.profile.subsidy_program" placeholder="TES, CSP, FHE, internal scholarship" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label>Religion</Label>
                                        <Input v-model="createForm.profile.religion" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label>Mother tongue</Label>
                                        <Input v-model="createForm.profile.mother_tongue" />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label>Disability type</Label>
                                        <Input v-model="createForm.profile.disability_type" :disabled="!createForm.profile.has_disability" />
                                    </div>
                                </div>

                                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                                    <label class="flex items-center gap-2 rounded-lg border p-3 text-sm">
                                        <input v-model="createForm.profile.is_4ps_beneficiary" type="checkbox" class="size-4" />
                                        4Ps beneficiary
                                    </label>
                                    <label class="flex items-center gap-2 rounded-lg border p-3 text-sm">
                                        <input v-model="createForm.profile.has_government_subsidy" type="checkbox" class="size-4" />
                                        Government subsidy
                                    </label>
                                    <label class="flex items-center gap-2 rounded-lg border p-3 text-sm">
                                        <input v-model="createForm.profile.is_indigenous_people" type="checkbox" class="size-4" />
                                        IP / ICC learner
                                    </label>
                                    <label class="flex items-center gap-2 rounded-lg border p-3 text-sm">
                                        <input v-model="createForm.profile.has_disability" type="checkbox" class="size-4" />
                                        Learner with disability
                                    </label>
                                </div>

                                <div class="grid gap-4 md:grid-cols-2">
                                    <Input v-model="createForm.profile.four_ps_household_id" placeholder="4Ps household ID" />
                                    <Input v-model="createForm.profile.indigenous_community" placeholder="IP / ICC community" />
                                </div>
                            </section>

                            <section v-if="intakeStep === 'documents'" class="grid gap-4">
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <h2 class="font-semibold">Photo and credentials</h2>
                                        <p class="text-sm text-muted-foreground">PDF, JPG, JPEG, or PNG up to 10 MB per file.</p>
                                    </div>
                                    <Button type="button" variant="outline" size="sm" @click="addDocument">Add document</Button>
                                </div>

                                <article
                                    v-for="(document, docIndex) in createForm.documents"
                                    :key="docIndex"
                                    class="grid gap-3 rounded-lg border p-4 xl:grid-cols-[1fr_1.2fr_0.8fr_0.8fr_auto]"
                                >
                                    <select v-model="document.document_type" class="h-9 rounded-md border bg-background px-3 text-sm">
                                        <option v-for="type in options.documentTypes" :key="type.value" :value="type.value">
                                            {{ type.label }}{{ type.required ? ' *' : '' }}
                                        </option>
                                    </select>
                                    <Input type="file" accept=".jpg,.jpeg,.png,.pdf" @change="setDocumentFile($event, docIndex)" />
                                    <Input v-model="document.issued_on" type="date" />
                                    <Input v-model="document.expires_on" type="date" />
                                    <Button type="button" variant="ghost" size="sm" @click="removeDocument(docIndex)">Remove</Button>
                                    <Textarea v-model="document.notes" class="xl:col-span-5" placeholder="Review notes, source, or original-copy remarks" />
                                </article>

                                <progress v-if="createForm.progress" :value="createForm.progress.percentage" max="100" class="h-2 w-full">
                                    {{ createForm.progress.percentage }}%
                                </progress>
                            </section>

                            <section v-if="intakeStep === 'review'" class="grid gap-4">
                                <article class="rounded-lg border p-5">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="text-sm text-muted-foreground">Student</p>
                                            <h2 class="mt-1 text-xl font-semibold">
                                                {{ createForm.first_name || 'First' }} {{ createForm.last_name || 'Last' }}
                                            </h2>
                                            <p class="text-sm text-muted-foreground">
                                                {{ classificationLabel }} / {{ selectedProgram?.code ?? selectedProgram?.name ?? 'No program selected' }}
                                            </p>
                                        </div>
                                        <Badge :class="statusClass(createForm.status)" class="capitalize">{{ createForm.status }}</Badge>
                                    </div>
                                </article>
                                <div class="grid gap-4 md:grid-cols-3">
                                    <article class="rounded-lg border p-4">
                                        <p class="text-sm text-muted-foreground">Documents attached</p>
                                        <p class="mt-2 text-2xl font-semibold">{{ createForm.documents.filter((document) => document.file).length }}</p>
                                    </article>
                                    <article class="rounded-lg border p-4">
                                        <p class="text-sm text-muted-foreground">Guardians</p>
                                        <p class="mt-2 text-2xl font-semibold">{{ createForm.guardians.length }}</p>
                                    </article>
                                    <article class="rounded-lg border p-4">
                                        <p class="text-sm text-muted-foreground">Income bracket</p>
                                        <p class="mt-2 text-sm font-semibold">
                                            {{ options.incomeBrackets[createForm.profile.annual_family_income_bracket] ?? 'Not recorded' }}
                                        </p>
                                    </article>
                                </div>
                            </section>

                            <footer class="flex flex-wrap items-center justify-between gap-3 border-t pt-5">
                                <p class="text-sm text-muted-foreground">
                                    The registrar can create the actual enrollment from the student profile after intake.
                                </p>
                                <Button type="submit" class="gap-2" :disabled="createForm.processing">
                                    Create intake record
                                    <ArrowRight class="size-4" />
                                </Button>
                            </footer>
                        </form>
                    </SheetContent>
                </Sheet>
            </header>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <article class="rounded-lg border bg-card p-5 shadow-sm">
                    <UsersRound class="size-5 text-primary" />
                    <p class="mt-4 text-3xl font-semibold">{{ summary.total }}</p>
                    <p class="text-sm text-muted-foreground">Active records</p>
                </article>
                <article class="rounded-lg border bg-card p-5 shadow-sm">
                    <ClipboardCheck class="size-5 text-primary" />
                    <p class="mt-4 text-3xl font-semibold">{{ summary.activeEnrollments }}</p>
                    <p class="text-sm text-muted-foreground">Pending or approved</p>
                </article>
                <article class="rounded-lg border bg-card p-5 shadow-sm">
                    <AlertTriangle class="size-5 text-amber-600" />
                    <p class="mt-4 text-3xl font-semibold">{{ summary.waiting }}</p>
                    <p class="text-sm text-muted-foreground">Draft or waitlisted</p>
                </article>
                <article class="rounded-lg border bg-card p-5 shadow-sm">
                    <FileWarning class="size-5 text-amber-600" />
                    <p class="mt-4 text-3xl font-semibold">{{ summary.documentGaps }}</p>
                    <p class="text-sm text-muted-foreground">Document gaps</p>
                </article>
                <article class="rounded-lg border bg-card p-5 shadow-sm">
                    <GraduationCap class="size-5 text-primary" />
                    <p class="mt-4 text-3xl font-semibold">{{ summary.transferReviews }}</p>
                    <p class="text-sm text-muted-foreground">Transfer reviews</p>
                </article>
            </section>

            <section class="grid gap-4 rounded-lg border bg-card p-4 shadow-sm">
                <div class="flex flex-wrap gap-2">
                    <Button
                        v-for="view in views"
                        :key="view.value"
                        type="button"
                        size="sm"
                        :variant="filterForm.view === view.value ? 'default' : 'outline'"
                        @click="setView(view.value)"
                    >
                        {{ view.label }}
                        <span class="ml-1 text-xs opacity-70">{{ view.count }}</span>
                    </Button>
                </div>

                <div class="grid gap-3 md:grid-cols-[1.4fr_repeat(5,minmax(0,1fr))_auto]">
                    <div class="relative">
                        <Search class="pointer-events-none absolute left-3 top-2.5 size-4 text-muted-foreground" />
                        <Input v-model="filterForm.search" class="pl-9" placeholder="Search name, number, email, phone" @keyup.enter="applyFilters" />
                    </div>
                    <select v-model="filterForm.status" class="h-9 rounded-md border bg-background px-3 text-sm">
                        <option value="">Any profile</option>
                        <option v-for="status in options.statuses" :key="status" :value="status">{{ status }}</option>
                    </select>
                    <select v-model="filterForm.enrollment_status" class="h-9 rounded-md border bg-background px-3 text-sm">
                        <option value="">Any enrollment</option>
                        <option v-for="status in options.enrollmentStatuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                    </select>
                    <select v-model="filterForm.term" class="h-9 rounded-md border bg-background px-3 text-sm">
                        <option value="">Any term</option>
                        <option v-for="term in options.terms" :key="term.id" :value="String(term.id)">{{ term.name }}</option>
                    </select>
                    <select v-model="filterForm.program" class="h-9 rounded-md border bg-background px-3 text-sm">
                        <option value="">Any program</option>
                        <option v-for="program in options.programs" :key="program.id" :value="String(program.id)">{{ program.code ?? program.name }}</option>
                    </select>
                    <select v-model="filterForm.section" class="h-9 rounded-md border bg-background px-3 text-sm">
                        <option value="">Any section</option>
                        <option v-for="section in options.sections" :key="section.id" :value="String(section.id)">{{ section.code ?? section.name }}</option>
                    </select>
                    <div class="flex gap-2">
                        <Button type="button" size="sm" @click="applyFilters">Apply</Button>
                        <Button type="button" size="sm" variant="ghost" @click="clearFilters">Reset</Button>
                    </div>
                </div>
            </section>

            <section class="overflow-hidden rounded-lg border bg-card shadow-sm">
                <article
                    v-for="student in students.data"
                    :key="student.id"
                    class="grid gap-4 border-b p-5 last:border-b-0 xl:grid-cols-[1.1fr_1.2fr_1fr_auto]"
                >
                    <div class="flex gap-3">
                        <div class="flex size-11 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <UsersRound class="size-5" />
                        </div>
                        <div>
                            <Link :href="show.url({ campus: campusSlug, student: student.id })" class="font-medium tracking-tight hover:text-primary">
                                {{ student.fullName }}
                            </Link>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{ student.studentNumber ?? 'No student number' }}
                                <span v-if="student.email"> / {{ student.email }}</span>
                            </p>
                        </div>
                    </div>

                    <div class="text-sm text-muted-foreground">
                        <p class="font-medium text-foreground">{{ student.currentEnrollment?.program ?? 'No current enrollment' }}</p>
                        <p>
                            {{ student.currentEnrollment?.period ?? 'No period' }}
                            <span v-if="student.currentEnrollment?.section"> / {{ student.currentEnrollment.section }}</span>
                            <span v-if="student.currentEnrollment?.classification"> / {{ student.currentEnrollment.classification.replace('_', ' ') }}</span>
                        </p>
                    </div>

                    <div class="grid gap-2 text-sm">
                        <div class="flex items-center gap-2">
                            <ShieldCheck class="size-4" :class="student.documentSummary.ready ? 'text-emerald-600' : 'text-amber-600'" />
                            <span>
                                {{ student.documentSummary.verifiedCount }}/{{ student.documentSummary.requiredCount }} required docs verified
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <GraduationCap class="size-4 text-muted-foreground" />
                            <span>
                                {{ student.transferSummary.openEvaluations }} transfer reviews /
                                {{ student.transferSummary.creditedSubjects }} credited subjects
                            </span>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 xl:justify-end">
                        <Badge :class="statusClass(student.status)" class="capitalize">{{ student.status }}</Badge>
                        <Badge v-if="student.currentEnrollment" :class="statusClass(student.currentEnrollment.status)" class="capitalize">
                            {{ student.currentEnrollment.status.replace('_', ' ') }}
                        </Badge>
                        <Badge v-if="student.documentSummary.ready" class="bg-emerald-500/10 text-emerald-700 dark:text-emerald-300">
                            <BadgeCheck class="mr-1 size-3" />
                            Ready
                        </Badge>
                    </div>
                </article>
                <p v-if="students.data.length === 0" class="p-10 text-center text-sm text-muted-foreground">
                    No student records match the current filters.
                </p>
            </section>

            <nav v-if="students.last_page > 1" class="flex flex-wrap gap-2">
                <Link
                    v-for="link in students.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    class="rounded-md border px-3 py-2 text-sm"
                    :class="[
                        link.active ? 'border-primary bg-primary text-primary-foreground' : 'bg-background',
                        !link.url ? 'pointer-events-none opacity-50' : '',
                    ]"
                >
                    <span v-html="link.label"></span>
                </Link>
            </nav>
        </div>
    </AppLayout>
</template>
