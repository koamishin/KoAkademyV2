<?php

declare(strict_types=1);

use App\Enums\RoleEnums;
use App\Filament\Resources\FeatureFlag\FeatureFlagResource;
use App\Models\Campus;
use App\Models\Institution;
use App\Models\Role;
use App\Models\RoleFeature;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;

beforeEach(function (): void {
    Role::query()->firstOrCreate([
        'campus_id' => null,
        'name' => RoleEnums::SUPER_ADMIN->value,
        'guard_name' => 'web',
    ]);

    $institution = Institution::query()->create([
        'name' => 'Ko Academy',
        'code' => 'KO',
    ]);

    $this->campus = Campus::query()->create([
        'institution_id' => $institution->id,
        'name' => 'Main Campus',
        'code' => 'MAIN',
    ]);

    $this->administrator = User::factory()->create();
    $this->administrator->campusMemberships()->create([
        'campus_id' => $this->campus->id,
        'role' => RoleEnums::SUPER_ADMIN,
        'active' => true,
        'is_default' => true,
    ]);

    $this->actingAs($this->administrator);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($this->campus);
    Filament::bootCurrentPanel();
});

test('feature flags page displays the active feature count for each role', function (): void {
    $role = Role::query()
        ->whereBelongsTo($this->campus)
        ->where('name', RoleEnums::SUPER_ADMIN->value)
        ->firstOrFail();

    RoleFeature::query()->create([
        'role_id' => $role->id,
        'feature' => 'settings_profile',
        'active' => true,
    ]);

    RoleFeature::query()->create([
        'role_id' => $role->id,
        'feature' => 'beta-features',
        'active' => false,
    ]);

    $response = $this->get(FeatureFlagResource::getUrl(
        name: 'index',
        panel: 'admin',
        tenant: $this->campus,
    ));

    $response->assertSuccessful();

    $roleWithActiveFeatureCount = Role::query()
        ->withCount([
            'features' => fn (Builder $query): Builder => $query->where('active', true),
        ])
        ->findOrFail($role->id);

    expect($roleWithActiveFeatureCount->features_count)->toBe(1);
});
