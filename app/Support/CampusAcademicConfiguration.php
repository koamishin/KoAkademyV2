<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Campus;
use App\Models\EducationLevel;
use App\Models\Program;
use Illuminate\Database\Eloquent\Builder;

final readonly class CampusAcademicConfiguration
{
    /**
     * @return array<int, int>
     */
    public function educationLevelIds(Campus $campus): array
    {
        $programLevelIds = Program::query()
            ->where('campus_id', $campus->getKey())
            ->whereNotNull('education_level_id')
            ->distinct()
            ->pluck('education_level_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        if ($programLevelIds !== []) {
            return $programLevelIds;
        }

        return $this->educationLevelIdsForCampusScope($campus);
    }

    public function educationLevelsQuery(Campus $campus): Builder
    {
        $ids = $this->educationLevelIds($campus);

        return EducationLevel::query()
            ->where('institution_id', $campus->institution_id)
            ->where('status', 'active')
            ->when(
                $ids !== [],
                fn (Builder $query): Builder => $query->whereKey($ids),
                fn (Builder $query): Builder => $query->whereRaw('1 = 0'),
            )
            ->orderBy('sequence')
            ->orderBy('name');
    }

    /**
     * @return array<int, string>
     */
    public function educationLevelOptions(Campus $campus): array
    {
        return $this->educationLevelsQuery($campus)
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function schoolTypeOptions(Campus $campus): array
    {
        return $this->educationLevelsQuery($campus)
            ->get()
            ->mapWithKeys(function (EducationLevel $educationLevel): array {
                $type = $this->schoolTypeKey($educationLevel);

                return [$type => $this->schoolTypeName($type)];
            })
            ->all();
    }

    public function schoolTypeKey(EducationLevel $educationLevel): string
    {
        $code = str($educationLevel->code)->upper()->toString();
        $name = str($educationLevel->name)->lower()->toString();

        return match (true) {
            $educationLevel->category === 'grade_school' => 'elementary',
            in_array($code, ['JHS', 'G7', 'G8', 'G9', 'G10'], true)
                || str_contains($name, 'junior high')
                || str_contains($name, 'middle school')
                || str_contains($name, 'grade 7')
                || str_contains($name, 'grade 8')
                || str_contains($name, 'grade 9')
                || str_contains($name, 'grade 10') => 'junior_high',
            in_array($code, ['SHS', 'G11', 'G12'], true)
                || str_contains($name, 'senior high')
                || str_contains($name, 'grade 11')
                || str_contains($name, 'grade 12') => 'senior_high',
            $educationLevel->category === 'college' => 'college',
            default => 'other',
        };
    }

    public function schoolTypeName(string $type, ?string $fallback = null): string
    {
        return match ($type) {
            'elementary' => 'Elementary',
            'junior_high' => 'Junior High',
            'senior_high' => 'Senior High',
            'college' => 'College',
            default => $fallback ?? 'Other',
        };
    }

    /**
     * @return array<int, int>
     */
    private function educationLevelIdsForCampusScope(Campus $campus): array
    {
        $scope = str((string) data_get($campus->settings, 'academic_scope', ''))->lower()->toString();

        if (blank($scope)) {
            return [];
        }

        return EducationLevel::query()
            ->where('institution_id', $campus->institution_id)
            ->where('status', 'active')
            ->where(function (Builder $query) use ($scope): void {
                match (true) {
                    str_contains($scope, 'grade_school') || str_contains($scope, 'elementary') => $query
                        ->where('category', 'grade_school')
                        ->orWhere('code', 'ELEM')
                        ->orWhere('name', 'like', '%Elementary%')
                        ->orWhere('name', 'like', '%Grade School%'),
                    str_contains($scope, 'college') => $query
                        ->where('category', 'college')
                        ->orWhere('code', 'COL')
                        ->orWhere('code', 'UG')
                        ->orWhere('name', 'like', '%College%')
                        ->orWhere('name', 'like', '%Undergraduate%')
                        ->orWhere('name', 'like', '%Graduate%'),
                    str_contains($scope, 'high_school') || str_contains($scope, 'senior_high') || str_contains($scope, 'junior_high') => $query
                        ->where('category', 'high_school')
                        ->orWhere('code', 'JHS')
                        ->orWhere('code', 'SHS')
                        ->orWhere('name', 'like', '%Junior High%')
                        ->orWhere('name', 'like', '%Senior High%'),
                    default => $query->whereRaw('1 = 0'),
                };
            })
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }
}
