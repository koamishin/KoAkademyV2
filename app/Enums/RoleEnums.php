<?php

declare(strict_types=1);

namespace App\Enums;

enum RoleEnums: string
{
    case SUPER_ADMIN = 'super_admin';
    case SCHOOL_ADMIN = 'school_admin';
    case REGISTRAR = 'registrar';
    case ADMISSIONS_OFFICER = 'admissions_officer';
    case ACADEMIC_COORDINATOR = 'academic_coordinator';
    case TEACHER = 'teacher';
    case APPLICANT = 'applicant';
    case STUDENT = 'student';
    case GUARDIAN = 'guardian';

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::SCHOOL_ADMIN => 'School Administrator',
            self::REGISTRAR => 'Registrar',
            self::ADMISSIONS_OFFICER => 'Admissions Officer',
            self::ACADEMIC_COORDINATOR => 'Academic Coordinator',
            self::TEACHER => 'Teacher',
            self::APPLICANT => 'Applicant',
            self::STUDENT => 'Student',
            self::GUARDIAN => 'Guardian',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function administrativeValues(): array
    {
        return [
            self::SUPER_ADMIN->value,
            self::SCHOOL_ADMIN->value,
            self::REGISTRAR->value,
            self::ADMISSIONS_OFFICER->value,
            self::ACADEMIC_COORDINATOR->value,
        ];
    }
}
