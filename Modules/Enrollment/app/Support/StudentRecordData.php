<?php

declare(strict_types=1);

namespace Modules\Enrollment\Support;

use App\Enums\PersonRole;
use App\Models\Campus;
use App\Models\Curriculum;
use App\Models\CurriculumItem;
use App\Models\Person;
use App\Models\Program;
use App\Models\Section;
use App\Models\Term;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Classroom\Models\ClassOffering;
use Modules\Enrollment\Enums\EnrollmentClassification;
use Modules\Enrollment\Enums\EnrollmentStatus;
use Modules\Enrollment\Models\Enrollment;
use Modules\Enrollment\Models\EnrollmentPeriod;
use Modules\Enrollment\Models\EnrollmentSubject;
use Modules\Enrollment\Models\StudentDocument;
use Modules\Enrollment\Models\TransferCreditEvaluation;
use Modules\Enrollment\Models\TransferCreditSubject;

final class StudentRecordData
{
    private const REQUIRED_DOCUMENT_TYPES = [
        'student_photo',
        'psa_birth_certificate',
        'form_137',
        'form_138',
    ];

    /**
     * @return array<string, mixed>
     */
    public function index(Request $request, Campus $campus): array
    {
        $filters = $this->filters($request);

        $query = Person::query()
            ->whereHas('roles', fn (Builder $query): Builder => $query
                ->where('campus_id', $campus->getKey())
                ->where('role', PersonRole::Student)
                ->where('active', true))
            ->with([
                'roles' => fn ($query) => $query->where('campus_id', $campus->getKey()),
                'studentProfile',
                'studentDocuments' => fn ($query) => $query->where('campus_id', $campus->getKey())->latest(),
                'transferCreditEvaluations' => fn ($query) => $query->where('campus_id', $campus->getKey())->with('subjects')->latest(),
                'enrollments' => fn ($query) => $query
                    ->where('campus_id', $campus->getKey())
                    ->with(['period.term.academicYear', 'curriculum.program', 'section'])
                    ->latest(),
            ])
            ->withCount(['enrollments as campus_enrollments_count' => fn ($query) => $query->where('campus_id', $campus->getKey())])
            ->latest();

        $this->applyFilters($query, $filters, $campus);

        /** @var LengthAwarePaginator<int, Person> $students */
        $students = $query
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Person $student): array => $this->studentRow($student));

        return [
            'students' => $students,
            'filters' => $filters,
            'options' => $this->options($campus),
            'summary' => [
                'total' => Person::query()->whereHas('roles', fn (Builder $query): Builder => $query
                    ->where('campus_id', $campus->getKey())
                    ->where('role', PersonRole::Student)
                    ->where('active', true))->count(),
                'activeEnrollments' => Enrollment::query()
                    ->whereBelongsTo($campus)
                    ->whereIn('status', [EnrollmentStatus::Pending, EnrollmentStatus::Approved])
                    ->count(),
                'waiting' => Enrollment::query()
                    ->whereBelongsTo($campus)
                    ->whereIn('status', [EnrollmentStatus::Draft, EnrollmentStatus::Waitlisted])
                    ->count(),
                'documentGaps' => $this->documentGapCount($campus),
                'transferReviews' => TransferCreditEvaluation::query()
                    ->whereBelongsTo($campus)
                    ->whereIn('status', ['draft', 'in_review'])
                    ->count(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function show(Person $student, Campus $campus): array
    {
        $student->load([
            'roles' => fn ($query) => $query->where('campus_id', $campus->getKey()),
            'studentProfile',
            'studentDocuments' => fn ($query) => $query->where('campus_id', $campus->getKey())->latest(),
            'transferCreditEvaluations' => fn ($query) => $query
                ->where('campus_id', $campus->getKey())
                ->with(['curriculum.program', 'evaluator:id,name', 'subjects.curriculumItem.subject'])
                ->latest(),
            'guardians',
            'enrollments' => fn ($query) => $query
                ->where('campus_id', $campus->getKey())
                ->with([
                    'period.term.academicYear',
                    'curriculum.program',
                    'section',
                    'assessment.lines',
                    'subjects.curriculumItem.subject',
                    'subjects.classOffering.subject',
                    'subjects.classOffering.teacher',
                    'subjects.classOffering.section',
                ])
                ->latest(),
        ]);

        return [
            'student' => $this->studentProfile($student),
            'documents' => $student->studentDocuments
                ->map(fn (StudentDocument $document): array => $this->studentDocument($document))
                ->values()
                ->all(),
            'transferCredits' => $student->transferCreditEvaluations
                ->map(fn (TransferCreditEvaluation $evaluation): array => $this->transferCreditEvaluation($evaluation))
                ->values()
                ->all(),
            'enrollments' => $student->enrollments
                ->map(fn (Enrollment $enrollment): array => $this->enrollmentProfile($enrollment))
                ->values()
                ->all(),
            'options' => $this->options($campus),
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function filters(Request $request): array
    {
        return [
            'search' => $request->string('search')->toString() ?: null,
            'status' => $request->string('status')->toString() ?: null,
            'term' => $request->string('term')->toString() ?: null,
            'program' => $request->string('program')->toString() ?: null,
            'curriculum' => $request->string('curriculum')->toString() ?: null,
            'section' => $request->string('section')->toString() ?: null,
            'enrollment_status' => $request->string('enrollment_status')->toString() ?: null,
            'view' => $request->string('view')->toString() ?: 'all',
        ];
    }

    /**
     * @param  Builder<Person>  $query
     * @param  array<string, string|null>  $filters
     */
    private function applyFilters(Builder $query, array $filters, Campus $campus): void
    {
        $query
            ->when($filters['search'], function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhereHas('roles', fn (Builder $query): Builder => $query->where('reference_number', 'like', "%{$search}%"));
                });
            })
            ->when($filters['status'], fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when($filters['term'], fn (Builder $query, string $termId): Builder => $query->whereHas(
                'enrollments.period',
                fn (Builder $query): Builder => $query->where('term_id', (int) $termId),
            ))
            ->when($filters['program'], fn (Builder $query, string $programId): Builder => $query->whereHas(
                'enrollments.curriculum',
                fn (Builder $query): Builder => $query->where('program_id', (int) $programId),
            ))
            ->when($filters['curriculum'], fn (Builder $query, string $curriculumId): Builder => $query->whereHas(
                'enrollments',
                fn (Builder $query): Builder => $query->where('curriculum_id', (int) $curriculumId),
            ))
            ->when($filters['section'], fn (Builder $query, string $sectionId): Builder => $query->whereHas(
                'enrollments',
                fn (Builder $query): Builder => $query->where('section_id', (int) $sectionId),
            ))
            ->when($filters['enrollment_status'], fn (Builder $query, string $status): Builder => $query->whereHas(
                'enrollments',
                fn (Builder $query): Builder => $query->where('campus_id', $campus->getKey())->where('status', $status),
            ))
            ->when($filters['view'] === 'document_gaps', function (Builder $query) use ($campus): void {
                $query->where(function (Builder $query) use ($campus): void {
                    foreach (self::REQUIRED_DOCUMENT_TYPES as $documentType) {
                        $query->orWhereDoesntHave(
                            'studentDocuments',
                            fn (Builder $query): Builder => $query
                                ->where('campus_id', $campus->getKey())
                                ->where('document_type', $documentType)
                                ->where('status', 'verified'),
                        );
                    }
                });
            })
            ->when($filters['view'] === 'transfer_reviews', fn (Builder $query): Builder => $query->whereHas(
                'transferCreditEvaluations',
                fn (Builder $query): Builder => $query->where('campus_id', $campus->getKey())->whereIn('status', ['draft', 'in_review']),
            ));
    }

    /**
     * @return array<string, mixed>
     */
    private function studentRow(Person $student): array
    {
        $currentEnrollment = $student->enrollments->first();
        $role = $student->roles->first();

        return [
            'id' => $student->id,
            'fullName' => $student->full_name,
            'studentNumber' => $role?->reference_number ?? $currentEnrollment?->student_number,
            'email' => $student->email,
            'phone' => $student->phone,
            'status' => $student->status,
            'enrollmentsCount' => (int) $student->campus_enrollments_count,
            'documentSummary' => $this->documentSummary($student),
            'transferSummary' => [
                'openEvaluations' => $student->transferCreditEvaluations
                    ->whereIn('status', ['draft', 'in_review'])
                    ->count(),
                'creditedSubjects' => $student->transferCreditEvaluations
                    ->flatMap->subjects
                    ->where('status', 'credited')
                    ->count(),
            ],
            'currentEnrollment' => $currentEnrollment ? [
                'id' => $currentEnrollment->id,
                'status' => $currentEnrollment->status->value,
                'classification' => $currentEnrollment->classification?->value,
                'period' => $currentEnrollment->period?->name,
                'term' => $currentEnrollment->period?->term?->name,
                'academicYear' => $currentEnrollment->period?->term?->academicYear?->name,
                'program' => $currentEnrollment->curriculum?->program?->name,
                'curriculum' => $currentEnrollment->curriculum?->name,
                'section' => $currentEnrollment->section?->name,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function studentProfile(Person $student): array
    {
        $role = $student->roles->first();

        return [
            'id' => $student->id,
            'firstName' => $student->first_name,
            'middleName' => $student->middle_name,
            'lastName' => $student->last_name,
            'suffix' => $student->suffix,
            'fullName' => $student->full_name,
            'birthDate' => $student->birth_date?->toDateString(),
            'sex' => $student->sex,
            'email' => $student->email,
            'phone' => $student->phone,
            'address' => $student->address,
            'status' => $student->status,
            'studentNumber' => $role?->reference_number,
            'metadata' => $student->metadata ?? [],
            'profile' => $student->studentProfile ? [
                'psaBirthCertificateNumber' => $student->studentProfile->psa_birth_certificate_number,
                'learnerReferenceNumber' => $student->studentProfile->learner_reference_number,
                'nationality' => $student->studentProfile->nationality,
                'civilStatus' => $student->studentProfile->civil_status,
                'religion' => $student->studentProfile->religion,
                'motherTongue' => $student->studentProfile->mother_tongue,
                'isIndigenousPeople' => $student->studentProfile->is_indigenous_people,
                'indigenousCommunity' => $student->studentProfile->indigenous_community,
                'hasDisability' => $student->studentProfile->has_disability,
                'disabilityType' => $student->studentProfile->disability_type,
                'is4psBeneficiary' => $student->studentProfile->is_4ps_beneficiary,
                'fourPsHouseholdId' => $student->studentProfile->four_ps_household_id,
                'annualFamilyIncomeBracket' => $student->studentProfile->annual_family_income_bracket,
                'householdGrossIncome' => $student->studentProfile->household_gross_income,
                'hasGovernmentSubsidy' => $student->studentProfile->has_government_subsidy,
                'subsidyProgram' => $student->studentProfile->subsidy_program,
                'emergencyContactName' => $student->studentProfile->emergency_contact_name,
                'emergencyContactRelationship' => $student->studentProfile->emergency_contact_relationship,
                'emergencyContactPhone' => $student->studentProfile->emergency_contact_phone,
                'currentAddress' => $student->studentProfile->current_address ?? [],
                'permanentAddress' => $student->studentProfile->permanent_address ?? [],
                'previousSchoolName' => $student->studentProfile->previous_school_name,
                'previousSchoolAddress' => $student->studentProfile->previous_school_address,
                'previousSchoolType' => $student->studentProfile->previous_school_type,
                'lastGradeLevelCompleted' => $student->studentProfile->last_grade_level_completed,
                'lastSchoolYearAttended' => $student->studentProfile->last_school_year_attended,
                'seniorHighSchoolStrand' => $student->studentProfile->senior_high_school_strand,
                'collegeYearLevel' => $student->studentProfile->college_year_level,
                'reportingFlags' => $student->studentProfile->reporting_flags ?? [],
            ] : [],
            'documentSummary' => $this->documentSummary($student),
            'guardians' => $student->guardians
                ->map(fn (Person $guardian): array => [
                    'id' => $guardian->id,
                    'fullName' => $guardian->full_name,
                    'firstName' => $guardian->first_name,
                    'lastName' => $guardian->last_name,
                    'email' => $guardian->email,
                    'phone' => $guardian->phone,
                    'relationship' => $guardian->pivot->relationship,
                    'isPrimary' => (bool) $guardian->pivot->is_primary,
                    'hasPortalAccess' => (bool) $guardian->pivot->has_portal_access,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function documentSummary(Person $student): array
    {
        $verified = $student->studentDocuments
            ->where('status', 'verified')
            ->pluck('document_type')
            ->unique()
            ->values();
        $missing = collect(self::REQUIRED_DOCUMENT_TYPES)
            ->reject(fn (string $documentType): bool => $verified->contains($documentType))
            ->values();

        return [
            'required' => self::REQUIRED_DOCUMENT_TYPES,
            'verified' => $verified->all(),
            'missing' => $missing->all(),
            'verifiedCount' => $verified->count(),
            'requiredCount' => count(self::REQUIRED_DOCUMENT_TYPES),
            'ready' => $missing->isEmpty(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function studentDocument(StudentDocument $document): array
    {
        return [
            'id' => $document->id,
            'type' => $document->document_type,
            'label' => StudentDocument::TYPES[$document->document_type] ?? str($document->document_type)->replace('_', ' ')->title()->toString(),
            'originalName' => $document->original_name,
            'mimeType' => $document->mime_type,
            'size' => $document->size,
            'status' => $document->status,
            'issuedOn' => $document->issued_on?->toDateString(),
            'expiresOn' => $document->expires_on?->toDateString(),
            'reviewedAt' => $document->reviewed_at?->toISOString(),
            'notes' => $document->notes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transferCreditEvaluation(TransferCreditEvaluation $evaluation): array
    {
        return [
            'id' => $evaluation->id,
            'sourceSchoolName' => $evaluation->source_school_name,
            'sourceSchoolAddress' => $evaluation->source_school_address,
            'previousProgram' => $evaluation->previous_program,
            'status' => $evaluation->status,
            'curriculum' => $evaluation->curriculum?->name,
            'program' => $evaluation->curriculum?->program?->name,
            'evaluator' => $evaluation->evaluator?->name,
            'evaluatedAt' => $evaluation->evaluated_at?->toISOString(),
            'notes' => $evaluation->notes,
            'creditedUnits' => $evaluation->subjects
                ->where('status', 'credited')
                ->sum(fn (TransferCreditSubject $subject): float => (float) ($subject->credited_units ?? 0)),
            'subjects' => $evaluation->subjects
                ->map(fn (TransferCreditSubject $subject): array => [
                    'id' => $subject->id,
                    'curriculumItemId' => $subject->curriculum_item_id,
                    'previousSubjectCode' => $subject->previous_subject_code,
                    'previousSubjectName' => $subject->previous_subject_name,
                    'previousUnits' => $subject->previous_units,
                    'previousGrade' => $subject->previous_grade,
                    'schoolYear' => $subject->school_year,
                    'term' => $subject->term,
                    'status' => $subject->status,
                    'creditedUnits' => $subject->credited_units,
                    'remarks' => $subject->remarks,
                    'matchedSubject' => $subject->curriculumItem?->subject?->name,
                    'matchedSubjectCode' => $subject->curriculumItem?->subject?->code,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function enrollmentProfile(Enrollment $enrollment): array
    {
        return [
            'id' => $enrollment->id,
            'studentNumber' => $enrollment->student_number,
            'classification' => $enrollment->classification?->value,
            'status' => $enrollment->status->value,
            'period' => $enrollment->period?->name,
            'term' => $enrollment->period?->term?->name,
            'academicYear' => $enrollment->period?->term?->academicYear?->name,
            'curriculum' => $enrollment->curriculum?->name,
            'program' => $enrollment->curriculum?->program?->name,
            'section' => $enrollment->section?->name,
            'approvedAt' => $enrollment->approved_at?->toISOString(),
            'notes' => $enrollment->notes,
            'assessment' => $enrollment->assessment ? [
                'currency' => $enrollment->assessment->currency,
                'tuitionTotal' => $enrollment->assessment->tuition_total,
                'laboratoryTotal' => $enrollment->assessment->laboratory_total,
                'miscellaneousTotal' => $enrollment->assessment->miscellaneous_total,
                'total' => $enrollment->assessment->total,
                'assessedAt' => $enrollment->assessment->assessed_at?->toISOString(),
            ] : null,
            'subjects' => $enrollment->subjects
                ->map(fn (EnrollmentSubject $subject): array => $this->enrollmentSubject($subject, $enrollment))
                ->values()
                ->all(),
            'assessmentLines' => $enrollment->assessment?->lines
                ->map(fn ($line): array => [
                    'id' => $line->id,
                    'type' => $line->type,
                    'code' => $line->code,
                    'description' => $line->description,
                    'quantity' => $line->quantity,
                    'unitAmount' => $line->unit_amount,
                    'amount' => $line->amount,
                    'curriculumItemId' => $line->curriculum_item_id,
                ])
                ->values()
                ->all() ?? [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function enrollmentSubject(EnrollmentSubject $subject, Enrollment $enrollment): array
    {
        $curriculumItem = $subject->curriculumItem;
        $classOffering = $subject->classOffering;
        $assessmentLine = $enrollment->assessment?->lines
            ->firstWhere('curriculum_item_id', $subject->curriculum_item_id);

        return [
            'id' => $subject->id,
            'curriculumItemId' => $subject->curriculum_item_id,
            'classOfferingId' => $subject->class_offering_id,
            'subjectName' => $curriculumItem?->subject?->name,
            'subjectCode' => $curriculumItem?->subject?->code,
            'creditUnits' => $curriculumItem?->credit_units,
            'contactHours' => $curriculumItem?->contact_hours,
            'labHours' => $curriculumItem?->lab_hours,
            'isRequired' => (bool) $curriculumItem?->is_required,
            'status' => $subject->status,
            'finalResult' => $subject->final_result,
            'classOffering' => $classOffering ? [
                'id' => $classOffering->id,
                'name' => $classOffering->subject?->name ?? $classOffering->name,
                'code' => $classOffering->subject?->code ?? $classOffering->code,
                'teacher' => $classOffering->teacher?->full_name,
                'section' => $classOffering->section?->name,
            ] : null,
            'assessmentAmount' => $assessmentLine?->amount,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function options(Campus $campus): array
    {
        $programs = Program::query()
            ->whereBelongsTo($campus)
            ->with(['curricula' => fn ($query) => $query->orderBy('name')])
            ->orderBy('name')
            ->get(['id', 'campus_id', 'name', 'code']);

        return [
            'statuses' => ['active', 'inactive', 'graduated', 'transferred'],
            'incomeBrackets' => [
                'below_100000' => 'Below PHP 100,000',
                '100000_250000' => 'PHP 100,000 - 250,000',
                '250001_400000' => 'PHP 250,001 - 400,000',
                '400001_800000' => 'PHP 400,001 - 800,000',
                'above_800000' => 'Above PHP 800,000',
            ],
            'documentTypes' => collect(StudentDocument::TYPES)
                ->map(fn (string $label, string $value): array => ['value' => $value, 'label' => $label, 'required' => in_array($value, self::REQUIRED_DOCUMENT_TYPES, true)])
                ->values()
                ->all(),
            'documentStatuses' => collect(StudentDocument::STATUSES)
                ->map(fn (string $status): array => ['value' => $status, 'label' => str($status)->replace('_', ' ')->title()->toString()])
                ->values()
                ->all(),
            'transferStatuses' => collect(TransferCreditEvaluation::STATUSES)
                ->map(fn (string $status): array => ['value' => $status, 'label' => str($status)->replace('_', ' ')->title()->toString()])
                ->values()
                ->all(),
            'transferSubjectStatuses' => collect(TransferCreditSubject::STATUSES)
                ->map(fn (string $status): array => ['value' => $status, 'label' => str($status)->replace('_', ' ')->title()->toString()])
                ->values()
                ->all(),
            'enrollmentStatuses' => collect(EnrollmentStatus::cases())
                ->map(fn (EnrollmentStatus $status): array => ['value' => $status->value, 'label' => str($status->value)->replace('_', ' ')->title()->toString()])
                ->values()
                ->all(),
            'classifications' => collect(EnrollmentClassification::cases())
                ->map(fn (EnrollmentClassification $classification): array => ['value' => $classification->value, 'label' => str($classification->value)->replace('_', ' ')->title()->toString()])
                ->values()
                ->all(),
            'periods' => EnrollmentPeriod::query()
                ->whereBelongsTo($campus)
                ->with('term.academicYear')
                ->orderByDesc('opens_at')
                ->get(['id', 'campus_id', 'term_id', 'name', 'active'])
                ->map(fn (EnrollmentPeriod $period): array => [
                    'id' => $period->id,
                    'name' => $period->name,
                    'termId' => $period->term_id,
                    'term' => $period->term?->name,
                    'academicYear' => $period->term?->academicYear?->name,
                    'active' => $period->active,
                ])
                ->all(),
            'terms' => Term::query()
                ->whereHas('academicYear', fn (Builder $query): Builder => $query->where('institution_id', $campus->institution_id))
                ->orderByDesc('starts_on')
                ->get(['id', 'academic_year_id', 'name', 'code'])
                ->map(fn (Term $term): array => ['id' => $term->id, 'name' => $term->name, 'code' => $term->code])
                ->all(),
            'programs' => $programs
                ->map(fn (Program $program): array => [
                    'id' => $program->id,
                    'name' => $program->name,
                    'code' => $program->code,
                    'curricula' => $program->curricula
                        ->map(fn (Curriculum $curriculum): array => [
                            'id' => $curriculum->id,
                            'name' => $curriculum->name,
                            'code' => $curriculum->code,
                        ])
                        ->values()
                        ->all(),
                ])
                ->all(),
            'sections' => Section::query()
                ->where('campus_id', $campus->getKey())
                ->orderBy('name')
                ->get(['id', 'program_id', 'term_id', 'name', 'code', 'year_level', 'capacity'])
                ->map(fn (Section $section): array => [
                    'id' => $section->id,
                    'programId' => $section->program_id,
                    'termId' => $section->term_id,
                    'name' => $section->name,
                    'code' => $section->code,
                    'yearLevel' => $section->year_level,
                    'capacity' => $section->capacity,
                ])
                ->all(),
            'classOfferings' => ClassOffering::query()
                ->whereBelongsTo($campus)
                ->with(['subject:id,name,code', 'teacher:id,first_name,middle_name,last_name,suffix', 'section:id,name'])
                ->where('status', 'active')
                ->orderBy('code')
                ->get(['id', 'campus_id', 'term_id', 'subject_id', 'section_id', 'teacher_id', 'name', 'code'])
                ->map(fn (ClassOffering $classOffering): array => [
                    'id' => $classOffering->id,
                    'termId' => $classOffering->term_id,
                    'subjectId' => $classOffering->subject_id,
                    'name' => $classOffering->subject?->name ?? $classOffering->name,
                    'code' => $classOffering->subject?->code ?? $classOffering->code,
                    'teacher' => $classOffering->teacher?->full_name,
                    'section' => $classOffering->section?->name,
                ])
                ->all(),
            'curriculumItems' => CurriculumItem::query()
                ->whereHas('curriculum.program', fn (Builder $query): Builder => $query->where('campus_id', $campus->getKey()))
                ->with(['subject:id,name,code', 'electiveGroup:id,name'])
                ->orderBy('year_level')
                ->orderBy('term_sequence')
                ->orderBy('position')
                ->get(['id', 'curriculum_id', 'subject_id', 'year_level', 'term_sequence', 'credit_units', 'is_required', 'elective_group_id'])
                ->map(fn (CurriculumItem $item): array => [
                    'id' => $item->id,
                    'curriculumId' => $item->curriculum_id,
                    'subjectId' => $item->subject_id,
                    'subjectName' => $item->subject?->name,
                    'subjectCode' => $item->subject?->code,
                    'yearLevel' => $item->year_level,
                    'termSequence' => $item->term_sequence,
                    'creditUnits' => $item->credit_units,
                    'isRequired' => $item->is_required,
                    'electiveGroup' => $item->electiveGroup?->name,
                    'electiveGroupId' => $item->elective_group_id,
                ])
                ->all(),
        ];
    }

    private function documentGapCount(Campus $campus): int
    {
        return Person::query()
            ->whereHas('roles', fn (Builder $query): Builder => $query
                ->where('campus_id', $campus->getKey())
                ->where('role', PersonRole::Student)
                ->where('active', true))
            ->where(function (Builder $query) use ($campus): void {
                foreach (self::REQUIRED_DOCUMENT_TYPES as $documentType) {
                    $query->orWhereDoesntHave(
                        'studentDocuments',
                        fn (Builder $query): Builder => $query
                            ->where('campus_id', $campus->getKey())
                            ->where('document_type', $documentType)
                            ->where('status', 'verified'),
                    );
                }
            })
            ->count();
    }
}
