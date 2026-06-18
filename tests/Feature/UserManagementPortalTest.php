<?php

use App\Enums\RoleEnums;
use App\Models\Campus;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

function umCampus(string $name = 'Main Campus', string $code = 'MAIN'): Campus
{
    $institution = Institution::query()->create([
        'name' => "Institution {$code}",
        'code' => $code,
    ]);

    return Campus::query()->create([
        'institution_id' => $institution->id,
        'name' => $name,
        'code' => $code,
    ]);
}

function umUser(Campus $campus, RoleEnums $role, array $attributes = []): User
{
    $user = User::factory()->create($attributes);

    $user->campusMemberships()->create([
        'campus_id' => $campus->id,
        'role' => $role,
        'active' => true,
        'is_default' => true,
    ]);

    return $user->refresh();
}

function umSession(User $user, int $lastActivity, string $id): void
{
    DB::table('sessions')->insert([
        'id' => $id,
        'user_id' => $user->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Mozilla/5.0 Windows Chrome/125.0',
        'payload' => 'payload',
        'last_activity' => $lastActivity,
    ]);
}

function umNavigationTitles($response): array
{
    return collect($response->inertiaProps('portal.navigation'))
        ->flatMap(fn (array $group): array => collect($group['items'])->pluck('title')->all())
        ->values()
        ->all();
}

test('admin portal navigation includes users', function (): void {
    $campus = umCampus();
    $admin = umUser($campus, RoleEnums::SCHOOL_ADMIN);

    $response = $this->actingAs($admin)
        ->get(route('campus.dashboard', ['campus' => $campus]))
        ->assertOk();

    expect(umNavigationTitles($response))->toContain('Users');
});

test('students and teachers cannot access admin user management', function (RoleEnums $role): void {
    $campus = umCampus();
    $user = umUser($campus, $role);

    $this->actingAs($user)
        ->get(route('admin.users.index', ['campus' => $campus]))
        ->assertForbidden();
})->with([RoleEnums::STUDENT, RoleEnums::TEACHER]);

test('super admins can view users across campuses', function (): void {
    $campus = umCampus();
    $otherCampus = umCampus('North Campus', 'NORTH');
    $admin = umUser($campus, RoleEnums::SUPER_ADMIN);
    $otherUser = umUser($otherCampus, RoleEnums::STUDENT, ['name' => 'North Student']);

    $this->actingAs($admin)
        ->get(route('admin.users.index', ['campus' => $campus]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('user-management/AdminUsers')
            ->where('can.viewGlobal', true)
            ->where('analytics.cards.totalUsers', 2)
            ->where('users.data.1.name', $otherUser->name)
        );
});

test('school admins are scoped to current campus users', function (): void {
    $campus = umCampus();
    $otherCampus = umCampus('North Campus', 'NORTH');
    $admin = umUser($campus, RoleEnums::SCHOOL_ADMIN);
    $student = umUser($campus, RoleEnums::STUDENT, ['name' => 'Main Student']);
    umUser($otherCampus, RoleEnums::STUDENT, ['name' => 'North Student']);

    $response = $this->actingAs($admin)
        ->get(route('admin.users.index', ['campus' => $campus]))
        ->assertOk();

    $names = collect($response->inertiaProps('users.data'))->pluck('name');

    expect($names)
        ->toContain($admin->name, $student->name)
        ->not->toContain('North Student');
});

test('non school admin roles can view but cannot mutate users', function (): void {
    $campus = umCampus();
    $registrar = umUser($campus, RoleEnums::REGISTRAR);
    $student = umUser($campus, RoleEnums::STUDENT);

    $this->actingAs($registrar)
        ->get(route('admin.users.index', ['campus' => $campus]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('can.manage', false));

    $this->actingAs($registrar)
        ->post(route('admin.users.verify-email', ['campus' => $campus, 'user' => $student]))
        ->assertForbidden();
});

test('filters return expected user subsets', function (): void {
    $campus = umCampus();
    $admin = umUser($campus, RoleEnums::SCHOOL_ADMIN);
    $teacher = umUser($campus, RoleEnums::TEACHER, ['name' => 'Focused Teacher']);
    umUser($campus, RoleEnums::STUDENT, ['name' => 'Focused Student']);

    $response = $this->actingAs($admin)
        ->get(route('admin.users.index', ['campus' => $campus, 'role' => RoleEnums::TEACHER->value]))
        ->assertOk();

    expect(collect($response->inertiaProps('users.data'))->pluck('name'))
        ->toContain($teacher->name)
        ->not->toContain('Focused Student');
});

test('analytics and online monitoring count active sessions only', function (): void {
    $campus = umCampus();
    $admin = umUser($campus, RoleEnums::SCHOOL_ADMIN);
    $student = umUser($campus, RoleEnums::STUDENT);
    $teacher = umUser($campus, RoleEnums::TEACHER);

    umSession($student, now()->timestamp, 'student-online');
    umSession($teacher, now()->subMinutes(10)->timestamp, 'teacher-stale');

    $this->actingAs($admin)
        ->get(route('admin.users.index', ['campus' => $campus]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('analytics.cards.students', 1)
            ->where('analytics.cards.teachers', 1)
            ->where('analytics.cards.onlineNow', 1)
            ->has('onlineUsers', 1)
            ->where('onlineUsers.0.id', $student->id)
        );
});

test('school admins can create operational users and send a password reset link', function (): void {
    Notification::fake();

    $campus = umCampus();
    $admin = umUser($campus, RoleEnums::SCHOOL_ADMIN);

    $this->actingAs($admin)
        ->post(route('admin.users.store', ['campus' => $campus]), [
            'name' => 'New Student',
            'email' => 'new-student@example.com',
            'email_verified' => true,
            'memberships' => [[
                'campus_id' => $campus->id,
                'role' => RoleEnums::STUDENT->value,
                'active' => true,
                'is_default' => true,
            ]],
        ])
        ->assertRedirect();

    $created = User::query()->where('email', 'new-student@example.com')->firstOrFail();

    expect($created->campusMemberships()->where('role', RoleEnums::STUDENT->value)->exists())->toBeTrue();
    Notification::assertSentTo($created, ResetPassword::class);
});

test('school admins can update manageable users', function (): void {
    $campus = umCampus();
    $admin = umUser($campus, RoleEnums::SCHOOL_ADMIN);
    $student = umUser($campus, RoleEnums::STUDENT, ['name' => 'Old Name']);

    $this->actingAs($admin)
        ->patch(route('admin.users.update', ['campus' => $campus, 'user' => $student]), [
            'name' => 'Updated Name',
            'email' => $student->email,
            'email_verified' => false,
            'memberships' => [[
                'campus_id' => $campus->id,
                'role' => RoleEnums::TEACHER->value,
                'active' => true,
                'is_default' => true,
            ]],
        ])
        ->assertRedirect();

    expect($student->refresh()->name)->toBe('Updated Name')
        ->and($student->email_verified_at)->toBeNull()
        ->and($student->campusMemberships()->first()->role)->toBe(RoleEnums::TEACHER);
});

test('email verification actions update the user', function (): void {
    $campus = umCampus();
    $admin = umUser($campus, RoleEnums::SCHOOL_ADMIN);
    $student = umUser($campus, RoleEnums::STUDENT)->forceFill(['email_verified_at' => null]);
    $student->save();

    $this->actingAs($admin)
        ->post(route('admin.users.verify-email', ['campus' => $campus, 'user' => $student]))
        ->assertRedirect();

    expect($student->refresh()->email_verified_at)->not->toBeNull();

    $this->actingAs($admin)
        ->post(route('admin.users.unverify-email', ['campus' => $campus, 'user' => $student]))
        ->assertRedirect();

    expect($student->refresh()->email_verified_at)->toBeNull();
});

test('force logout removes target sessions without touching the admin session', function (): void {
    $campus = umCampus();
    $admin = umUser($campus, RoleEnums::SCHOOL_ADMIN);
    $student = umUser($campus, RoleEnums::STUDENT);

    umSession($admin, now()->timestamp, 'admin-session');
    umSession($student, now()->timestamp, 'student-session-one');
    umSession($student, now()->timestamp, 'student-session-two');

    $this->actingAs($admin)
        ->delete(route('admin.users.force-logout', ['campus' => $campus, 'user' => $student]))
        ->assertRedirect();

    expect(DB::table('sessions')->where('user_id', $student->id)->count())->toBe(0)
        ->and(DB::table('sessions')->where('user_id', $admin->id)->count())->toBe(1);
});

test('impersonation starts only for allowed targets', function (): void {
    $campus = umCampus();
    $admin = umUser($campus, RoleEnums::SCHOOL_ADMIN);
    $student = umUser($campus, RoleEnums::STUDENT);

    $this->actingAs($admin)
        ->post(route('admin.users.impersonate', ['campus' => $campus, 'user' => $student]))
        ->assertRedirect(route('impersonate.take-redirect'))
        ->assertSessionHas(config('laravel-impersonate.session_key'), $admin->id);
});
