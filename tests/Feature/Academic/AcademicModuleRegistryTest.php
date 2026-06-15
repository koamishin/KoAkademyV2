<?php

use App\Academic\AcademicModuleRegistry;
use App\Models\AcademicModuleSetting;

test('academic core is always application functionality and not a module', function (): void {
    $academicModuleRegistry = app(AcademicModuleRegistry::class);

    expect($academicModuleRegistry->get('academic-core'))->toBeNull()
        ->and($academicModuleRegistry->enabled('academic-core'))->toBeFalse()
        ->and(AcademicModuleSetting::query()->where('module', 'academic-core')->exists())->toBeFalse();
});

test('admissions and enrollment can be enabled independently', function (): void {
    $academicModuleRegistry = app(AcademicModuleRegistry::class);
    $academicModuleRegistry->setEnabled('admissions', false);
    $academicModuleRegistry->setEnabled('enrollment', true);

    expect($academicModuleRegistry->enabled('admissions'))->toBeFalse()
        ->and($academicModuleRegistry->enabled('enrollment'))->toBeTrue();
});

test('disabling enrollment disables classroom', function (): void {
    $academicModuleRegistry = app(AcademicModuleRegistry::class);
    $academicModuleRegistry->setEnabled('classroom', true);
    $academicModuleRegistry->setEnabled('enrollment', false);

    expect($academicModuleRegistry->enabled('enrollment'))->toBeFalse()
        ->and($academicModuleRegistry->enabled('classroom'))->toBeFalse();
});
