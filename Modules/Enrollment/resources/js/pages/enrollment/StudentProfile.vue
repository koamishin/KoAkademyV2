<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { CheckCircle2, ClipboardList, FileCheck2, Plus, Save, UserRound } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, update } from '@/routes/admin/students';
import { approve, cancel, store as storeEnrollment } from '@/routes/admin/students/enrollments';
import { update as updateEnrollmentSubject } from '@/routes/admin/students/enrollment-subjects';
import type { AppPageProps, BreadcrumbItem } from '@/types';

type Guardian = {
    id: number;
    firstName: string;
    lastName: string;
    fullName: string;
    email?: string | null;
    phone?: string | null;
    relationship: string;
    isPrimary: boolean;
    hasPortalAccess: boolean;
};

type Student = {
    id: number;
    firstName: string;
    middleName?: string | null;
    lastName: string;
    suffix?: string | null;
    fullName: string;
    birthDate?: string | null;
    sex?: string | null;
    email?: string | null;
    phone?: string | null;
    address?: string | null;
    status: string;
    studentNumber?: string | null;
    metadata: Record<string, string | null>;
    guardians: Guardian[];
};

type EnrollmentSubject = {
    id: number;
    curriculumItemId: number;
    classOfferingId?: number | null;
    subjectName?: string | null;
    subjectCode?: string | null;
    creditUnits?: string | null;
    contactHours?: string | null;
    labHours?: string | null;
    isRequired: boolean;
    status: string;
    finalResult?: string | null;
    assessmentAmount?: string | null;
    classOffering?: {
        id: number;
        name: string;
        code: string;
        teacher?: string | null;
        section?: string | null;
    } | null;
};

type Enrollment = {
    id: number;
    studentNumber?: string | null;
    classification?: string | null;
    status: string;
    period?: string | null;
    term?: string | null;
    academicYear?: string | null;
    curriculum?: string | null;
    program?: string | null;
    section?: string | null;
    approvedAt?: string | null;
    notes?: string | null;
    assessment?: {
        currency: string;
        tuitionTotal: string;
        laboratoryTotal: string;
        miscellaneousTotal: string;
        total: string;
        assessedAt?: string | null;
    } | null;
    subjects: EnrollmentSubject[];
    assessmentLines: {
        id: number;
        type: string;
        code: string;
        description: string;
        quantity: string;
        unitAmount: string;
        amount: string;
        curriculumItemId?: number | null;
    }[];
};

type Options = {
    statuses: string[];
    enrollmentStatuses: { value: string; label: string }[];
    classifications: { value: string; label: string }[];
    periods: { id: number; name: string; termId: number; term?: string | null; academicYear?: string | null; active: boolean }[];
    programs: { id: number; name: string; code?: string | null; curricula: { id: number; name: string; code?: string | null }[] }[];
    sections: { id: number; programId: number; termId: number; name: string; code?: string | null; yearLevel?: number | null }[];
    classOfferings: { id: number; termId: number; subjectId: number; name: string; code: string; teacher?: string | null; section?: string | null }[];
    curriculumItems: {
        id: number;
        curriculumId: number;
        subjectId: number;
        subjectName?: string | null;
        subjectCode?: string | null;
        yearLevel?: number | null;
        termSequence?: number | null;
        creditUnits?: string | null;
        isRequired: boolean;
        electiveGroup?: string | null;
        electiveGroupId?: number | null;
    }[];
};

const props = defineProps<{
    student: Student;
    enrollments: Enrollment[];
    options: Options;
}>();

const page = usePage<AppPageProps>();
const campusSlug = computed(() => page.props.currentCampus!.slug);
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Student Records', href: index.url({ campus: campusSlug.value }) },
    { title: props.student.fullName },
];

const editForm = useForm({
    first_name: props.student.firstName,
    middle_name: props.student.middleName ?? '',
    last_name: props.student.lastName,
    suffix: props.student.suffix ?? '',
    birth_date: props.student.birthDate ?? '',
    sex: props.student.sex ?? '',
    email: props.student.email ?? '',
    phone: props.student.phone ?? '',
    address: props.student.address ?? '',
    status: props.student.status,
    student_number: props.student.studentNumber ?? '',
    metadata: {
        learner_reference_number: props.student.metadata?.learner_reference_number ?? '',
        previous_school: props.student.metadata?.previous_school ?? '',
        emergency_contact: props.student.metadata?.emergency_contact ?? '',
    },
    guardians: props.student.guardians.map((guardian) => ({
        id: guardian.id,
        first_name: guardian.firstName,
        last_name: guardian.lastName,
        email: guardian.email ?? '',
        phone: guardian.phone ?? '',
        relationship: guardian.relationship,
        is_primary: guardian.isPrimary,
        has_portal_access: guardian.hasPortalAccess,
    })),
});

const enrollmentForm = useForm({
    enrollment_period_id: props.options.periods[0]?.id ?? '',
    curriculum_id: props.options.programs[0]?.curricula[0]?.id ?? '',
    classification: props.options.classifications[0]?.value ?? 'new',
    section_id: '',
    year_level: 1,
    selected_elective_item_ids: [] as number[],
    notes: '',
});

const subjectForms = reactive<Record<number, { class_offering_id: string; status: string; final_result: string }>>({});
const showingProfileForm = ref(false);

for (const enrollment of props.enrollments) {
    for (const subject of enrollment.subjects) {
        subjectForms[subject.id] = {
            class_offering_id: subject.classOfferingId ? String(subject.classOfferingId) : '',
            status: subject.status,
            final_result: subject.finalResult ?? '',
        };
    }
}

const selectedCurriculumItems = computed(() =>
    props.options.curriculumItems.filter(
        (item) => item.curriculumId === Number(enrollmentForm.curriculum_id),
    ),
);

const requiredPreview = computed(() =>
    selectedCurriculumItems.value.filter((item) => item.isRequired),
);

const electivePreview = computed(() =>
    selectedCurriculumItems.value.filter((item) => !item.isRequired),
);

const matchingSections = computed(() => {
    const curriculumId = Number(enrollmentForm.curriculum_id);
    const program = props.options.programs.find((program) =>
        program.curricula.some((curriculum) => curriculum.id === curriculumId),
    );
    const period = props.options.periods.find((period) => period.id === Number(enrollmentForm.enrollment_period_id));

    return props.options.sections.filter(
        (section) =>
            section.programId === program?.id &&
            (!period || section.termId === period.termId),
    );
});

function classOfferingsFor(subject: EnrollmentSubject, enrollment: Enrollment) {
    const period = props.options.periods.find((item) => item.name === enrollment.period);

    return props.options.classOfferings.filter((classOffering) => {
        const curriculumItem = props.options.curriculumItems.find(
            (item) => item.id === subject.curriculumItemId,
        );

        return (
            classOffering.subjectId === curriculumItem?.subjectId &&
            (!period || classOffering.termId === period.termId)
        );
    });
}

const statusClass = (status: string) => {
    if (['approved', 'active', 'completed', 'enrolled'].includes(status)) {
        return 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300';
    }

    if (['pending', 'waitlisted', 'draft'].includes(status)) {
        return 'bg-amber-500/10 text-amber-700 dark:text-amber-300';
    }

    return 'bg-muted text-muted-foreground';
};

function addGuardian() {
    editForm.guardians.push({
        id: undefined as unknown as number,
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        relationship: '',
        is_primary: editForm.guardians.length === 0,
        has_portal_access: true,
    });
}

function removeGuardian(index: number) {
    editForm.guardians.splice(index, 1);
}

function submitProfile() {
    editForm.patch(update.url({ campus: campusSlug.value, student: props.student.id }), {
        preserveScroll: true,
        onSuccess: () => {
            showingProfileForm.value = false;
        },
    });
}

function submitEnrollment() {
    enrollmentForm.post(storeEnrollment.url({ campus: campusSlug.value, student: props.student.id }), {
        preserveScroll: true,
        onSuccess: () => enrollmentForm.reset('notes', 'selected_elective_item_ids'),
    });
}

function approveEnrollment(enrollment: Enrollment) {
    router.post(
        approve.url({ campus: campusSlug.value, student: props.student.id, enrollment: enrollment.id }),
        {},
        { preserveScroll: true },
    );
}

function cancelEnrollment(enrollment: Enrollment) {
    router.post(
        cancel.url({ campus: campusSlug.value, student: props.student.id, enrollment: enrollment.id }),
        {},
        { preserveScroll: true },
    );
}

function updateSubject(enrollment: Enrollment, subject: EnrollmentSubject) {
    const form = subjectForms[subject.id];

    router.patch(
        updateEnrollmentSubject.url({
            campus: campusSlug.value,
            student: props.student.id,
            enrollment: enrollment.id,
            enrollmentSubject: subject.id,
        }),
        {
            class_offering_id: form.class_offering_id || null,
            status: form.status,
            final_result: form.final_result || null,
        },
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head :title="student.fullName" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-[1500px] flex-col gap-6 p-4 sm:p-6 lg:p-8">
            <header class="grid gap-5 border-b pb-6 xl:grid-cols-[1fr_auto]">
                <div class="flex gap-4">
                    <div class="flex size-14 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <UserRound class="size-7" />
                    </div>
                    <div>
                        <Link :href="index.url({ campus: campusSlug })" class="text-sm text-muted-foreground hover:text-primary">
                            Student Records
                        </Link>
                        <h1 class="mt-1 text-2xl font-semibold tracking-tight sm:text-3xl">
                            {{ student.fullName }}
                        </h1>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ student.studentNumber ?? 'No student number yet' }}
                            <span v-if="student.email"> / {{ student.email }}</span>
                            <span v-if="student.phone"> / {{ student.phone }}</span>
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap items-start gap-2 xl:justify-end">
                    <Badge :class="statusClass(student.status)" class="capitalize">{{ student.status }}</Badge>
                    <Button variant="outline" class="gap-2" @click="showingProfileForm = !showingProfileForm">
                        <Save class="size-4" />
                        Edit profile
                    </Button>
                </div>
            </header>

            <section v-if="showingProfileForm" class="rounded-lg border bg-card p-5 shadow-sm">
                <form class="grid gap-5" @submit.prevent="submitProfile">
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div class="grid gap-2">
                            <Label>First name</Label>
                            <Input v-model="editForm.first_name" />
                        </div>
                        <div class="grid gap-2">
                            <Label>Middle name</Label>
                            <Input v-model="editForm.middle_name" />
                        </div>
                        <div class="grid gap-2">
                            <Label>Last name</Label>
                            <Input v-model="editForm.last_name" />
                        </div>
                        <div class="grid gap-2">
                            <Label>Student number</Label>
                            <Input v-model="editForm.student_number" />
                        </div>
                        <div class="grid gap-2">
                            <Label>Birth date</Label>
                            <Input v-model="editForm.birth_date" type="date" />
                        </div>
                        <div class="grid gap-2">
                            <Label>Sex</Label>
                            <Input v-model="editForm.sex" />
                        </div>
                        <div class="grid gap-2">
                            <Label>Email</Label>
                            <Input v-model="editForm.email" type="email" />
                        </div>
                        <div class="grid gap-2">
                            <Label>Phone</Label>
                            <Input v-model="editForm.phone" />
                        </div>
                        <div class="grid gap-2 xl:col-span-2">
                            <Label>Address</Label>
                            <Input v-model="editForm.address" />
                        </div>
                        <div class="grid gap-2">
                            <Label>Status</Label>
                            <select v-model="editForm.status" class="h-9 rounded-md border bg-background px-3 text-sm">
                                <option v-for="status in options.statuses" :key="status" :value="status">{{ status }}</option>
                            </select>
                        </div>
                        <div class="grid gap-2">
                            <Label>LRN</Label>
                            <Input v-model="editForm.metadata.learner_reference_number" />
                        </div>
                    </div>

                    <div class="grid gap-4">
                        <div class="flex items-center justify-between gap-4">
                            <h2 class="font-semibold">Guardians</h2>
                            <Button type="button" size="sm" variant="outline" class="gap-2" @click="addGuardian">
                                <Plus class="size-4" />
                                Add guardian
                            </Button>
                        </div>
                        <article
                            v-for="(guardian, guardianIndex) in editForm.guardians"
                            :key="guardianIndex"
                            class="grid gap-3 rounded-md border bg-muted/30 p-4 md:grid-cols-3"
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
                                <Button type="button" variant="ghost" size="sm" @click="removeGuardian(guardianIndex)">Remove</Button>
                            </div>
                        </article>
                    </div>

                    <div class="flex justify-end">
                        <Button type="submit" :disabled="editForm.processing">Save profile</Button>
                    </div>
                </form>
            </section>

            <section class="grid gap-5 xl:grid-cols-[0.9fr_1.4fr]">
                <aside class="grid gap-5">
                    <article class="rounded-lg border bg-card p-5 shadow-sm">
                        <h2 class="font-semibold">Profile</h2>
                        <dl class="mt-4 grid gap-3 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-muted-foreground">Birth date</dt>
                                <dd>{{ student.birthDate ?? 'Not recorded' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-muted-foreground">Sex</dt>
                                <dd>{{ student.sex ?? 'Not recorded' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-muted-foreground">LRN</dt>
                                <dd>{{ student.metadata?.learner_reference_number ?? 'Not recorded' }}</dd>
                            </div>
                            <div class="grid gap-1">
                                <dt class="text-muted-foreground">Address</dt>
                                <dd>{{ student.address ?? 'Not recorded' }}</dd>
                            </div>
                        </dl>
                    </article>

                    <article class="rounded-lg border bg-card p-5 shadow-sm">
                        <h2 class="font-semibold">Guardians</h2>
                        <div class="mt-4 grid gap-3">
                            <div v-for="guardian in student.guardians" :key="guardian.id" class="rounded-md bg-muted/50 p-3">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="font-medium">{{ guardian.fullName }}</p>
                                    <Badge v-if="guardian.isPrimary" variant="secondary">Primary</Badge>
                                </div>
                                <p class="text-sm text-muted-foreground">
                                    {{ guardian.relationship }}
                                    <span v-if="guardian.phone"> / {{ guardian.phone }}</span>
                                </p>
                            </div>
                            <p v-if="student.guardians.length === 0" class="text-sm text-muted-foreground">
                                No guardians recorded.
                            </p>
                        </div>
                    </article>

                    <article class="rounded-lg border bg-card p-5 shadow-sm">
                        <div class="flex items-center gap-2">
                            <ClipboardList class="size-5 text-primary" />
                            <h2 class="font-semibold">Create enrollment</h2>
                        </div>
                        <form class="mt-4 grid gap-4" @submit.prevent="submitEnrollment">
                            <div class="grid gap-2">
                                <Label>Period</Label>
                                <select v-model="enrollmentForm.enrollment_period_id" class="h-9 rounded-md border bg-background px-3 text-sm">
                                    <option v-for="period in options.periods" :key="period.id" :value="period.id">
                                        {{ period.name }} / {{ period.term ?? 'Term' }}
                                    </option>
                                </select>
                            </div>
                            <div class="grid gap-2">
                                <Label>Curriculum</Label>
                                <select v-model="enrollmentForm.curriculum_id" class="h-9 rounded-md border bg-background px-3 text-sm">
                                    <optgroup v-for="program in options.programs" :key="program.id" :label="program.name">
                                        <option v-for="curriculum in program.curricula" :key="curriculum.id" :value="curriculum.id">
                                            {{ curriculum.code ?? curriculum.name }}
                                        </option>
                                    </optgroup>
                                </select>
                            </div>
                            <div class="grid gap-2">
                                <Label>Classification</Label>
                                <select v-model="enrollmentForm.classification" class="h-9 rounded-md border bg-background px-3 text-sm">
                                    <option v-for="classification in options.classifications" :key="classification.value" :value="classification.value">
                                        {{ classification.label }}
                                    </option>
                                </select>
                            </div>
                            <div class="grid gap-2">
                                <Label>Section</Label>
                                <select v-model="enrollmentForm.section_id" class="h-9 rounded-md border bg-background px-3 text-sm">
                                    <option value="">No section</option>
                                    <option v-for="section in matchingSections" :key="section.id" :value="section.id">
                                        {{ section.code ?? section.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="grid gap-2">
                                <Label>Year level</Label>
                                <Input v-model="enrollmentForm.year_level" type="number" min="1" />
                            </div>

                            <div v-if="requiredPreview.length > 0" class="rounded-md bg-muted/50 p-3 text-sm">
                                <p class="font-medium">Required subjects</p>
                                <p class="mt-1 text-muted-foreground">
                                    {{ requiredPreview.map((item) => item.subjectCode ?? item.subjectName).join(', ') }}
                                </p>
                            </div>

                            <div v-if="electivePreview.length > 0" class="grid gap-2">
                                <p class="text-sm font-medium">Electives</p>
                                <label v-for="item in electivePreview" :key="item.id" class="flex items-center gap-2 text-sm">
                                    <input v-model="enrollmentForm.selected_elective_item_ids" type="checkbox" :value="item.id" class="size-4" />
                                    {{ item.subjectCode }} / {{ item.subjectName }}
                                    <span v-if="item.electiveGroup" class="text-muted-foreground">({{ item.electiveGroup }})</span>
                                </label>
                            </div>

                            <div class="grid gap-2">
                                <Label>Notes</Label>
                                <Input v-model="enrollmentForm.notes" />
                            </div>
                            <Button type="submit" :disabled="enrollmentForm.processing">Create enrollment</Button>
                        </form>
                    </article>
                </aside>

                <main class="grid gap-5">
                    <article
                        v-for="enrollment in enrollments"
                        :key="enrollment.id"
                        class="rounded-lg border bg-card shadow-sm"
                    >
                        <header class="flex flex-col gap-4 border-b p-5 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="font-semibold">{{ enrollment.period ?? 'Enrollment' }}</h2>
                                    <Badge :class="statusClass(enrollment.status)" class="capitalize">
                                        {{ enrollment.status.replace('_', ' ') }}
                                    </Badge>
                                </div>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    {{ enrollment.program ?? 'Program' }}
                                    <span v-if="enrollment.curriculum"> / {{ enrollment.curriculum }}</span>
                                    <span v-if="enrollment.section"> / {{ enrollment.section }}</span>
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <Button
                                    v-if="!['approved', 'cancelled', 'completed'].includes(enrollment.status)"
                                    size="sm"
                                    class="gap-2"
                                    @click="approveEnrollment(enrollment)"
                                >
                                    <CheckCircle2 class="size-4" />
                                    Approve
                                </Button>
                                <Button
                                    v-if="!['cancelled', 'completed'].includes(enrollment.status)"
                                    size="sm"
                                    variant="outline"
                                    @click="cancelEnrollment(enrollment)"
                                >
                                    Cancel
                                </Button>
                            </div>
                        </header>

                        <section class="grid gap-4 border-b p-5 md:grid-cols-4">
                            <div>
                                <p class="text-xs text-muted-foreground">Tuition</p>
                                <p class="font-semibold">{{ enrollment.assessment?.tuitionTotal ?? '0.00' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Laboratory</p>
                                <p class="font-semibold">{{ enrollment.assessment?.laboratoryTotal ?? '0.00' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Miscellaneous</p>
                                <p class="font-semibold">{{ enrollment.assessment?.miscellaneousTotal ?? '0.00' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Total</p>
                                <p class="font-semibold">{{ enrollment.assessment?.total ?? '0.00' }}</p>
                            </div>
                        </section>

                        <section class="grid gap-3 p-5">
                            <div class="flex items-center gap-2">
                                <FileCheck2 class="size-5 text-primary" />
                                <h3 class="font-semibold">Enrolled subjects</h3>
                            </div>
                            <article
                                v-for="subject in enrollment.subjects"
                                :key="subject.id"
                                class="grid gap-3 rounded-md border bg-background p-4 xl:grid-cols-[1fr_1fr_auto]"
                            >
                                <div>
                                    <p class="font-medium">{{ subject.subjectCode }} / {{ subject.subjectName }}</p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ subject.creditUnits ?? '0.00' }} units
                                        <span v-if="subject.labHours && Number(subject.labHours) > 0"> / lab {{ subject.labHours }}</span>
                                        <span v-if="subject.assessmentAmount"> / {{ subject.assessmentAmount }}</span>
                                    </p>
                                </div>
                                <div class="grid gap-2 md:grid-cols-3">
                                    <select v-model="subjectForms[subject.id].class_offering_id" class="h-9 rounded-md border bg-background px-3 text-sm md:col-span-2">
                                        <option value="">No class offering</option>
                                        <option
                                            v-for="classOffering in classOfferingsFor(subject, enrollment)"
                                            :key="classOffering.id"
                                            :value="String(classOffering.id)"
                                        >
                                            {{ classOffering.code }} / {{ classOffering.teacher ?? 'No teacher' }}
                                        </option>
                                    </select>
                                    <select v-model="subjectForms[subject.id].status" class="h-9 rounded-md border bg-background px-3 text-sm">
                                        <option value="enrolled">Enrolled</option>
                                        <option value="dropped">Dropped</option>
                                        <option value="completed">Completed</option>
                                        <option value="withdrawn">Withdrawn</option>
                                    </select>
                                    <Input v-model="subjectForms[subject.id].final_result" placeholder="Final result" class="md:col-span-3" />
                                </div>
                                <div class="flex items-center xl:justify-end">
                                    <Button size="sm" variant="outline" @click="updateSubject(enrollment, subject)">Save</Button>
                                </div>
                            </article>
                            <p v-if="enrollment.subjects.length === 0" class="text-sm text-muted-foreground">
                                No subjects recorded for this enrollment.
                            </p>
                        </section>
                    </article>

                    <p v-if="enrollments.length === 0" class="rounded-lg border bg-card p-10 text-center text-sm text-muted-foreground">
                        No enrollments have been created for this student yet.
                    </p>
                </main>
            </section>
        </div>
    </AppLayout>
</template>
