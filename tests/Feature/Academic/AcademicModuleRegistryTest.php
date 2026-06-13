<?php

use App\Academic\AcademicModuleRegistry;
use App\Models\AcademicModuleSetting;

test('academic core is always application functionality and not a module', function (): void {
    $registry = app(AcademicModuleRegistry::class);

    expect($registry->get('academic-core'))->toBeNull()
        ->and($registry->enabled('academic-core'))->toBeFalse()
        ->and(AcademicModuleSetting::query()->where('module', 'academic-core')->exists())->toBeFalse();
});

test('admissions and enrollment can be enabled independently', function (): void {
    $registry = app(AcademicModuleRegistry::class);
    $registry->setEnabled('admissions', false);
    $registry->setEnabled('enrollment', true);

    expect($registry->enabled('admissions'))->toBeFalse()
        ->and($registry->enabled('enrollment'))->toBeTrue();
});

test('disabling enrollment disables classroom', function (): void {
    $registry = app(AcademicModuleRegistry::class);
    $registry->setEnabled('classroom', true);
    $registry->setEnabled('enrollment', false);

    expect($registry->enabled('enrollment'))->toBeFalse()
        ->and($registry->enabled('classroom'))->toBeFalse();
});
