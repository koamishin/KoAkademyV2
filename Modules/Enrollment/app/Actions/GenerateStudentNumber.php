<?php

declare(strict_types=1);

namespace Modules\Enrollment\Actions;

use App\Models\AcademicSequence;
use App\Models\AcademicSetting;
use Illuminate\Support\Facades\DB;
use Modules\Enrollment\Models\Enrollment;

final class GenerateStudentNumber
{
    public function execute(Enrollment $enrollment): string
    {
        return DB::transaction(function () use ($enrollment): string {
            $campusId = $enrollment->period->campus_id;
            $year = now()->format('Y');
            $sequence = AcademicSequence::query()->where('campus_id', $campusId)->where('key', "student-number-{$year}")->lockForUpdate()->first();
            if (! $sequence) {
                $sequence = AcademicSequence::query()->create(['campus_id' => $campusId, 'key' => "student-number-{$year}", 'next_value' => 1]);
            }
            $value = $sequence->next_value;
            $sequence->increment('next_value');
            $setting = AcademicSetting::query()->whereNull('campus_id')->where('key', 'student_number_format')->first();
            $settingValue = $setting?->value;
            $format = $settingValue[0] ?? '{year}-{sequence:6}';
            $number = str_replace('{year}', $year, $format);

            return preg_replace_callback('/\{sequence:(\d+)\}/', fn (array $matches): string => str_pad((string) $value, (int) $matches[1], '0', STR_PAD_LEFT), $number) ?? $number;
        });
    }
}
