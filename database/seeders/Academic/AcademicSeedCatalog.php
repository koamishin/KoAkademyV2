<?php

declare(strict_types=1);

namespace Database\Seeders\Academic;

final class AcademicSeedCatalog
{
    /**
     * @return array<string, mixed>
     */
    public function institution(): array
    {
        return [
            'code' => 'KOA',
            'name' => 'Ko Academy',
            'timezone' => 'Asia/Manila',
            'locale' => 'en_PH',
            'status' => 'active',
            'settings' => [
                'country' => 'PH',
                'regulatory_reporting' => ['CHED', 'DepEd'],
                'academic_model' => 'multi_level_single_institution',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function campuses(): array
    {
        return [
            [
                'code' => 'MAIN',
                'name' => 'Elementary Campus',
                'address' => 'Foundations Avenue, Quezon City, Metro Manila',
                'timezone' => 'Asia/Manila',
                'status' => 'active',
                'settings' => ['is_primary' => true, 'academic_scope' => 'grade_school'],
            ],
            [
                'code' => 'SOUTH',
                'name' => 'High School and Senior High School Campus',
                'address' => 'Scholars Road, Calamba City, Laguna',
                'timezone' => 'Asia/Manila',
                'status' => 'active',
                'settings' => ['is_primary' => false, 'academic_scope' => 'high_school_senior_high_school'],
            ],
            [
                'code' => 'COLLEGE',
                'name' => 'College Campus',
                'address' => 'Innovation Drive, Taguig City, Metro Manila',
                'timezone' => 'Asia/Manila',
                'status' => 'active',
                'settings' => ['is_primary' => false, 'academic_scope' => 'college'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function academicYear(): array
    {
        return [
            'name' => 'SY 2026-2027',
            'starts_on' => '2026-06-01',
            'ends_on' => '2027-03-31',
            'status' => 'active',
            'is_current' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function applicationDetails(): array
    {
        return [
            'site_name' => 'Ko Academy',
            'site_description' => 'A unified academic management platform for basic education, senior high school, and college operations.',
            'site_logo_url' => null,
            'site_favicon_url' => null,
            'site_logo_path' => null,
            'site_favicon_path' => null,
            'timezone' => 'Asia/Manila',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'contact_email' => 'registrar@koacademy.example',
            'support_url' => 'https://koacademy.example/support',
            'support_phone' => '+63 2 555 0100',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function applicationFeatures(): array
    {
        return [
            'registration_enabled' => true,
            'email_verification_required' => true,
            'two_factor_authentication_enabled' => true,
            'password_reset_enabled' => true,
            'user_impersonation_enabled' => true,
            'default_user_role' => 'applicant',
            'activity_log_enabled' => true,
            'notifications_enabled' => true,
            'auth_layout' => 'simple',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function applicationSecurity(): array
    {
        return [
            'password_min_length' => 8,
            'password_requires_uppercase' => true,
            'password_requires_lowercase' => true,
            'password_requires_numbers' => true,
            'password_requires_symbols' => false,
            'session_lifetime' => 120,
            'single_session' => false,
            'login_rate_limit' => 5,
            'login_rate_limit_decay' => 60,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function operations(): array
    {
        return [
            'schedule_increment' => 15,
            'working_days' => [1, 2, 3, 4, 5],
            'student_number_format' => 'STU-{year}-{sequence:5}',
            'application_number_format' => 'APP-{year}-{sequence:5}',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function terms(): array
    {
        return [
            [
                'code' => 'BE-ANNUAL',
                'name' => 'Basic Education Annual Term',
                'sequence' => 1,
                'starts_on' => '2026-06-01',
                'ends_on' => '2027-03-31',
                'status' => 'active',
            ],
            [
                'code' => 'SEM1',
                'name' => 'First Semester',
                'sequence' => 1,
                'starts_on' => '2026-08-01',
                'ends_on' => '2026-12-15',
                'status' => 'active',
            ],
            [
                'code' => 'SEM2',
                'name' => 'Second Semester',
                'sequence' => 2,
                'starts_on' => '2027-01-10',
                'ends_on' => '2027-05-30',
                'status' => 'active',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function educationLevels(): array
    {
        return [
            [
                'code' => 'ELEM',
                'name' => 'Grade School / Elementary',
                'category' => 'grade_school',
                'sequence' => 1,
                'features' => ['span' => 'Kindergarten to Grade 6', 'reporting' => 'DepEd LIS'],
            ],
            [
                'code' => 'JHS',
                'name' => 'Junior High School / Middle School',
                'category' => 'high_school',
                'sequence' => 2,
                'features' => ['span' => 'Grades 7 to 10', 'stage' => 'junior_high_school'],
            ],
            [
                'code' => 'SHS',
                'name' => 'Senior High School',
                'category' => 'high_school',
                'sequence' => 3,
                'features' => ['span' => 'Grades 11 to 12', 'stage' => 'senior_high_school'],
            ],
            [
                'code' => 'COL',
                'name' => 'College',
                'category' => 'college',
                'sequence' => 4,
                'features' => ['span' => 'Undergraduate degree programs', 'reporting' => 'CHED'],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function rooms(): array
    {
        return [
            ['code' => 'ELEM-101', 'name' => 'Elementary Homeroom 101', 'capacity' => 30, 'room_type' => 'classroom'],
            ['code' => 'JHS-LAB', 'name' => 'Junior High Science Laboratory', 'capacity' => 32, 'room_type' => 'laboratory'],
            ['code' => 'SHS-ICT', 'name' => 'Senior High ICT Laboratory', 'capacity' => 35, 'room_type' => 'laboratory'],
            ['code' => 'COL-LAB1', 'name' => 'College Computer Laboratory 1', 'capacity' => 40, 'room_type' => 'laboratory'],
            ['code' => 'AUD-1', 'name' => 'Academic Lecture Hall', 'capacity' => 120, 'room_type' => 'lecture'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function programs(): array
    {
        return [
            [
                'campus_code' => 'MAIN',
                'level_code' => 'ELEM',
                'code' => 'ELEM',
                'name' => 'Elementary Education',
                'award_type' => 'basic_education',
                'term_code' => 'BE-ANNUAL',
                'sections' => [
                    ['code' => 'ELEM-G1A', 'name' => 'Grade 1 - A', 'year_level' => 1, 'capacity' => 30],
                    ['code' => 'ELEM-G4A', 'name' => 'Grade 4 - A', 'year_level' => 4, 'capacity' => 32],
                ],
                'curricula' => [
                    $this->elementaryCoreCurriculum(),
                    $this->elementarySteamCurriculum(),
                ],
            ],
            [
                'campus_code' => 'SOUTH',
                'level_code' => 'JHS',
                'code' => 'JHS',
                'name' => 'Junior High School',
                'award_type' => 'basic_education',
                'term_code' => 'BE-ANNUAL',
                'sections' => [
                    ['code' => 'JHS-G7A', 'name' => 'Grade 7 - A', 'year_level' => 7, 'capacity' => 35],
                    ['code' => 'JHS-G9A', 'name' => 'Grade 9 - A', 'year_level' => 9, 'capacity' => 35],
                ],
                'curricula' => [
                    $this->juniorHighMatatagCurriculum(),
                    $this->juniorHighScienceCurriculum(),
                ],
            ],
            [
                'campus_code' => 'SOUTH',
                'level_code' => 'SHS',
                'code' => 'SHS',
                'name' => 'Senior High School',
                'award_type' => 'senior_high_school',
                'term_code' => 'BE-ANNUAL',
                'sections' => [
                    ['code' => 'SHS-11STEM', 'name' => 'Grade 11 STEM', 'year_level' => 11, 'capacity' => 35],
                    ['code' => 'SHS-11ABM', 'name' => 'Grade 11 ABM', 'year_level' => 11, 'capacity' => 35],
                ],
                'curricula' => [
                    $this->seniorHighStemCurriculum(),
                    $this->seniorHighAbmCurriculum(),
                ],
            ],
            [
                'campus_code' => 'COLLEGE',
                'level_code' => 'COL',
                'code' => 'BSIT',
                'name' => 'Bachelor of Science in Information Technology',
                'award_type' => 'bachelor',
                'term_code' => 'SEM1',
                'sections' => [
                    ['code' => 'BSIT-1A', 'name' => 'BSIT 1A', 'year_level' => 1, 'capacity' => 40],
                ],
                'curricula' => [
                    $this->collegeBsitCurriculum(),
                ],
            ],
            [
                'campus_code' => 'COLLEGE',
                'level_code' => 'COL',
                'code' => 'BSBA',
                'name' => 'Bachelor of Science in Business Administration',
                'award_type' => 'bachelor',
                'term_code' => 'SEM1',
                'sections' => [
                    ['code' => 'BSBA-1A', 'name' => 'BSBA 1A', 'year_level' => 1, 'capacity' => 40],
                ],
                'curricula' => [
                    $this->collegeBsbaCurriculum(),
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function elementaryCoreCurriculum(): array
    {
        return $this->curriculum('ELEM-CORE-2026', 'Elementary Core Curriculum SY 2026-2027', 'Department of Education', [
            $this->subject('ELEM-ENG', 'Language and Literacy', 1, 1, 4),
            $this->subject('ELEM-MATH', 'Elementary Mathematics', 1, 1, 4),
            $this->subject('ELEM-SCI', 'Science and Health', 4, 1, 4),
            $this->subject('ELEM-MKB', 'Makabansa', 1, 1, 3),
            $this->subject('ELEM-GMRC', 'Good Manners and Right Conduct', 1, 1, 2),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function elementarySteamCurriculum(): array
    {
        return $this->curriculum('ELEM-STEAM-2026', 'Elementary STEAM Enrichment Curriculum', 'Institutional', [
            $this->subject('ELEM-STEAM-LIT', 'Integrated Literacy Studio', 1, 1, 4),
            $this->subject('ELEM-STEAM-MATH', 'Creative Mathematics', 1, 1, 4),
            $this->subject('ELEM-STEAM-SCI', 'Discovery Science', 3, 1, 4),
            $this->subject('ELEM-STEAM-ART', 'Arts, Music, and Movement', 1, 1, 3),
            $this->subject('ELEM-STEAM-DESIGN', 'Design Thinking for Kids', 4, 1, 3),
        ], customized: true);
    }

    /**
     * @return array<string, mixed>
     */
    private function juniorHighMatatagCurriculum(): array
    {
        return $this->curriculum('JHS-MATATAG-2026', 'Junior High MATATAG Curriculum', 'Department of Education', [
            $this->subject('JHS-FIL', 'Filipino', 7, 1, 4),
            $this->subject('JHS-ENG', 'English', 7, 1, 4),
            $this->subject('JHS-MATH', 'Mathematics', 7, 1, 4),
            $this->subject('JHS-SCI', 'Science', 7, 1, 4, subjectType: 'laboratory', labHours: 1),
            $this->subject('JHS-AP', 'Araling Panlipunan', 7, 1, 3),
            $this->subject('JHS-TLE', 'Technology and Livelihood Education', 7, 1, 3),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function juniorHighScienceCurriculum(): array
    {
        return $this->curriculum('JHS-SCI-TECH-2026', 'Junior High Science and Technology Curriculum', 'Institutional', [
            $this->subject('JHS-ST-MATH', 'Advanced Mathematics', 7, 1, 5),
            $this->subject('JHS-ST-BIO', 'Life Science Laboratory', 7, 1, 4, subjectType: 'laboratory', labHours: 2),
            $this->subject('JHS-ST-ENG', 'Research Communication', 7, 1, 4),
            $this->subject('JHS-ST-CS', 'Computational Thinking', 8, 1, 3, subjectType: 'laboratory', labHours: 2),
            $this->subject('JHS-ST-ROBO', 'Robotics Foundations', 9, 1, 3, subjectType: 'laboratory', labHours: 2),
        ], customized: true);
    }

    /**
     * @return array<string, mixed>
     */
    private function seniorHighStemCurriculum(): array
    {
        return $this->curriculum('SHS-STEM-2026', 'Senior High STEM Curriculum', 'Department of Education', [
            $this->subject('SHS-STEM-COMM', 'Effective Communication', 11, 1, 0, contactHours: 4),
            $this->subject('SHS-STEM-GENMATH', 'General Mathematics', 11, 1, 0, contactHours: 4),
            $this->subject('SHS-STEM-EARTH', 'Earth and Life Science', 11, 1, 0, contactHours: 4, subjectType: 'laboratory', labHours: 1),
            $this->subject('SHS-STEM-CALC', 'Pre-Calculus', 11, 1, 0, contactHours: 4),
            $this->subject('SHS-STEM-RES', 'Practical Research 1', 11, 1, 0, contactHours: 4),
            $this->subject('SHS-STEM-ELEC1', 'STEM Elective: Programming', 12, 1, 0, contactHours: 4, subjectType: 'laboratory', labHours: 2, required: false, electiveGroup: 'STEM-ELECTIVES'),
        ], electiveGroups: [
            ['code' => 'STEM-ELECTIVES', 'name' => 'STEM Electives', 'minimum_subjects' => 1, 'maximum_subjects' => 2],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function seniorHighAbmCurriculum(): array
    {
        return $this->curriculum('SHS-ABM-2026', 'Senior High ABM Curriculum', 'Department of Education', [
            $this->subject('SHS-ABM-COMM', 'Effective Communication', 11, 1, 0, contactHours: 4),
            $this->subject('SHS-ABM-GENMATH', 'General Mathematics', 11, 1, 0, contactHours: 4),
            $this->subject('SHS-ABM-ECON', 'Applied Economics', 11, 1, 0, contactHours: 4),
            $this->subject('SHS-ABM-BFIN', 'Business Finance', 11, 1, 0, contactHours: 4),
            $this->subject('SHS-ABM-ENTREP', 'Entrepreneurship', 12, 1, 0, contactHours: 4),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function collegeBsitCurriculum(): array
    {
        return $this->curriculum('BSIT-2026', 'BSIT Curriculum 2026', 'Commission on Higher Education', [
            $this->subject('IT101', 'Introduction to Computing', 1, 1, 3, contactHours: 2, subjectType: 'laboratory', labHours: 3),
            $this->subject('IT102', 'Computer Programming 1', 1, 1, 3, contactHours: 2, subjectType: 'laboratory', labHours: 3),
            $this->subject('GE-MATH', 'Mathematics in the Modern World', 1, 1, 3, contactHours: 3),
            $this->subject('IT103', 'Computer Programming 2', 1, 2, 3, contactHours: 2, subjectType: 'laboratory', labHours: 3, prerequisites: ['IT102']),
            $this->subject('IT-ELEC1', 'Professional Elective 1', 3, 1, 3, contactHours: 2, subjectType: 'laboratory', labHours: 3, required: false, electiveGroup: 'PROF-ELECTIVES'),
        ], electiveGroups: [
            ['code' => 'PROF-ELECTIVES', 'name' => 'Professional Electives', 'minimum_subjects' => 1, 'maximum_subjects' => 3, 'minimum_units' => 3, 'maximum_units' => 9],
        ], tuitionPerUnit: 375, laboratoryFee: 2000, miscellaneousFees: $this->collegeFees());
    }

    /**
     * @return array<string, mixed>
     */
    private function collegeBsbaCurriculum(): array
    {
        return $this->curriculum('BSBA-2026', 'BSBA Curriculum 2026', 'Commission on Higher Education', [
            $this->subject('BA101', 'Principles of Management', 1, 1, 3, contactHours: 3),
            $this->subject('BA102', 'Financial Accounting and Reporting', 1, 1, 3, contactHours: 3),
            $this->subject('BA103', 'Business Mathematics', 1, 1, 3, contactHours: 3),
            $this->subject('GE-STS', 'Science, Technology and Society', 1, 1, 3, contactHours: 3),
            $this->subject('BA-ELEC1', 'Business Analytics Elective', 3, 1, 3, contactHours: 2, subjectType: 'laboratory', labHours: 2, required: false, electiveGroup: 'BUS-ELECTIVES'),
        ], electiveGroups: [
            ['code' => 'BUS-ELECTIVES', 'name' => 'Business Electives', 'minimum_subjects' => 1, 'maximum_subjects' => 2, 'minimum_units' => 3, 'maximum_units' => 6],
        ], tuitionPerUnit: 360, laboratoryFee: 1500, miscellaneousFees: $this->collegeFees());
    }

    /**
     * @param  array<int, array<string, mixed>>  $subjects
     * @param  array<int, array<string, mixed>>  $electiveGroups
     * @param  array<int, array<string, mixed>>  $miscellaneousFees
     * @return array<string, mixed>
     */
    private function curriculum(
        string $code,
        string $name,
        string $authority,
        array $subjects,
        bool $customized = false,
        array $electiveGroups = [],
        float $tuitionPerUnit = 0,
        float $laboratoryFee = 0,
        array $miscellaneousFees = [],
    ): array {
        return [
            'code' => $code,
            'name' => $name,
            'effective_year' => 2026,
            'template_version' => 'SY 2026-2027',
            'template_authority' => $authority,
            'template_source_url' => null,
            'is_customized' => $customized,
            'currency' => 'PHP',
            'tuition_per_unit' => $tuitionPerUnit,
            'laboratory_fee_per_subject' => $laboratoryFee,
            'status' => 'active',
            'subjects' => $subjects,
            'elective_groups' => $electiveGroups,
            'miscellaneous_fees' => $miscellaneousFees,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function subject(
        string $code,
        string $name,
        int $yearLevel,
        int $termSequence,
        float $units,
        float $contactHours = 0,
        string $subjectType = 'academic',
        float $labHours = 0,
        float $competencyHours = 0,
        bool $required = true,
        ?string $electiveGroup = null,
        array $prerequisites = [],
    ): array {
        return [
            'code' => $code,
            'name' => $name,
            'subject_type' => $subjectType,
            'year_level' => $yearLevel,
            'term_sequence' => $termSequence,
            'credit_units' => $units,
            'contact_hours' => $contactHours,
            'lab_hours' => $labHours,
            'competency_hours' => $competencyHours,
            'is_required' => $required,
            'elective_group' => $electiveGroup,
            'prerequisites' => $prerequisites,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function collegeFees(): array
    {
        return [
            ['code' => 'REG', 'name' => 'Registration Fee', 'description' => 'Standard enrollment processing fee.', 'amount' => 500, 'is_active' => true],
            ['code' => 'LIB', 'name' => 'Library Fee', 'description' => 'Campus library and digital resource access.', 'amount' => 300, 'is_active' => true],
        ];
    }
}
