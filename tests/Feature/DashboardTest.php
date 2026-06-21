<?php

use App\Enums\RoleEnums;
use App\Models\Campus;
use App\Models\Institution;
use App\Models\User;
use Spatie\Permission\Models\Role;

test('guests are redirected to the login page', function (): void {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function (): void {
    $user = User::factory()->create();
    $institution = Institution::query()->create(['name' => 'Ko Academy', 'code' => 'KO']);
    $campus = Campus::query()->create(['institution_id' => $institution->id, 'name' => 'Main', 'code' => 'MAIN']);
    Role::query()->create(['campus_id' => null, 'name' => RoleEnums::STUDENT->value, 'guard_name' => 'web']);
    $user->campusMemberships()->create([
        'campus_id' => $campus->id,
        'role' => RoleEnums::STUDENT,
        'is_default' => true,
    ]);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('campus.dashboard', ['campus' => $campus]));
});

test('dashboard redirect repairs legacy campuses without slugs', function (): void {
    $user = User::factory()->create();
    $institution = Institution::query()->create(['name' => 'Ko Academy', 'code' => 'KO']);
    $campus = Campus::query()->create(['institution_id' => $institution->id, 'name' => 'Main', 'code' => 'MAIN']);
    $campus->forceFill(['slug' => null])->save();
    Role::query()->create(['campus_id' => null, 'name' => RoleEnums::STUDENT->value, 'guard_name' => 'web']);
    $user->campusMemberships()->create([
        'campus_id' => $campus->id,
        'role' => RoleEnums::STUDENT,
        'is_default' => true,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('campus.dashboard', ['campus' => $campus->fresh()]));

    expect($campus->fresh()->slug)->toBe('main');
});
