<?php

use App\Enums\RoleEnums;
use App\Models\Campus;
use App\Models\Curriculum;
use App\Models\CurriculumItem;
use App\Models\EducationLevel;
use App\Models\Institution;
use App\Models\PersonRoleAssignment;
use App\Models\Program;
use App\Models\Room;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use App\Settings\ApplicationDetailsSettings;
use App\Settings\ApplicationSetupSettings;
use Database\Seeders\Academic\AcademicFoundationSeeder;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use Modules\Classroom\Models\ClassOffering;
use Modules\Enrollment\Models\Enrollment;
use Modules\Enrollment\Models\EnrollmentAssessment;
use Modules\Enrollment\Models\EnrollmentPeriod;
use Modules\Enrollment\Models\StudentDocument;
use Modules\Enrollment\Models\StudentProfile;

test('academic foundation seeder creates the full multi-level academic setup', function (): void {
    $this->seed(AcademicFoundationSeeder::class);

    $institution = Institution::query()->where('code', 'KOA')->sole();
    $levelCodes = ['ELEM', 'JHS', 'SHS', 'COL'];

    expect(Campus::query()->where('institution_id', $institution->id)->count())->toBe(3)
        ->and(Term::query()->count())->toBe(3)
        ->and(Room::query()->count())->toBe(15)
        ->and(EnrollmentPeriod::query()->count())->toBe(9)
        ->and(ClassOffering::query()->count())->toBeGreaterThanOrEqual(16)
        ->and(Section::query()->count())->toBeGreaterThanOrEqual(8);

    foreach (['MAIN', 'SOUTH', 'COLLEGE'] as $campusCode) {
        $campus = Campus::query()->where('code', $campusCode)->sole();
        $curriculaCount = Curriculum::query()
            ->whereHas('program', fn ($query) => $query->where('campus_id', $campus->getKey()))
            ->count();

        expect($curriculaCount)->toBeGreaterThanOrEqual(2);
    }

    expect(Curriculum::query()
        ->where('status', 'active')
        ->whereHas('program', fn ($query) => $query->where('status', 'active')->whereHas('campus', fn ($campusQuery) => $campusQuery->where('code', 'MAIN')))
        ->count())->toBe(2)
        ->and(Curriculum::query()
            ->where('status', 'active')
            ->whereHas('program', fn ($query) => $query->where('status', 'active')->whereHas('campus', fn ($campusQuery) => $campusQuery->where('code', 'SOUTH')))
            ->count())->toBe(4)
        ->and(Curriculum::query()
            ->where('status', 'active')
            ->whereHas('program', fn ($query) => $query->where('status', 'active')->whereHas('campus', fn ($campusQuery) => $campusQuery->where('code', 'COLLEGE')))
            ->count())->toBe(2);

    foreach ($levelCodes as $code) {
        $level = EducationLevel::query()
            ->where('institution_id', $institution->id)
            ->where('code', $code)
            ->sole();

        $curriculaCount = Curriculum::query()
            ->whereHas('program', fn ($query) => $query->where('education_level_id', $level->id))
            ->count();

        expect($curriculaCount)->toBeGreaterThanOrEqual(2);
    }

    Curriculum::query()->each(function (Curriculum $curriculum): void {
        expect(CurriculumItem::query()->where('curriculum_id', $curriculum->id)->count())
            ->toBeGreaterThanOrEqual(5);
    });

    expect(Program::query()->count())->toBe(5)
        ->and(Curriculum::query()->count())->toBe(8)
        ->and(Subject::query()->count())->toBeGreaterThanOrEqual(35);

    $applicationSetupSettings = app(ApplicationSetupSettings::class);
    $applicationSetupSettings->refresh();
    $applicationDetailsSettings = app(ApplicationDetailsSettings::class);
    $applicationDetailsSettings->refresh();

    expect($applicationSetupSettings->isComplete())->toBeTrue()
        ->and($applicationSetupSettings->current_step)->toBe(7)
        ->and($applicationSetupSettings->completed_at)->not->toBeNull()
        ->and($applicationDetailsSettings->site_name)->toBe('Ko Academy')
        ->and($applicationDetailsSettings->timezone)->toBe('Asia/Manila');
});

test('academic foundation seeder is idempotent for editable reference data', function (): void {
    $this->seed(AcademicFoundationSeeder::class);

    $counts = [
        'campuses' => Campus::query()->count(),
        'education_levels' => EducationLevel::query()->count(),
        'programs' => Program::query()->count(),
        'curricula' => Curriculum::query()->count(),
        'curriculum_items' => CurriculumItem::query()->count(),
        'sections' => Section::query()->count(),
        'class_offerings' => ClassOffering::query()->count(),
        'enrollment_periods' => EnrollmentPeriod::query()->count(),
    ];

    $this->seed(AcademicFoundationSeeder::class);

    foreach ($counts as $table => $count) {
        expect(match ($table) {
            'campuses' => Campus::query()->count(),
            'education_levels' => EducationLevel::query()->count(),
            'programs' => Program::query()->count(),
            'curricula' => Curriculum::query()->count(),
            'curriculum_items' => CurriculumItem::query()->count(),
            'sections' => Section::query()->count(),
            'class_offerings' => ClassOffering::query()->count(),
            'enrollment_periods' => EnrollmentPeriod::query()->count(),
        })->toBe($count);
    }
});

test('default database seeder includes the academic foundation', function (): void {
    $this->seed();

    expect(Institution::query()->where('code', 'KOA')->exists())->toBeTrue()
        ->and(EducationLevel::query()->whereIn('code', ['ELEM', 'JHS', 'SHS', 'COL'])->count())->toBe(4)
        ->and(Curriculum::query()->count())->toBe(8)
        ->and(EnrollmentPeriod::query()->count())->toBe(9);
});

test('default database seeder creates 200 student records and enrollments for each seeded campus', function (): void {
    $this->seed();

    foreach (['MAIN', 'SOUTH', 'COLLEGE'] as $campusCode) {
        $campus = Campus::query()->where('code', $campusCode)->sole();

        expect(PersonRoleAssignment::query()
            ->where('campus_id', $campus->getKey())
            ->where('role', 'student')
            ->count())->toBe(200)
            ->and(StudentProfile::query()->where('campus_id', $campus->getKey())->count())->toBe(200)
            ->and(Enrollment::query()->where('campus_id', $campus->getKey())->count())->toBe(200)
            ->and(StudentDocument::query()->where('campus_id', $campus->getKey())->count())->toBe(800);
    }

    expect(PersonRoleAssignment::query()->where('role', 'student')->count())->toBe(600)
        ->and(PersonRoleAssignment::query()->where('role', 'guardian')->count())->toBe(600)
        ->and(StudentProfile::query()->count())->toBe(600)
        ->and(Enrollment::query()->count())->toBe(600)
        ->and(EnrollmentAssessment::query()->count())->toBe(600)
        ->and(StudentDocument::query()->count())->toBe(2400);

    expect(enrollmentCountForProgram('MAIN', 'ELEM'))->toBe(200)
        ->and(enrollmentCountForProgram('SOUTH', 'JHS'))->toBe(100)
        ->and(enrollmentCountForProgram('SOUTH', 'SHS'))->toBe(100)
        ->and(enrollmentCountForProgram('COLLEGE', 'BSBA'))->toBe(100)
        ->and(enrollmentCountForProgram('COLLEGE', 'BSIT'))->toBe(100);
});

function enrollmentCountForProgram(string $campusCode, string $programCode): int
{
    return Enrollment::query()
        ->whereHas('campus', fn ($query) => $query->where('code', $campusCode))
        ->whereHas('curriculum.program', fn ($query) => $query->where('code', $programCode))
        ->count();
}

test('seeded application setup unlocks the normal filament dashboard', function (): void {
    $this->seed();

    $campus = Campus::query()->where('code', 'MAIN')->sole();
    $user = User::factory()->create();
    $user->campusMemberships()->create([
        'campus_id' => $campus->getKey(),
        'role' => RoleEnums::SUPER_ADMIN,
        'active' => true,
        'is_default' => true,
    ]);

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->get(Dashboard::getUrl(panel: 'admin', tenant: $campus))
        ->assertSuccessful();
});
