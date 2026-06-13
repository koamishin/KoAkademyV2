<?php

declare(strict_types=1);

namespace App\Actions\Academic;

use App\Models\EducationLevel;
use App\Models\Institution;
use Illuminate\Support\Facades\DB;

final class ApplyAcademicPreset
{
    public function execute(Institution $institution, string $preset): void
    {
        $levels = match ($preset) {
            'grade_school' => [['Kindergarten', 'K', 0], ['Grade 1', 'G1', 1], ['Grade 2', 'G2', 2], ['Grade 3', 'G3', 3], ['Grade 4', 'G4', 4], ['Grade 5', 'G5', 5], ['Grade 6', 'G6', 6]],
            'high_school' => [['Grade 7', 'G7', 7], ['Grade 8', 'G8', 8], ['Grade 9', 'G9', 9], ['Grade 10', 'G10', 10], ['Grade 11', 'G11', 11], ['Grade 12', 'G12', 12]],
            'college' => [['Undergraduate', 'UG', 1], ['Graduate', 'GRAD', 2]],
            'tesda' => [['Technical-Vocational', 'TVET', 1]],
            default => [],
        };
        DB::transaction(function () use ($institution, $levels, $preset): void {
            foreach ($levels as [$name,$code,$sequence]) {
                EducationLevel::query()->updateOrCreate(['institution_id' => $institution->id, 'code' => $code], ['name' => $name, 'category' => $preset, 'sequence' => $sequence, 'status' => 'active']);
            }
        });
    }
}
