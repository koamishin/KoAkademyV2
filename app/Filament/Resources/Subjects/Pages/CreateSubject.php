<?php

declare(strict_types=1);

namespace App\Filament\Resources\Subjects\Pages;

use App\Academic\CurriculumTemplateRegistry;
use App\Actions\Academic\CreateCurriculumFromBuilder;
use App\Filament\Resources\Curricula\CurriculumResource;
use App\Filament\Resources\Subjects\SubjectResource;
use App\Models\Campus;
use App\Models\Curriculum;
use App\Models\EducationLevel;
use App\Models\Program;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\Concerns\HasWizard;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

final class CreateSubject extends CreateRecord
{
    use HasWizard;

    protected static string $resource = SubjectResource::class;

    protected static ?string $title = 'Build a curriculum';

    private ?Curriculum $createdCurriculum = null;

    public function getSubheading(): string
    {
        return 'Start from an official Philippine template or shape a flexible curriculum for your school.';
    }

    public function getSteps(): array
    {
        return [
            Step::make('School context')
                ->icon(Heroicon::BuildingLibrary)
                ->description('Choose the level and program')
                ->schema([
                    Section::make('Where will this curriculum be used?')
                        ->description('The institution and campus are locked to your current workspace.')
                        ->icon(Heroicon::MapPin)
                        ->schema([
                            Placeholder::make('campus')
                                ->label('Current campus')
                                ->content(fn (): string => $this->campus()->name),
                            Placeholder::make('institution')
                                ->label('Institution')
                                ->content(fn (): string => $this->campus()->institution()->value('name') ?? 'Institution'),
                            Select::make('education_level_id')
                                ->label('Education level')
                                ->options(fn (): array => EducationLevel::query()
                                    ->where('institution_id', $this->campus()->institution_id)
                                    ->where('status', 'active')
                                    ->orderBy('sequence')
                                    ->pluck('name', 'id')
                                    ->all())
                                ->searchable()
                                ->preload()
                                ->live()
                                ->required()
                                ->afterStateUpdated(function (Set $set): void {
                                    $set('program_id', null);
                                    $set('template_key', null);
                                    $set('subjects', []);
                                    $set('elective_groups', []);
                                }),
                            Toggle::make('create_program')
                                ->label('Create a new program')
                                ->helperText('Use this for a new degree, strand, or grade-level program.')
                                ->live()
                                ->default(false)
                                ->afterStateUpdated(fn (Set $set): mixed => $set('program_id', null)),
                            Select::make('program_id')
                                ->label('Existing program')
                                ->options(fn (Get $get): array => Program::query()
                                    ->where('campus_id', $this->campus()->id)
                                    ->when(
                                        filled($get('education_level_id')),
                                        fn ($query) => $query->where('education_level_id', $get('education_level_id')),
                                    )
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all())
                                ->searchable()
                                ->preload()
                                ->live()
                                ->required(fn (Get $get): bool => ! (bool) $get('create_program'))
                                ->visible(fn (Get $get): bool => ! (bool) $get('create_program')),
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('program_name')
                                        ->label('Program name')
                                        ->placeholder('e.g. Bachelor of Science in Information Technology')
                                        ->required(fn (Get $get): bool => (bool) $get('create_program'))
                                        ->live(onBlur: true),
                                    TextInput::make('program_code')
                                        ->label('Program code')
                                        ->placeholder('e.g. BSIT or GRADE-4')
                                        ->required(fn (Get $get): bool => (bool) $get('create_program'))
                                        ->maxLength(50)
                                        ->live(onBlur: true)
                                        ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Str::upper($state) : null),
                                    TextInput::make('award_type')
                                        ->label('Award or credential')
                                        ->placeholder('Optional')
                                        ->columnSpanFull(),
                                ])
                                ->visible(fn (Get $get): bool => (bool) $get('create_program'))
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                ]),
            Step::make('Official template')
                ->icon(Heroicon::DocumentCheck)
                ->description('Choose a versioned foundation')
                ->schema([
                    Section::make('Curriculum foundation')
                        ->description('Official definitions are read-only. Your generated copy remains fully editable.')
                        ->icon(Heroicon::ShieldCheck)
                        ->schema([
                            Select::make('template_key')
                                ->label('Template')
                                ->options(fn (Get $get): array => $this->templateOptions($get))
                                ->required()
                                ->live()
                                ->afterStateUpdated(fn (Set $set, ?string $state) => $this->loadTemplate($set, $state)),
                            Placeholder::make('template_details')
                                ->label('Source and effectivity')
                                ->content(fn (Get $get): HtmlString => $this->templateDetails($get('template_key')))
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                ]),
            Step::make('Curriculum details')
                ->icon(Heroicon::Identification)
                ->description('Identity, pricing, and fees')
                ->schema([
                    Section::make('Identity and publication')
                        ->icon(Heroicon::CalendarDays)
                        ->schema([
                            TextInput::make('name')
                                ->label('Curriculum name')
                                ->placeholder('e.g. BSIT Curriculum 2026')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('code')
                                ->label('Curriculum code')
                                ->placeholder('e.g. BSIT-2026')
                                ->required()
                                ->maxLength(50)
                                ->dehydrateStateUsing(fn (string $state): string => Str::upper($state)),
                            TextInput::make('effective_year')
                                ->label('Effective year')
                                ->numeric()
                                ->minValue(1900)
                                ->maxValue(2200)
                                ->default((int) now()->format('Y'))
                                ->required(),
                            Select::make('status')
                                ->options([
                                    'draft' => 'Draft - review before enrollment',
                                    'active' => 'Active - available for enrollment',
                                    'inactive' => 'Inactive',
                                    'archived' => 'Archived',
                                ])
                                ->default('draft')
                                ->required(),
                        ])
                        ->columns(2),
                    Section::make('Fees and assessment')
                        ->description('New curricula start at PHP 375 per unit and PHP 2,000 once per laboratory subject.')
                        ->icon(Heroicon::Banknotes)
                        ->schema([
                            TextInput::make('currency')
                                ->default('PHP')
                                ->readOnly()
                                ->saved()
                                ->required(),
                            TextInput::make('tuition_per_unit')
                                ->label('Tuition per credit unit')
                                ->prefix('PHP')
                                ->numeric()
                                ->minValue(0)
                                ->default(375)
                                ->live(onBlur: true)
                                ->required(),
                            TextInput::make('laboratory_fee_per_subject')
                                ->label('Flat laboratory fee per subject')
                                ->helperText('Charged once when a curriculum item has any laboratory hours.')
                                ->prefix('PHP')
                                ->numeric()
                                ->minValue(0)
                                ->default(2000)
                                ->live(onBlur: true)
                                ->required(),
                            Placeholder::make('pricing_example')
                                ->label('Estimated Year 1 / Term 1 assessment')
                                ->content(fn (Get $get): string => $this->formatMoney($this->estimatedAssessment($get)))
                                ->columnSpanFull(),
                            Repeater::make('miscellaneous_fees')
                                ->label('Miscellaneous fees')
                                ->schema([
                                    TextInput::make('code')
                                        ->required()
                                        ->maxLength(50),
                                    TextInput::make('name')
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('amount')
                                        ->prefix('PHP')
                                        ->numeric()
                                        ->minValue(0)
                                        ->live(onBlur: true)
                                        ->required(),
                                    Toggle::make('is_active')
                                        ->label('Active')
                                        ->default(true),
                                    Textarea::make('description')
                                        ->rows(2)
                                        ->columnSpanFull(),
                                ])
                                ->columns(4)
                                ->reorderable()
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                                ->addActionLabel('Add miscellaneous fee')
                                ->columnSpanFull(),
                        ])
                        ->columns(3),
                ]),
            Step::make('Subjects and choices')
                ->icon(Heroicon::BookOpen)
                ->description('Review years, terms, and electives')
                ->schema([
                    Section::make('Elective rules')
                        ->description('Define how many choices students must make from each group.')
                        ->icon(Heroicon::AdjustmentsHorizontal)
                        ->schema([
                            Repeater::make('elective_groups')
                                ->hiddenLabel()
                                ->schema([
                                    TextInput::make('code')->required()->maxLength(50),
                                    TextInput::make('name')->required()->maxLength(255),
                                    TextInput::make('minimum_subjects')->label('Min subjects')->numeric()->minValue(0)->default(0),
                                    TextInput::make('maximum_subjects')->label('Max subjects')->numeric()->minValue(1),
                                    TextInput::make('minimum_units')->label('Min units')->numeric()->minValue(0)->default(0),
                                    TextInput::make('maximum_units')->label('Max units')->numeric()->minValue(0),
                                ])
                                ->columns(3)
                                ->addActionLabel('Add elective group')
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                                ->columnSpanFull(),
                        ])
                        ->collapsible()
                        ->collapsed(fn (Get $get): bool => blank($get('elective_groups'))),
                    Section::make('Curriculum subjects')
                        ->description('Drag to reorder. Existing institution subjects with matching codes will be reused.')
                        ->icon(Heroicon::RectangleStack)
                        ->schema([
                            Repeater::make('subjects')
                                ->hiddenLabel()
                                ->schema([
                                    Grid::make(12)->schema([
                                        TextInput::make('code')
                                            ->required()
                                            ->maxLength(50)
                                            ->columnSpan(['default' => 12, 'md' => 3]),
                                        TextInput::make('name')
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpan(['default' => 12, 'md' => 6]),
                                        Select::make('subject_type')
                                            ->options([
                                                'academic' => 'Academic',
                                                'laboratory' => 'Laboratory',
                                                'competency' => 'Competency',
                                            ])
                                            ->default('academic')
                                            ->required()
                                            ->columnSpan(['default' => 12, 'md' => 3]),
                                        TextInput::make('year_level')->label('Year / grade')->numeric()->minValue(0)->columnSpan(2),
                                        TextInput::make('term_sequence')->label('Term')->numeric()->minValue(1)->columnSpan(2),
                                        TextInput::make('credit_units')->label('Units')->numeric()->minValue(0)->default(0)->required()->columnSpan(2),
                                        TextInput::make('contact_hours')->label('Lecture hours')->numeric()->minValue(0)->default(0)->required()->columnSpan(2),
                                        TextInput::make('lab_hours')->label('Lab hours')->numeric()->minValue(0)->default(0)->required()->columnSpan(2),
                                        TextInput::make('competency_hours')->label('Competency hours')->numeric()->minValue(0)->default(0)->required()->columnSpan(2),
                                        Toggle::make('is_required')->label('Required')->default(true)->inline(false)->columnSpan(2),
                                        TextInput::make('elective_group')->label('Elective group code')->columnSpan(4),
                                        Select::make('prerequisites')
                                            ->label('Prerequisite subject codes')
                                            ->multiple()
                                            ->options(fn (Get $get): array => collect($get('../../subjects') ?? [])
                                                ->filter(fn (array $subject): bool => filled($subject['code'] ?? null))
                                                ->mapWithKeys(fn (array $subject): array => [
                                                    Str::upper($subject['code']) => "{$subject['code']} - {$subject['name']}",
                                                ])
                                                ->all())
                                            ->searchable()
                                            ->columnSpan(6),
                                        Textarea::make('description')->rows(2)->columnSpanFull(),
                                        Hidden::make('position'),
                                    ]),
                                ])
                                ->reorderable()
                                ->cloneable()
                                ->collapsible()
                                ->itemLabel(fn (array $state): string => filled($state['code'] ?? null)
                                    ? "{$state['code']} - {$state['name']}"
                                    : 'New subject')
                                ->addActionLabel('Add subject')
                                ->minItems(1)
                                ->required()
                                ->columnSpanFull(),
                        ]),
                ]),
            Step::make('Review and create')
                ->icon(Heroicon::Sparkles)
                ->description('Validate the complete structure')
                ->schema([
                    Section::make('Ready to build')
                        ->description('The curriculum, reusable subject catalog, prerequisites, and elective rules are created in one transaction.')
                        ->icon(Heroicon::CheckBadge)
                        ->schema([
                            Placeholder::make('review_program')
                                ->label('Program')
                                ->content(fn (Get $get): string => $this->programLabel($get)),
                            Placeholder::make('review_template')
                                ->label('Foundation')
                                ->content(fn (Get $get): string => $this->templateName($get('template_key'))),
                            Placeholder::make('review_subjects')
                                ->label('Subjects')
                                ->content(fn (Get $get): string => (string) count($get('subjects') ?? [])),
                            Placeholder::make('review_units')
                                ->label('Total credit units')
                                ->content(fn (Get $get): string => number_format(
                                    collect($get('subjects') ?? [])->sum('credit_units'),
                                    2,
                                )),
                            Placeholder::make('review_hours')
                                ->label('Total scheduled hours')
                                ->content(fn (Get $get): string => number_format(
                                    collect($get('subjects') ?? [])->sum(
                                        fn (array $subject): float => (float) ($subject['contact_hours'] ?? 0)
                                            + (float) ($subject['lab_hours'] ?? 0)
                                            + (float) ($subject['competency_hours'] ?? 0),
                                    ),
                                    2,
                                )),
                            Placeholder::make('review_electives')
                                ->label('Elective groups')
                                ->content(fn (Get $get): string => (string) count($get('elective_groups') ?? [])),
                            Placeholder::make('review_assessment')
                                ->label('Estimated Year 1 / Term 1')
                                ->content(fn (Get $get): string => $this->formatMoney($this->estimatedAssessment($get))),
                        ])
                        ->columns(3),
                ]),
        ];
    }

    protected function handleRecordCreation(array $data): Model
    {
        $this->createdCurriculum = app(CreateCurriculumFromBuilder::class)->execute($this->campus(), $data);

        return $this->createdCurriculum->items->first()->subject;
    }

    protected function getRedirectUrl(): string
    {
        return CurriculumResource::getUrl('edit', [
            'record' => $this->createdCurriculum,
            'tenant' => $this->campus(),
        ]);
    }

    protected function getCreatedNotificationTitle(): string
    {
        return 'Curriculum created successfully';
    }

    private function campus(): Campus
    {
        $campus = Filament::getTenant();

        abort_unless($campus instanceof Campus, 404);

        return $campus;
    }

    private function templateOptions(Get $get): array
    {
        $level = EducationLevel::query()
            ->whereKey($get('education_level_id'))
            ->where('institution_id', $this->campus()->institution_id)
            ->first();

        if ($level === null) {
            return ['blank' => 'Start with a flexible blank curriculum'];
        }

        $program = filled($get('program_id'))
            ? Program::query()->where('campus_id', $this->campus()->id)->find($get('program_id'))
            : new Program([
                'name' => $get('program_name'),
                'code' => $get('program_code'),
            ]);

        return app(CurriculumTemplateRegistry::class)->optionsFor($level, $program);
    }

    private function loadTemplate(Set $set, ?string $key): void
    {
        $template = $key === 'blank'
            ? app(CurriculumTemplateRegistry::class)->blank()
            : app(CurriculumTemplateRegistry::class)->find((string) $key);

        $set('subjects', $template['subjects'] ?? []);
        $set('elective_groups', $template['elective_groups'] ?? []);
    }

    private function templateDetails(?string $key): HtmlString
    {
        if (blank($key) || $key === 'blank') {
            return new HtmlString('<span class="text-sm text-gray-500">Flexible institutional structure. It will not be labeled as an official government template.</span>');
        }

        $template = app(CurriculumTemplateRegistry::class)->find($key);

        if ($template === null) {
            return new HtmlString('<span class="text-sm text-gray-500">Choose a template to see its source.</span>');
        }

        $url = e($template['source_url']);
        $authority = e($template['authority']);
        $version = e($template['version']);
        $order = e($template['order']);
        $verifiedThrough = e($template['verified_through'] ?? '');
        $issuanceIndexUrl = e($template['issuance_index_url'] ?? '');
        $verification = filled($verifiedThrough)
            ? "<div class=\"mt-1 text-amber-800 dark:text-amber-300\">Verified through {$verifiedThrough}</div>"
            : '';
        $issuanceLink = filled($issuanceIndexUrl)
            ? "<a class=\"mt-2 ml-3 inline-flex font-medium text-amber-700 underline dark:text-amber-300\" href=\"{$issuanceIndexUrl}\" target=\"_blank\" rel=\"noopener\">Open current CHED issuances</a>"
            : '';

        return new HtmlString(
            '<div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm dark:border-amber-500/30 dark:bg-amber-500/10">'
            ."<div class=\"font-semibold text-amber-900 dark:text-amber-200\">{$authority}</div>"
            ."<div class=\"mt-1 text-amber-800 dark:text-amber-300\">{$order} - {$version}</div>"
            .$verification
            ."<a class=\"mt-2 inline-flex font-medium text-amber-700 underline dark:text-amber-300\" href=\"{$url}\" target=\"_blank\" rel=\"noopener\">Open official source</a>"
            .$issuanceLink
            .'</div>',
        );
    }

    private function programLabel(Get $get): string
    {
        if ((bool) $get('create_program')) {
            return "{$get('program_name')} ({$get('program_code')})";
        }

        return Program::query()->where('campus_id', $this->campus()->id)->find($get('program_id'))?->name
            ?? 'Existing program';
    }

    private function templateName(?string $key): string
    {
        if ($key === 'blank') {
            return 'Flexible blank curriculum';
        }

        return app(CurriculumTemplateRegistry::class)->find((string) $key)['name'] ?? 'Not selected';
    }

    private function estimatedAssessment(Get $get): float
    {
        $subjects = collect($get('subjects') ?? [])
            ->filter(fn (array $subject): bool => in_array($subject['year_level'] ?? null, [null, 1], true)
                && in_array($subject['term_sequence'] ?? null, [null, 1], true)
                && (bool) ($subject['is_required'] ?? false));
        $tuition = $subjects->sum('credit_units') * (float) ($get('tuition_per_unit') ?? 0);
        $laboratory = $subjects
            ->filter(fn (array $subject): bool => (float) ($subject['lab_hours'] ?? 0) > 0)
            ->count() * (float) ($get('laboratory_fee_per_subject') ?? 0);
        $miscellaneous = collect($get('miscellaneous_fees') ?? [])
            ->filter(fn (array $fee): bool => (bool) ($fee['is_active'] ?? false))
            ->sum('amount');

        return $tuition + $laboratory + $miscellaneous;
    }

    private function formatMoney(float $amount): string
    {
        return 'PHP '.number_format($amount, 2);
    }
}
