<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    Check,
    ClipboardCheck,
    FileUp,
    GraduationCap,
    Info,
    Plus,
    Save,
    Trash2,
    UserRound,
    UsersRound,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldDescription, FieldError, FieldGroup, FieldLabel, FieldLegend, FieldSet } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Item, ItemContent, ItemDescription, ItemGroup, ItemMedia, ItemTitle } from '@/components/ui/item';
import { Progress } from '@/components/ui/progress';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Stepper, StepperIndicator, StepperItem, StepperSeparator, StepperTitle, StepperTrigger } from '@/components/ui/stepper';
import { Textarea } from '@/components/ui/textarea';
import type { AppPageProps } from '@/types';

type AcademicLevel = { id: number; name: string; code?: string | null; category?: string | null };
type Option = { id: number; name: string; code?: string | null };
type ProgramOption = Option & { curricula: Option[]; educationLevel?: AcademicLevel | null };
type SelectOption = { value: string; label: string; required?: boolean };
type SectionOption = { id: number; programId?: number | null; name: string; code?: string | null; yearLevel?: number | null };
type AcademicStyle = { level: 'college' | 'high_school' | 'elementary'; label: string };
type Address = {
    house: string;
    barangay: string;
    city: string;
    province: string;
    country: string;
    zip_code: string;
};
type GuardianForm = {
    id?: number | null;
    first_name: string;
    last_name: string;
    email: string;
    phone: string;
    relationship: string;
    is_primary: boolean;
    has_portal_access: boolean;
};
type IntakeDocument = {
    document_type: string;
    file: File | null;
    issued_on: string;
    expires_on: string;
    notes: string;
};
type StudentFormData = {
    first_name: string;
    middle_name: string;
    last_name: string;
    suffix: string;
    birth_date: string;
    sex: string;
    email: string;
    phone: string;
    address: string;
    status: string;
    student_number: string;
    metadata: {
        learner_reference_number: string;
        previous_school: string;
        emergency_contact: string;
    };
    profile: {
        psa_birth_certificate_number: string;
        learner_reference_number: string;
        nationality: string;
        civil_status: string;
        religion: string;
        mother_tongue: string;
        is_indigenous_people: boolean;
        indigenous_community: string;
        has_disability: boolean;
        disability_type: string;
        is_4ps_beneficiary: boolean;
        four_ps_household_id: string;
        annual_family_income_bracket: string;
        household_gross_income: string;
        has_government_subsidy: boolean;
        subsidy_program: string;
        emergency_contact_name: string;
        emergency_contact_relationship: string;
        emergency_contact_phone: string;
        current_address: Address;
        permanent_address: Address;
        previous_school_name: string;
        previous_school_address: string;
        previous_school_type: string;
        last_grade_level_completed: string;
        last_school_year_attended: string;
        senior_high_school_strand: string;
        college_year_level: string;
        reporting_flags: {
            intake_classification: string;
            intended_program_id: string;
            intended_curriculum_id: string;
        };
    };
    guardians: GuardianForm[];
    documents: IntakeDocument[];
};
type Options = {
    academicStyle?: AcademicStyle;
    statuses: string[];
    classifications: SelectOption[];
    incomeBrackets: Record<string, string>;
    documentTypes: SelectOption[];
    programs: ProgramOption[];
    sections?: SectionOption[];
};

const props = defineProps<{
    options: Options;
    submitUrl: string;
    method: 'post' | 'patch';
    mode: 'create' | 'edit';
    initial?: Partial<StudentFormData>;
    submitLabel: string;
}>();

const steps = [
    { value: 1, key: 'identity', label: 'Identity', icon: UserRound },
    { value: 2, key: 'contact', label: 'Contact', icon: ClipboardCheck },
    { value: 3, key: 'guardians', label: 'Guardians', icon: UsersRound },
    { value: 4, key: 'academic', label: 'Academic', icon: GraduationCap },
    { value: 5, key: 'compliance', label: 'Compliance', icon: ClipboardCheck },
    { value: 6, key: 'documents', label: 'Documents', icon: FileUp },
    { value: 7, key: 'review', label: 'Review', icon: Save },
];

const step = ref(1);
const page = usePage<AppPageProps>();

function blankAddress(): Address {
    return {
        house: '',
        barangay: '',
        city: '',
        province: '',
        country: 'Philippines',
        zip_code: '',
    };
}

function defaultDocuments(): IntakeDocument[] {
    return [
        { document_type: 'student_photo', file: null, issued_on: '', expires_on: '', notes: '' },
        { document_type: 'psa_birth_certificate', file: null, issued_on: '', expires_on: '', notes: '' },
        { document_type: 'form_138', file: null, issued_on: '', expires_on: '', notes: '' },
        { document_type: 'transfer_credential', file: null, issued_on: '', expires_on: '', notes: '' },
    ];
}

function defaultForm(): StudentFormData {
    return {
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
            current_address: blankAddress(),
            permanent_address: blankAddress(),
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
        documents: props.mode === 'create' ? defaultDocuments() : [],
    };
}

function mergeInitial(): StudentFormData {
    const base = defaultForm();
    const initial = props.initial ?? {};
    const profile = initial.profile ?? {};

    return {
        ...base,
        ...initial,
        metadata: { ...base.metadata, ...(initial.metadata ?? {}) },
        profile: {
            ...base.profile,
            ...profile,
            current_address: { ...base.profile.current_address, ...(profile.current_address ?? {}) },
            permanent_address: { ...base.profile.permanent_address, ...(profile.permanent_address ?? {}) },
            reporting_flags: { ...base.profile.reporting_flags, ...(profile.reporting_flags ?? {}) },
        },
        guardians: initial.guardians?.length ? initial.guardians : base.guardians,
        documents: props.mode === 'create' ? (initial.documents?.length ? initial.documents : base.documents) : [],
    } as StudentFormData;
}

const form = useForm<StudentFormData>(mergeInitial());

const progress = computed(() => Math.round((step.value / steps.length) * 100));
const selectedProgram = computed(() =>
    props.options.programs.find((program) => String(program.id) === form.profile.reporting_flags.intended_program_id),
);
const selectedCurricula = computed(() => selectedProgram.value?.curricula ?? []);
const selectedCurriculum = computed(() =>
    selectedCurricula.value.find((curriculum) => String(curriculum.id) === form.profile.reporting_flags.intended_curriculum_id),
);
const selectedProgramSections = computed(() =>
    props.options.sections?.filter((section) => String(section.programId ?? '') === form.profile.reporting_flags.intended_program_id) ?? [],
);
const attachedDocuments = computed(() => form.documents.filter((document) => document.file).length);
const requiredDocuments = computed(() => props.options.documentTypes.filter((documentType) => documentType.required).length);
const studentDisplayName = computed(() => [form.first_name, form.middle_name, form.last_name, form.suffix].filter(Boolean).join(' '));
const hasValidationErrors = computed(() => Object.keys(form.errors).length > 0);

const inferredAcademicLevel = computed<AcademicStyle['level']>(() => {
    const source = [
        props.options.academicStyle?.level,
        selectedProgram.value?.educationLevel?.code,
        selectedProgram.value?.educationLevel?.name,
        selectedProgram.value?.educationLevel?.category,
        page.props.currentCampus?.code,
        page.props.currentCampus?.name,
    ]
        .filter(Boolean)
        .join(' ')
        .toLowerCase();

    if (source.includes('elementary') || source.includes('grade_school') || source.includes('elem') || source.includes('kindergarten')) {
        return 'elementary';
    }

    if (source.includes('high') || source.includes('senior') || source.includes('junior') || source.includes('shs') || source.includes('jhs')) {
        return 'high_school';
    }

    return 'college';
});

const academicStyle = computed<AcademicStyle>(() => ({
    level: props.options.academicStyle?.level ?? inferredAcademicLevel.value,
    label:
        props.options.academicStyle?.label ??
        {
            college: 'College',
            high_school: 'High school',
            elementary: 'Elementary',
        }[inferredAcademicLevel.value],
}));

const academicLabels = computed(() => {
    if (academicStyle.value.level === 'elementary') {
        return {
            program: 'Grade program',
            curriculum: 'Curriculum / track',
            yearLevel: 'Intended grade level',
            previousLevel: 'Last grade completed',
            strand: 'Learning group / section note',
            academicTarget: 'Grade placement',
            previousSchool: 'Previous school',
        };
    }

    if (academicStyle.value.level === 'high_school') {
        return {
            program: 'School level / strand',
            curriculum: 'Curriculum / strand plan',
            yearLevel: 'Incoming grade level',
            previousLevel: 'Last grade completed',
            strand: 'Senior high strand',
            academicTarget: 'Grade and strand placement',
            previousSchool: 'Previous school',
        };
    }

    return {
        program: 'Intended program',
        curriculum: 'Intended curriculum',
        yearLevel: 'College year level',
        previousLevel: 'Last grade level completed',
        strand: 'Senior high school strand',
        academicTarget: 'Enrollment target',
        previousSchool: 'Previous school',
    };
});

const yearLevelOptions = computed(() => {
    const sectionYears = selectedProgramSections.value
        .map((section) => section.yearLevel)
        .filter((yearLevel): yearLevel is number => typeof yearLevel === 'number');

    if (sectionYears.length > 0) {
        return [...new Set(sectionYears)].sort((a, b) => a - b);
    }

    if (academicStyle.value.level === 'elementary') {
        return [0, 1, 2, 3, 4, 5, 6];
    }

    if (academicStyle.value.level === 'high_school') {
        return [7, 8, 9, 10, 11, 12];
    }

    return [1, 2, 3, 4, 5, 6];
});

const activeStep = computed(() => steps.find((item) => item.value === step.value) ?? steps[0]);

const sectionNotes: Record<string, { eyebrow: string; title: string; description: string; note: string }> = {
    identity: {
        eyebrow: 'Registry foundation',
        title: 'Student identity',
        description: 'Capture the official person record, learner identifiers, and demographic fields used across enrollment.',
        note: 'Names and learner identifiers should match supporting documents before enrollment is approved.',
    },
    contact: {
        eyebrow: 'Reachability',
        title: 'Contact and address',
        description: 'Keep current contact details and address records ready for guardians, billing, and emergency workflows.',
        note: 'Use the copy action when permanent and current addresses are the same.',
    },
    guardians: {
        eyebrow: 'Family access',
        title: 'Guardians and portal access',
        description: 'Record the people responsible for notices, approvals, and student portal access.',
        note: 'At least one primary contact keeps follow-up work clear for the registrar team.',
    },
    academic: {
        eyebrow: 'Placement intent',
        title: 'Academic intent',
        description: 'Set the student classification, intended program, curriculum, and prior school context.',
        note: 'Program and curriculum choices power enrollment creation from the profile.',
    },
    compliance: {
        eyebrow: 'Reporting readiness',
        title: 'Compliance and learner support',
        description: 'Collect the reporting flags and support markers needed for institutional and government reports.',
        note: 'Support flags stay visible in review so the team can route the record correctly.',
    },
    documents: {
        eyebrow: 'Intake packet',
        title: 'Document packet',
        description: 'Attach source files and note dates, expiry, or registrar review context.',
        note: 'Files can be PDF, JPG, JPEG, or PNG up to 10 MB each.',
    },
    review: {
        eyebrow: 'Final check',
        title: 'Review and submit',
        description: 'Confirm the record summary before saving it into the student lifecycle.',
        note: 'Saving preserves guardians, profile details, reporting fields, and attached documents.',
    },
};

const currentSection = computed(() => sectionNotes[activeStep.value.key]);

const readinessChecks = computed(() => [
    {
        label: 'Identity',
        detail: studentDisplayName.value || 'Name not started',
        complete: Boolean(form.first_name && form.last_name),
    },
    {
        label: 'Contact',
        detail: form.email || form.phone || 'No contact method',
        complete: Boolean(form.email || form.phone),
    },
    {
        label: 'Guardian',
        detail: `${form.guardians.length} record${form.guardians.length === 1 ? '' : 's'}`,
        complete: form.guardians.some((guardian) => guardian.first_name && guardian.last_name && guardian.relationship),
    },
    {
        label: 'Academic',
        detail: selectedProgram.value?.code ?? selectedProgram.value?.name ?? 'Program pending',
        complete: Boolean(form.profile.reporting_flags.intake_classification && form.profile.reporting_flags.intended_program_id),
    },
    {
        label: 'Documents',
        detail: `${attachedDocuments.value} attached`,
        complete: attachedDocuments.value >= Math.min(requiredDocuments.value || 1, form.documents.length || 1),
    },
]);

const readinessScore = computed(() => {
    const completed = readinessChecks.value.filter((check) => check.complete).length;

    return Math.round((completed / readinessChecks.value.length) * 100);
});

const stepStatuses = computed(() =>
    steps.map((item) => {
        const check = readinessChecks.value.find((readinessCheck) => readinessCheck.label.toLowerCase() === item.key);

        if (item.key === 'compliance') {
            return {
                ...item,
                complete: Boolean(
                    form.profile.annual_family_income_bracket ||
                        form.profile.emergency_contact_name ||
                        form.profile.emergency_contact_phone ||
                        form.profile.is_4ps_beneficiary ||
                        form.profile.has_disability ||
                        form.profile.is_indigenous_people,
                ),
            };
        }

        if (item.key === 'review') {
            return { ...item, complete: readinessScore.value >= 80 };
        }

        return { ...item, complete: check?.complete ?? false };
    }),
);

const supportFlags = computed(() =>
    [
        form.profile.is_4ps_beneficiary ? '4Ps' : null,
        form.profile.has_government_subsidy ? 'Subsidy' : null,
        form.profile.is_indigenous_people ? 'IP / ICC' : null,
        form.profile.has_disability ? 'Disability support' : null,
    ].filter(Boolean),
);

watch(
    () => form.profile.reporting_flags.intended_program_id,
    () => {
        form.profile.reporting_flags.intended_curriculum_id = '';

        if (!form.profile.college_year_level && yearLevelOptions.value.length > 0) {
            form.profile.college_year_level = String(yearLevelOptions.value[0]);
        }
    },
);

function error(key: string): string | undefined {
    return form.errors[key as keyof typeof form.errors] as string | undefined;
}

function nextStep() {
    step.value = Math.min(steps.length, step.value + 1);
}

function previousStep() {
    step.value = Math.max(1, step.value - 1);
}

function addGuardian() {
    form.guardians.push({
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        relationship: '',
        is_primary: form.guardians.length === 0,
        has_portal_access: true,
    });
}

function removeGuardian(index: number) {
    form.guardians.splice(index, 1);
}

function addDocument() {
    form.documents.push({ document_type: 'custom', file: null, issued_on: '', expires_on: '', notes: '' });
}

function removeDocument(index: number) {
    form.documents.splice(index, 1);
}

function setDocumentFile(event: Event, index: number) {
    const input = event.target as HTMLInputElement;
    form.documents[index].file = input.files?.[0] ?? null;
}

function copyCurrentAddress() {
    form.profile.permanent_address = { ...form.profile.current_address };
}

function titleCase(value: string): string {
    return value
        .trim()
        .replace(/\s+/g, ' ')
        .toLowerCase()
        .replace(/(^|[\s'-])\p{L}/gu, (match) => match.toUpperCase());
}

function cleanPersonNames() {
    form.first_name = titleCase(form.first_name);
    form.middle_name = titleCase(form.middle_name);
    form.last_name = titleCase(form.last_name);
    form.suffix = form.suffix.trim().toUpperCase();
}

function cleanContactFields() {
    form.email = form.email.trim().toLowerCase();
    form.phone = form.phone.replace(/[^\d+]/g, '');
    form.profile.emergency_contact_phone = form.profile.emergency_contact_phone.replace(/[^\d+]/g, '');
}

function cleanRegistryFields() {
    form.student_number = form.student_number.trim().toUpperCase();
    form.profile.learner_reference_number = form.profile.learner_reference_number.replace(/\D/g, '').slice(0, 12);
    form.metadata.learner_reference_number = form.profile.learner_reference_number;
    form.profile.psa_birth_certificate_number = form.profile.psa_birth_certificate_number.trim().toUpperCase();
}

function cleanGuardian(index: number) {
    const guardian = form.guardians[index];

    if (!guardian) {
        return;
    }

    guardian.first_name = titleCase(guardian.first_name);
    guardian.last_name = titleCase(guardian.last_name);
    guardian.relationship = titleCase(guardian.relationship);
    guardian.email = guardian.email.trim().toLowerCase();
    guardian.phone = guardian.phone.replace(/[^\d+]/g, '');
}

function smartFillRecord() {
    cleanPersonNames();
    cleanContactFields();
    cleanRegistryFields();

    if (!form.address) {
        form.address = [
            form.profile.current_address.house,
            form.profile.current_address.barangay,
            form.profile.current_address.city,
            form.profile.current_address.province,
            form.profile.current_address.country,
            form.profile.current_address.zip_code,
        ]
            .filter(Boolean)
            .join(', ');
    }

    if (!form.profile.permanent_address.house && form.profile.current_address.house) {
        copyCurrentAddress();
    }

    if (!form.metadata.previous_school) {
        form.metadata.previous_school = form.profile.previous_school_name;
    }

    if (!form.metadata.emergency_contact) {
        form.metadata.emergency_contact = form.profile.emergency_contact_phone;
    }

    if (!form.profile.college_year_level && yearLevelOptions.value.length > 0) {
        form.profile.college_year_level = String(yearLevelOptions.value[0]);
    }

    form.guardians.forEach((_, index) => cleanGuardian(index));
}

function yearLevelLabel(yearLevel: number): string {
    if (academicStyle.value.level === 'elementary') {
        return yearLevel === 0 ? 'Kindergarten' : `Grade ${yearLevel}`;
    }

    if (academicStyle.value.level === 'high_school') {
        return `Grade ${yearLevel}`;
    }

    return `Year ${yearLevel}`;
}

function submit() {
    smartFillRecord();
    form.metadata.learner_reference_number = form.profile.learner_reference_number;
    form.metadata.previous_school = form.profile.previous_school_name;
    form.metadata.emergency_contact = form.profile.emergency_contact_phone;

    if (props.method === 'patch') {
        form.transform((data) => ({ ...data, _method: 'patch' }) as StudentFormData & { _method: string }).post(props.submitUrl, {
            forceFormData: true,
            preserveScroll: true,
        });

        return;
    }

    form.post(props.submitUrl, {
        forceFormData: true,
        preserveScroll: true,
    });
}
</script>

<template>
    <form class="grid gap-6 xl:grid-cols-[20rem_minmax(0,1fr)_21rem] xl:items-start" @submit.prevent="submit">
        <aside class="grid gap-4 xl:sticky xl:top-6">
            <Card>
                <CardHeader class="gap-4">
                    <div class="grid gap-2">
                        <Badge variant="outline" class="w-fit">{{ mode === 'create' ? 'New record' : 'Record update' }}</Badge>
                        <div>
                            <CardTitle>{{ mode === 'create' ? 'Student intake' : 'Student profile edit' }}</CardTitle>
                            <CardDescription>Move through each registrar checkpoint without losing the record context.</CardDescription>
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-muted-foreground">Form progress</span>
                            <span class="font-medium">{{ progress }}%</span>
                        </div>
                        <Progress :model-value="progress" />
                    </div>
                </CardHeader>
                <CardContent>
                    <Stepper v-model="step" orientation="vertical" class="grid gap-2">
                        <StepperItem v-for="item in stepStatuses" :key="item.key" :step="item.value" class="relative grid gap-2">
                            <StepperTrigger as-child>
                                <Button
                                    type="button"
                                    :variant="step === item.value ? 'default' : 'ghost'"
                                    class="h-auto w-full justify-start gap-3 px-3 py-3 text-left"
                                >
                                    <StepperIndicator as-child>
                                        <span class="flex size-8 shrink-0 items-center justify-center rounded-md border bg-background">
                                            <component :is="item.complete ? Check : item.icon" data-icon="inline-start" />
                                        </span>
                                    </StepperIndicator>
                                    <span class="grid min-w-0 flex-1 gap-1">
                                        <StepperTitle class="truncate">{{ item.label }}</StepperTitle>
                                        <span class="text-xs font-normal opacity-80">
                                            {{ item.complete ? 'Ready' : item.value === step ? 'In progress' : 'Pending' }}
                                        </span>
                                    </span>
                                </Button>
                            </StepperTrigger>
                            <StepperSeparator v-if="item.value < steps.length" class="ml-7 h-3" />
                        </StepperItem>
                    </Stepper>
                </CardContent>
            </Card>
        </aside>

        <main class="grid gap-5">
            <Card>
                <CardHeader class="gap-4">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div class="grid gap-2">
                            <Badge variant="secondary" class="w-fit">{{ currentSection.eyebrow }}</Badge>
                            <div>
                                <CardTitle>{{ currentSection.title }}</CardTitle>
                                <CardDescription>{{ currentSection.description }}</CardDescription>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <Badge variant="outline">{{ academicStyle.label }} intake</Badge>
                            <Badge variant="outline">Step {{ step }} of {{ steps.length }}</Badge>
                            <Button type="button" variant="outline" size="sm" @click="smartFillRecord">Smart fill</Button>
                        </div>
                    </div>
                    <Alert :variant="hasValidationErrors ? 'destructive' : 'default'">
                        <Info />
                        <AlertTitle>{{ hasValidationErrors ? 'Some fields need attention' : 'Registrar note' }}</AlertTitle>
                        <AlertDescription>
                            {{ hasValidationErrors ? 'Review the highlighted fields before saving this record.' : currentSection.note }}
                        </AlertDescription>
                    </Alert>
                </CardHeader>
                <CardContent class="grid gap-6">
                    <section v-if="step === 1" class="grid gap-6">
                        <FieldSet>
                            <FieldLegend>Official identity</FieldLegend>
                            <FieldDescription>Use the legal name and identifiers shown on the submitted source documents.</FieldDescription>
                            <FieldGroup class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                <Field :data-invalid="!!error('first_name')">
                                    <FieldLabel for="first-name">First name</FieldLabel>
                                    <Input id="first-name" v-model="form.first_name" :aria-invalid="!!error('first_name')" @blur="cleanPersonNames" />
                                    <FieldError :errors="[error('first_name')]" />
                                </Field>
                                <Field>
                                    <FieldLabel for="middle-name">Middle name</FieldLabel>
                                    <Input id="middle-name" v-model="form.middle_name" @blur="cleanPersonNames" />
                                </Field>
                                <Field :data-invalid="!!error('last_name')">
                                    <FieldLabel for="last-name">Last name</FieldLabel>
                                    <Input id="last-name" v-model="form.last_name" :aria-invalid="!!error('last_name')" @blur="cleanPersonNames" />
                                    <FieldError :errors="[error('last_name')]" />
                                </Field>
                                <Field>
                                    <FieldLabel for="suffix">Suffix</FieldLabel>
                                    <Input id="suffix" v-model="form.suffix" @blur="cleanPersonNames" />
                                </Field>
                                <Field>
                                    <FieldLabel for="student-number">Student number</FieldLabel>
                                    <Input id="student-number" v-model="form.student_number" placeholder="Generated or existing" @blur="cleanRegistryFields" />
                                </Field>
                                <Field>
                                    <FieldLabel for="birth-date">Birth date</FieldLabel>
                                    <Input id="birth-date" v-model="form.birth_date" type="date" />
                                </Field>
                                <Field>
                                    <FieldLabel for="sex">Sex</FieldLabel>
                                    <Select
                                        :model-value="form.sex || '__none'"
                                        @update:model-value="(value) => (form.sex = value === '__none' ? '' : String(value))"
                                    >
                                        <SelectTrigger id="sex" class="w-full">
                                            <SelectValue placeholder="Not recorded" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectGroup>
                                                <SelectItem value="__none">Not recorded</SelectItem>
                                                <SelectItem value="female">Female</SelectItem>
                                                <SelectItem value="male">Male</SelectItem>
                                                <SelectItem value="self_described">Self-described</SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                </Field>
                                <Field>
                                    <FieldLabel for="status">Profile status</FieldLabel>
                                    <Select v-model="form.status">
                                        <SelectTrigger id="status" class="w-full capitalize">
                                            <SelectValue placeholder="Choose status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectGroup>
                                                <SelectItem v-for="status in options.statuses" :key="status" :value="status" class="capitalize">
                                                    {{ status }}
                                                </SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                </Field>
                            </FieldGroup>
                        </FieldSet>

                        <Separator />

                        <FieldSet>
                            <FieldLegend>Registry identifiers</FieldLegend>
                            <FieldGroup class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                <Field>
                                    <FieldLabel for="psa">PSA certificate number</FieldLabel>
                                    <Input id="psa" v-model="form.profile.psa_birth_certificate_number" @blur="cleanRegistryFields" />
                                </Field>
                                <Field>
                                    <FieldLabel for="lrn">Learner reference number</FieldLabel>
                                    <Input id="lrn" v-model="form.profile.learner_reference_number" maxlength="12" @blur="cleanRegistryFields" />
                                </Field>
                                <Field>
                                    <FieldLabel for="nationality">Nationality</FieldLabel>
                                    <Input id="nationality" v-model="form.profile.nationality" />
                                </Field>
                                <Field>
                                    <FieldLabel for="civil-status">Civil status</FieldLabel>
                                    <Input id="civil-status" v-model="form.profile.civil_status" />
                                </Field>
                            </FieldGroup>
                        </FieldSet>
                    </section>

                    <section v-if="step === 2" class="grid gap-6">
                        <FieldSet>
                            <FieldLegend>Primary contact</FieldLegend>
                            <FieldGroup class="grid gap-4 md:grid-cols-2">
                                <Field>
                                    <FieldLabel for="email">Email</FieldLabel>
                                    <Input id="email" v-model="form.email" type="email" @blur="cleanContactFields" />
                                </Field>
                                <Field>
                                    <FieldLabel for="phone">Phone</FieldLabel>
                                    <Input id="phone" v-model="form.phone" @blur="cleanContactFields" />
                                </Field>
                                <Field class="md:col-span-2">
                                    <FieldLabel for="address">General address</FieldLabel>
                                    <Input id="address" v-model="form.address" />
                                </Field>
                            </FieldGroup>
                        </FieldSet>

                        <Separator />

                        <div class="grid gap-5 xl:grid-cols-2">
                            <FieldSet>
                                <FieldLegend>Current address</FieldLegend>
                                <FieldGroup class="grid gap-3 md:grid-cols-2">
                                    <Input v-model="form.profile.current_address.house" placeholder="House / street / sitio" />
                                    <Input v-model="form.profile.current_address.barangay" placeholder="Barangay" />
                                    <Input v-model="form.profile.current_address.city" placeholder="Municipality / city" />
                                    <Input v-model="form.profile.current_address.province" placeholder="Province" />
                                    <Input v-model="form.profile.current_address.country" placeholder="Country" />
                                    <Input v-model="form.profile.current_address.zip_code" placeholder="ZIP code" />
                                </FieldGroup>
                            </FieldSet>
                            <FieldSet>
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <FieldLegend>Permanent address</FieldLegend>
                                        <FieldDescription>Copy the current address when both match.</FieldDescription>
                                    </div>
                                    <Button type="button" size="sm" variant="outline" @click="copyCurrentAddress">Copy</Button>
                                </div>
                                <FieldGroup class="grid gap-3 md:grid-cols-2">
                                    <Input v-model="form.profile.permanent_address.house" placeholder="House / street / sitio" />
                                    <Input v-model="form.profile.permanent_address.barangay" placeholder="Barangay" />
                                    <Input v-model="form.profile.permanent_address.city" placeholder="Municipality / city" />
                                    <Input v-model="form.profile.permanent_address.province" placeholder="Province" />
                                    <Input v-model="form.profile.permanent_address.country" placeholder="Country" />
                                    <Input v-model="form.profile.permanent_address.zip_code" placeholder="ZIP code" />
                                </FieldGroup>
                            </FieldSet>
                        </div>
                    </section>

                    <section v-if="step === 3" class="grid gap-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="font-semibold">Guardian records</h2>
                                <p class="text-sm text-muted-foreground">Primary guardian and portal access are visible in the lifecycle summary.</p>
                            </div>
                            <Button type="button" variant="outline" class="gap-2" @click="addGuardian">
                                <Plus data-icon="inline-start" />
                                Add guardian
                            </Button>
                        </div>

                        <div class="grid gap-3">
                            <div v-for="(guardian, guardianIndex) in form.guardians" :key="guardianIndex" class="rounded-lg border bg-card p-4">
                                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <h3 class="font-medium">Guardian {{ guardianIndex + 1 }}</h3>
                                        <p class="text-sm text-muted-foreground">{{ guardian.relationship || 'Relationship pending' }}</p>
                                    </div>
                                    <Button type="button" variant="ghost" class="gap-2" @click="removeGuardian(guardianIndex)">
                                        <Trash2 data-icon="inline-start" />
                                        Remove
                                    </Button>
                                </div>
                                <FieldGroup class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                    <Field>
                                        <FieldLabel>First name</FieldLabel>
                                        <Input v-model="guardian.first_name" @blur="cleanGuardian(guardianIndex)" />
                                    </Field>
                                    <Field>
                                        <FieldLabel>Last name</FieldLabel>
                                        <Input v-model="guardian.last_name" @blur="cleanGuardian(guardianIndex)" />
                                    </Field>
                                    <Field>
                                        <FieldLabel>Email</FieldLabel>
                                        <Input v-model="guardian.email" type="email" @blur="cleanGuardian(guardianIndex)" />
                                    </Field>
                                    <Field>
                                        <FieldLabel>Phone</FieldLabel>
                                        <Input v-model="guardian.phone" @blur="cleanGuardian(guardianIndex)" />
                                    </Field>
                                    <Field>
                                        <FieldLabel>Relationship</FieldLabel>
                                        <Input v-model="guardian.relationship" @blur="cleanGuardian(guardianIndex)" />
                                    </Field>
                                    <Field orientation="horizontal">
                                        <Checkbox v-model:checked="guardian.is_primary" />
                                        <FieldLabel>Primary guardian</FieldLabel>
                                    </Field>
                                    <Field orientation="horizontal">
                                        <Checkbox v-model:checked="guardian.has_portal_access" />
                                        <FieldLabel>Portal access</FieldLabel>
                                    </Field>
                                </FieldGroup>
                            </div>
                        </div>
                    </section>

                    <section v-if="step === 4" class="grid gap-6">
                        <FieldSet>
                            <FieldLegend>{{ academicLabels.academicTarget }}</FieldLegend>
                            <FieldDescription>
                                This step is tuned for the current {{ academicStyle.label.toLowerCase() }} campus style.
                            </FieldDescription>
                            <FieldGroup class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                <Field>
                                    <FieldLabel>Classification</FieldLabel>
                                    <Select v-model="form.profile.reporting_flags.intake_classification">
                                        <SelectTrigger class="w-full">
                                            <SelectValue placeholder="Choose classification" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectGroup>
                                                <SelectItem v-for="classification in options.classifications" :key="classification.value" :value="classification.value">
                                                    {{ classification.label }}
                                                </SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                </Field>
                                <Field>
                                    <FieldLabel>{{ academicLabels.program }}</FieldLabel>
                                    <Select
                                        :model-value="form.profile.reporting_flags.intended_program_id || '__none'"
                                        @update:model-value="
                                            (value) => (form.profile.reporting_flags.intended_program_id = value === '__none' ? '' : String(value))
                                        "
                                    >
                                        <SelectTrigger class="w-full">
                                            <SelectValue placeholder="Not selected" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectGroup>
                                                <SelectItem value="__none">Not selected</SelectItem>
                                                <SelectItem v-for="program in options.programs" :key="program.id" :value="String(program.id)">
                                                    {{ program.code ?? program.name }}
                                                </SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                </Field>
                                <Field>
                                    <FieldLabel>{{ academicLabels.curriculum }}</FieldLabel>
                                    <Select
                                        :model-value="form.profile.reporting_flags.intended_curriculum_id || '__none'"
                                        :disabled="!selectedCurricula.length"
                                        @update:model-value="
                                            (value) => (form.profile.reporting_flags.intended_curriculum_id = value === '__none' ? '' : String(value))
                                        "
                                    >
                                        <SelectTrigger class="w-full">
                                            <SelectValue placeholder="Not selected" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectGroup>
                                                <SelectItem value="__none">Not selected</SelectItem>
                                                <SelectItem v-for="curriculum in selectedCurricula" :key="curriculum.id" :value="String(curriculum.id)">
                                                    {{ curriculum.code ?? curriculum.name }}
                                                </SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                </Field>
                                <Field>
                                    <FieldLabel>{{ academicLabels.yearLevel }}</FieldLabel>
                                    <Select
                                        :model-value="form.profile.college_year_level || '__none'"
                                        @update:model-value="(value) => (form.profile.college_year_level = value === '__none' ? '' : String(value))"
                                    >
                                        <SelectTrigger class="w-full">
                                            <SelectValue placeholder="Not selected" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectGroup>
                                                <SelectItem value="__none">Not selected</SelectItem>
                                                <SelectItem v-for="yearLevel in yearLevelOptions" :key="yearLevel" :value="String(yearLevel)">
                                                    {{ yearLevelLabel(yearLevel) }}
                                                </SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                </Field>
                            </FieldGroup>
                        </FieldSet>

                        <Separator />

                        <FieldSet>
                            <FieldLegend>Previous school context</FieldLegend>
                            <FieldGroup class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                <Field>
                                    <FieldLabel>{{ academicLabels.previousSchool }}</FieldLabel>
                                    <Input v-model="form.profile.previous_school_name" />
                                </Field>
                                <Field>
                                    <FieldLabel>Previous school type</FieldLabel>
                                    <Input v-model="form.profile.previous_school_type" />
                                </Field>
                                <Field>
                                    <FieldLabel>{{ academicLabels.previousLevel }}</FieldLabel>
                                    <Input v-model="form.profile.last_grade_level_completed" />
                                </Field>
                                <Field>
                                    <FieldLabel>Last school year attended</FieldLabel>
                                    <Input v-model="form.profile.last_school_year_attended" />
                                </Field>
                                <Field class="md:col-span-2">
                                    <FieldLabel>Previous school address</FieldLabel>
                                    <Input v-model="form.profile.previous_school_address" />
                                </Field>
                                <Field class="md:col-span-2">
                                    <FieldLabel>{{ academicLabels.strand }}</FieldLabel>
                                    <Input v-model="form.profile.senior_high_school_strand" />
                                </Field>
                            </FieldGroup>
                        </FieldSet>
                    </section>

                    <section v-if="step === 5" class="grid gap-6">
                        <FieldSet>
                            <FieldLegend>Reporting details</FieldLegend>
                            <FieldGroup class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                <Field>
                                    <FieldLabel>Annual family income bracket</FieldLabel>
                                    <Select
                                        :model-value="form.profile.annual_family_income_bracket || '__none'"
                                        @update:model-value="
                                            (value) => (form.profile.annual_family_income_bracket = value === '__none' ? '' : String(value))
                                        "
                                    >
                                        <SelectTrigger class="w-full">
                                            <SelectValue placeholder="Not recorded" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectGroup>
                                                <SelectItem value="__none">Not recorded</SelectItem>
                                                <SelectItem v-for="(label, value) in options.incomeBrackets" :key="value" :value="String(value)">
                                                    {{ label }}
                                                </SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                </Field>
                                <Field>
                                    <FieldLabel>Household gross income</FieldLabel>
                                    <Input v-model="form.profile.household_gross_income" type="number" min="0" step="0.01" />
                                </Field>
                                <Field>
                                    <FieldLabel>Subsidy / scholarship program</FieldLabel>
                                    <Input v-model="form.profile.subsidy_program" />
                                </Field>
                                <Field>
                                    <FieldLabel>Religion</FieldLabel>
                                    <Input v-model="form.profile.religion" />
                                </Field>
                                <Field>
                                    <FieldLabel>Mother tongue</FieldLabel>
                                    <Input v-model="form.profile.mother_tongue" />
                                </Field>
                                <Field>
                                    <FieldLabel>Disability type</FieldLabel>
                                    <Input v-model="form.profile.disability_type" :disabled="!form.profile.has_disability" />
                                </Field>
                            </FieldGroup>
                        </FieldSet>

                        <FieldSet>
                            <FieldLegend>Emergency contact</FieldLegend>
                            <FieldGroup class="grid gap-4 md:grid-cols-3">
                                <Field>
                                    <FieldLabel>Emergency contact name</FieldLabel>
                                    <Input v-model="form.profile.emergency_contact_name" />
                                </Field>
                                <Field>
                                    <FieldLabel>Emergency relationship</FieldLabel>
                                    <Input v-model="form.profile.emergency_contact_relationship" />
                                </Field>
                                <Field>
                                    <FieldLabel>Emergency phone</FieldLabel>
                                    <Input v-model="form.profile.emergency_contact_phone" @blur="cleanContactFields" />
                                </Field>
                            </FieldGroup>
                        </FieldSet>

                        <FieldGroup class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                            <Field orientation="horizontal">
                                <Checkbox v-model:checked="form.profile.is_4ps_beneficiary" />
                                <FieldLabel>4Ps beneficiary</FieldLabel>
                            </Field>
                            <Field orientation="horizontal">
                                <Checkbox v-model:checked="form.profile.has_government_subsidy" />
                                <FieldLabel>Government subsidy</FieldLabel>
                            </Field>
                            <Field orientation="horizontal">
                                <Checkbox v-model:checked="form.profile.is_indigenous_people" />
                                <FieldLabel>IP / ICC learner</FieldLabel>
                            </Field>
                            <Field orientation="horizontal">
                                <Checkbox v-model:checked="form.profile.has_disability" />
                                <FieldLabel>Learner with disability</FieldLabel>
                            </Field>
                        </FieldGroup>

                        <FieldGroup class="grid gap-4 md:grid-cols-2">
                            <Field>
                                <FieldLabel>4Ps household ID</FieldLabel>
                                <Input v-model="form.profile.four_ps_household_id" />
                            </Field>
                            <Field>
                                <FieldLabel>IP / ICC community</FieldLabel>
                                <Input v-model="form.profile.indigenous_community" />
                            </Field>
                        </FieldGroup>
                    </section>

                    <section v-if="step === 6" class="grid gap-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="font-semibold">Document packet</h2>
                                <p class="text-sm text-muted-foreground">{{ attachedDocuments }} of {{ form.documents.length }} document rows have files attached.</p>
                            </div>
                            <Button type="button" variant="outline" class="gap-2" @click="addDocument">
                                <Plus data-icon="inline-start" />
                                Add document
                            </Button>
                        </div>

                        <div class="grid gap-3">
                            <div v-for="(document, docIndex) in form.documents" :key="docIndex" class="rounded-lg border bg-card p-4">
                                <div class="mb-4 flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
                                    <div>
                                        <h3 class="font-medium">Document {{ docIndex + 1 }}</h3>
                                        <p class="text-sm text-muted-foreground">
                                            {{ document.file?.name ?? 'No file attached yet' }}
                                        </p>
                                    </div>
                                    <Button type="button" variant="ghost" @click="removeDocument(docIndex)">Remove</Button>
                                </div>
                                <FieldGroup class="grid gap-3 xl:grid-cols-[1fr_1.2fr_0.8fr_0.8fr]">
                                    <Field>
                                        <FieldLabel>Document type</FieldLabel>
                                        <Select v-model="document.document_type">
                                            <SelectTrigger class="w-full">
                                                <SelectValue placeholder="Choose document" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectGroup>
                                                    <SelectItem v-for="type in options.documentTypes" :key="type.value" :value="type.value">
                                                        {{ type.label }}{{ type.required ? ' *' : '' }}
                                                    </SelectItem>
                                                </SelectGroup>
                                            </SelectContent>
                                        </Select>
                                    </Field>
                                    <Field>
                                        <FieldLabel>File</FieldLabel>
                                        <Input type="file" accept=".jpg,.jpeg,.png,.pdf" @change="setDocumentFile($event, docIndex)" />
                                    </Field>
                                    <Field>
                                        <FieldLabel>Issued on</FieldLabel>
                                        <Input v-model="document.issued_on" type="date" />
                                    </Field>
                                    <Field>
                                        <FieldLabel>Expires on</FieldLabel>
                                        <Input v-model="document.expires_on" type="date" />
                                    </Field>
                                    <Field class="xl:col-span-4">
                                        <FieldLabel>Review notes</FieldLabel>
                                        <Textarea v-model="document.notes" placeholder="Review notes, source, or original-copy remarks" />
                                    </Field>
                                </FieldGroup>
                            </div>
                        </div>
                        <Progress v-if="form.progress" :model-value="form.progress.percentage" />
                    </section>

                    <section v-if="step === 7" class="grid gap-4">
                        <div class="rounded-lg border bg-muted/30 p-5">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <p class="text-sm text-muted-foreground">Student</p>
                                    <h2 class="text-2xl font-semibold">{{ studentDisplayName || 'Name pending' }}</h2>
                                    <p class="text-sm text-muted-foreground">
                                        {{ form.student_number || 'No student number' }} / {{ form.profile.reporting_flags.intake_classification || 'new' }}
                                    </p>
                                </div>
                                <Badge class="capitalize">{{ form.status }}</Badge>
                            </div>
                        </div>

                        <ItemGroup class="grid gap-3 md:grid-cols-3">
                            <Item variant="outline">
                                <ItemContent>
                                    <ItemDescription>Documents attached</ItemDescription>
                                    <ItemTitle>{{ attachedDocuments }}</ItemTitle>
                                </ItemContent>
                            </Item>
                            <Item variant="outline">
                                <ItemContent>
                                    <ItemDescription>Guardians</ItemDescription>
                                    <ItemTitle>{{ form.guardians.length }}</ItemTitle>
                                </ItemContent>
                            </Item>
                            <Item variant="outline">
                                <ItemContent>
                                    <ItemDescription>Income bracket</ItemDescription>
                                    <ItemTitle class="text-sm">{{ options.incomeBrackets[form.profile.annual_family_income_bracket] ?? 'Not recorded' }}</ItemTitle>
                                </ItemContent>
                            </Item>
                        </ItemGroup>
                    </section>
                </CardContent>
            </Card>

            <div class="sticky bottom-0 flex flex-col gap-3 rounded-lg border bg-background/95 p-4 backdrop-blur sm:flex-row sm:items-center sm:justify-between">
                <div class="flex gap-2">
                    <Button type="button" variant="outline" class="gap-2" :disabled="step === 1" @click="previousStep">
                        <ArrowLeft data-icon="inline-start" />
                        Previous
                    </Button>
                    <Button type="button" variant="outline" class="gap-2" :disabled="step === steps.length" @click="nextStep">
                        Next
                        <ArrowRight data-icon="inline-end" />
                    </Button>
                </div>
                <Button v-if="step < steps.length" type="button" class="gap-2" @click="nextStep">
                    Continue
                    <ArrowRight data-icon="inline-end" />
                </Button>
                <Button v-else type="submit" class="gap-2" :disabled="form.processing">
                    <Save data-icon="inline-start" />
                    {{ form.processing ? 'Saving...' : submitLabel }}
                </Button>
            </div>
        </main>

        <aside class="grid gap-4 xl:sticky xl:top-6">
            <Card>
                <CardHeader class="gap-4">
                    <div>
                        <CardTitle>Record readiness</CardTitle>
                        <CardDescription>Live intake signals before the record enters the lifecycle.</CardDescription>
                    </div>
                    <div class="grid gap-2">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-muted-foreground">Readiness</span>
                            <span class="font-medium">{{ readinessScore }}%</span>
                        </div>
                        <Progress :model-value="readinessScore" />
                    </div>
                </CardHeader>
                <CardContent class="grid gap-5">
                    <div class="grid gap-1">
                        <p class="text-sm text-muted-foreground">Active student</p>
                        <p class="font-medium">{{ studentDisplayName || 'Name pending' }}</p>
                        <p class="text-sm text-muted-foreground">{{ form.email || form.phone || 'No contact method recorded' }}</p>
                    </div>

                    <Separator />

                    <ItemGroup class="grid gap-2">
                        <Item v-for="check in readinessChecks" :key="check.label" variant="muted" size="sm">
                            <ItemMedia variant="icon">
                                <Check v-if="check.complete" />
                                <Info v-else />
                            </ItemMedia>
                            <ItemContent>
                                <ItemTitle>{{ check.label }}</ItemTitle>
                                <ItemDescription>{{ check.detail }}</ItemDescription>
                            </ItemContent>
                        </Item>
                    </ItemGroup>

                    <Separator />

                    <div class="grid gap-3">
                        <div class="grid gap-1">
                            <p class="text-sm font-medium">Academic target</p>
                            <p class="text-sm text-muted-foreground">
                                {{ selectedProgram?.code ?? selectedProgram?.name ?? 'Program not selected' }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{ selectedCurriculum?.code ?? selectedCurriculum?.name ?? 'Curriculum not selected' }}
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <Badge v-for="flag in supportFlags" :key="flag" variant="secondary">{{ flag }}</Badge>
                            <Badge v-if="!supportFlags.length" variant="outline">No support flags</Badge>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </aside>
    </form>
</template>
