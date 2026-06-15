<?php

use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\Institution;
use App\Models\Person;
use App\Models\PersonRoleAssignment;
use App\Models\Term;
use App\Models\User;
use Modules\Admissions\Actions\AcceptApplication;
use Modules\Admissions\Enums\ApplicationStatus;
use Modules\Admissions\Models\AdmissionPeriod;
use Modules\Admissions\Models\Application;
use Spatie\Permission\Models\Role;

test('accepting an applicant creates a student role without duplicating the person', function (): void {
    $actor = User::factory()->create();
    $applicant = User::factory()->create();
    Role::query()->create(['name' => 'student', 'guard_name' => 'web']);
    $institution = Institution::query()->create(['name' => 'Ko Academy', 'code' => 'KO']);
    $campus = Campus::query()->create(['institution_id' => $institution->id, 'name' => 'Main', 'code' => 'MAIN']);
    $academicYear = AcademicYear::query()->create(['institution_id' => $institution->id, 'name' => '2026-2027', 'starts_on' => '2026-06-01', 'ends_on' => '2027-03-31']);
    $term = Term::query()->create(['academic_year_id' => $academicYear->id, 'name' => 'First Term', 'code' => 'T1', 'sequence' => 1, 'starts_on' => '2026-06-01', 'ends_on' => '2026-10-31']);
    $person = Person::query()->create(['user_id' => $applicant->id, 'first_name' => 'Ana', 'last_name' => 'Reyes', 'email' => $applicant->email]);
    $admissionPeriod = AdmissionPeriod::query()->create(['campus_id' => $campus->id, 'term_id' => $term->id, 'name' => 'First Intake', 'opens_at' => now()->subDay(), 'closes_at' => now()->addDay()]);
    $application = Application::query()->create(['person_id' => $person->id, 'admission_period_id' => $admissionPeriod->id, 'status' => ApplicationStatus::Submitted]);

    app(AcceptApplication::class)->execute($application, $actor);

    expect($application->refresh()->status)->toBe(ApplicationStatus::Accepted)
        ->and(Person::query()->count())->toBe(1)
        ->and(PersonRoleAssignment::query()->where('person_id', $person->id)->where('role', 'student')->exists())->toBeTrue();
});
