<?php

use App\Enums\RoleEnums;
use App\Models\Campus;
use App\Models\Institution;
use App\Models\User;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('requires authentication', function (): void {
    get(route('dashboard'))
        ->assertRedirect(route('login'));
});

it('renders the dashboard page for authenticated users', function (): void {
    $user = User::factory()->create();
    $institution = Institution::query()->create([
        'name' => 'Ko Academy',
        'code' => 'KO',
    ]);
    $campus = Campus::query()->create([
        'institution_id' => $institution->id,
        'name' => 'Main Campus',
        'code' => 'MAIN',
    ]);
    Role::query()->firstOrCreate([
        'campus_id' => null,
        'name' => RoleEnums::STUDENT->value,
        'guard_name' => 'web',
    ]);
    $user->campusMemberships()->create([
        'campus_id' => $campus->id,
        'role' => RoleEnums::STUDENT,
        'active' => true,
        'is_default' => true,
    ]);

    actingAs($user)
        ->get(route('campus.dashboard', ['campus' => $campus]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/StudentDashboard')
            ->has('student')
            ->has('academicContext')
            ->has('enrollment')
            ->has('todaySchedule')
            ->has('upcomingAssignments')
            ->has('gradeSummary')
            ->has('recentAnnouncements')
            ->has('stats')
        );
});
