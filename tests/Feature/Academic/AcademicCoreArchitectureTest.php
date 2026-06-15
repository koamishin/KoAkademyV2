<?php

use Illuminate\Support\Facades\File;

test('academic core belongs to the main application', function (): void {
    expect(File::isDirectory(base_path('Modules/AcademicCore')))->toBeFalse();

    $sourceDirectories = [
        app_path(),
        base_path('Modules'),
        base_path('tests'),
        resource_path('js'),
    ];

    $references = collect($sourceDirectories)
        ->flatMap(fn (string $directory) => File::allFiles($directory))
        ->filter(fn (SplFileInfo $file): bool => in_array($file->getExtension(), ['php', 'ts', 'vue'], true))
        ->filter(fn (SplFileInfo $file): bool => str_contains($file->getContents(), 'Modules\\AcademicCore'));

    expect($references)->toBeEmpty();
});
