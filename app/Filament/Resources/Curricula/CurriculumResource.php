<?php

declare(strict_types=1);

namespace App\Filament\Resources\Curricula;

use App\Filament\Resources\Curricula\Pages\CreateCurriculum;
use App\Filament\Resources\Curricula\Pages\EditCurriculum;
use App\Filament\Resources\Curricula\Pages\ListCurricula;
use App\Models\Campus;
use App\Models\Curriculum;
use App\Models\Program;
use App\Models\Subject;
use App\Support\CampusAcademicConfiguration;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class CurriculumResource extends Resource
{
    protected static ?string $model = Curriculum::class;

    protected static bool $isScopedToTenant = false;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string|\UnitEnum|null $navigationGroup = 'Academic Setup';

    protected static ?string $navigationLabel = 'Curriculum Manager';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'curriculum';

    protected static ?string $pluralModelLabel = 'curricula';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Curriculum')
                ->tabs([
                    Tab::make('Overview')
                        ->icon('heroicon-o-identification')
                        ->schema([
                            Section::make('Curriculum identity')
                                ->schema([
                                    TextInput::make('name')->required()->maxLength(255),
                                    TextInput::make('code')->required()->maxLength(50),
                                    TextInput::make('effective_year')->numeric()->required(),
                                    Select::make('status')->options([
                                        'draft' => 'Draft',
                                        'active' => 'Active',
                                        'inactive' => 'Inactive',
                                        'archived' => 'Archived',
                                    ])->required(),
                                ])->columns(2),
                            Section::make('Template provenance')
                                ->description('This identifies the official foundation used to create the editable institutional copy.')
                                ->schema([
                                    TextInput::make('template_authority')->readOnly(),
                                    TextInput::make('template_version')->readOnly(),
                                    TextInput::make('template_source_url')->url()->readOnly()->columnSpanFull(),
                                    Toggle::make('is_customized')->label('Institutionally customized'),
                                ])->columns(2)
                                ->collapsible(),
                        ]),
                    Tab::make('Elective rules')
                        ->icon('heroicon-o-adjustments-horizontal')
                        ->schema([
                            Repeater::make('electiveGroups')
                                ->relationship()
                                ->schema([
                                    TextInput::make('code')->required(),
                                    TextInput::make('name')->required(),
                                    TextInput::make('minimum_subjects')->numeric()->minValue(0)->required(),
                                    TextInput::make('maximum_subjects')->numeric()->minValue(1),
                                    TextInput::make('minimum_units')->numeric()->minValue(0)->required(),
                                    TextInput::make('maximum_units')->numeric()->minValue(0),
                                ])
                                ->columns(3)
                                ->columnSpanFull(),
                        ]),
                    Tab::make('Fees and assessment')
                        ->icon('heroicon-o-banknotes')
                        ->schema([
                            Section::make('Curriculum pricing')
                                ->description('Laboratory fees are charged once per enrolled subject with laboratory hours, regardless of the number of hours.')
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
                                        ->required(),
                                    TextInput::make('laboratory_fee_per_subject')
                                        ->label('Flat laboratory fee per subject')
                                        ->prefix('PHP')
                                        ->numeric()
                                        ->minValue(0)
                                        ->required(),
                                ])
                                ->columns(3),
                            Section::make('Miscellaneous fees')
                                ->description('Each active fee is charged once for every enrollment using this curriculum.')
                                ->schema([
                                    Repeater::make('miscellaneousFees')
                                        ->relationship()
                                        ->orderColumn('position')
                                        ->schema([
                                            TextInput::make('code')->required()->maxLength(50),
                                            TextInput::make('name')->required()->maxLength(255),
                                            TextInput::make('amount')->prefix('PHP')->numeric()->minValue(0)->required(),
                                            Toggle::make('is_active')->label('Active')->default(true),
                                            Textarea::make('description')->rows(2)->columnSpanFull(),
                                        ])
                                        ->columns(4)
                                        ->reorderable()
                                        ->collapsible()
                                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                                        ->addActionLabel('Add miscellaneous fee')
                                        ->columnSpanFull(),
                                ]),
                        ]),
                    Tab::make('Subjects')
                        ->icon('heroicon-o-book-open')
                        ->schema([
                            Repeater::make('items')
                                ->relationship()
                                ->orderColumn('position')
                                ->schema([
                                    Select::make('subject_id')
                                        ->label('Subject')
                                        ->options(fn (): array => Subject::query()
                                            ->where('institution_id', Filament::getTenant()?->institution_id)
                                            ->orderBy('code')
                                            ->get()
                                            ->mapWithKeys(fn (Subject $subject): array => [
                                                $subject->id => "{$subject->code} - {$subject->name}",
                                            ])
                                            ->all())
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->columnSpan(4),
                                    TextInput::make('year_level')->label('Year / grade')->numeric()->columnSpan(1),
                                    TextInput::make('term_sequence')->label('Term')->numeric()->columnSpan(1),
                                    TextInput::make('credit_units')->label('Units')->numeric()->required()->columnSpan(1),
                                    TextInput::make('contact_hours')->label('Lecture')->numeric()->required()->columnSpan(1),
                                    TextInput::make('lab_hours')->label('Lab')->numeric()->required()->columnSpan(1),
                                    TextInput::make('competency_hours')->label('Competency')->numeric()->required()->columnSpan(1),
                                    Toggle::make('is_required')->label('Required')->columnSpan(1),
                                    Select::make('elective_group_id')
                                        ->label('Elective group')
                                        ->relationship('electiveGroup', 'name')
                                        ->columnSpan(3),
                                ])
                                ->columns(12)
                                ->reorderable()
                                ->collapsible()
                                ->columnSpanFull(),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->description(fn (Curriculum $curriculum): string => $curriculum->code),
                TextColumn::make('program.educationLevel.name')
                    ->label('School type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state, Curriculum $curriculum): string => self::schoolTypeLabel($curriculum, $state)),
                TextColumn::make('program.name')
                    ->label('Program')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('effective_year')->label('Effective')->sortable(),
                TextColumn::make('items_count')->counts('items')->label('Subjects')->badge(),
                TextColumn::make('pricing')
                    ->label('Pricing')
                    ->state(fn (Curriculum $curriculum): string => sprintf(
                        '%s %s / unit; %s %s / lab',
                        $curriculum->currency,
                        number_format((float) $curriculum->tuition_per_unit, 2),
                        $curriculum->currency,
                        number_format((float) $curriculum->laboratory_fee_per_subject, 2),
                    )),
                TextColumn::make('status')->badge(),
                IconColumn::make('is_customized')->label('Customized')->boolean(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                    'archived' => 'Archived',
                ]),
                SelectFilter::make('education_level_id')
                    ->label('Education level')
                    ->options(fn (): array => self::configuredEducationLevelOptions())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $query): Builder => $query->whereHas(
                            'program',
                            fn (Builder $programQuery): Builder => $programQuery->where('education_level_id', $data['value']),
                        ),
                    )),
                SelectFilter::make('program_id')
                    ->label('Program')
                    ->options(fn (): array => Program::query()
                        ->where('campus_id', Filament::getTenant()?->getKey())
                        ->where('status', 'active')
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all()),
                SelectFilter::make('effective_year')
                    ->label('Effective year')
                    ->options(fn (): array => Curriculum::query()
                        ->when(
                            Filament::getTenant() !== null,
                            fn (Builder $query): Builder => $query->whereHas(
                                'program',
                                fn (Builder $programQuery): Builder => $programQuery->where('campus_id', Filament::getTenant()?->getKey()),
                            ),
                        )
                        ->distinct()
                        ->orderByDesc('effective_year')
                        ->pluck('effective_year', 'effective_year')
                        ->map(fn (int $year): string => (string) $year)
                        ->all()),
            ])
            ->filtersFormColumns(2)
            ->recordUrl(fn (Curriculum $curriculum): string => self::getUrl('edit', [
                'record' => $curriculum,
                'tenant' => Filament::getTenant(),
            ]));
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $campus = Filament::getTenant();

        return $query->when(
            $campus !== null,
            fn (Builder $builder): Builder => $builder->whereHas(
                'program',
                fn (Builder $builder): Builder => $builder->where('campus_id', $campus->getKey()),
            ),
        );
    }

    /**
     * @return array<int, string>
     */
    public static function configuredEducationLevelOptions(?Campus $campus = null): array
    {
        $campus ??= Filament::getTenant();

        if (! $campus instanceof Campus) {
            return [];
        }

        return app(CampusAcademicConfiguration::class)->educationLevelOptions($campus);
    }

    /**
     * @return array<string, string>
     */
    public static function configuredSchoolTypeOptions(?Campus $campus = null): array
    {
        $campus ??= Filament::getTenant();

        if (! $campus instanceof Campus) {
            return [];
        }

        return app(CampusAcademicConfiguration::class)->schoolTypeOptions($campus);
    }

    /**
     * @return array<int, int>
     */
    public static function configuredEducationLevelIds(Campus $campus): array
    {
        return app(CampusAcademicConfiguration::class)->educationLevelIds($campus);
    }

    public static function applySchoolTypeScope(Builder $query, string $type): Builder
    {
        return match ($type) {
            'elementary' => $query->whereHas('program.educationLevel', fn (Builder $builder): Builder => $builder
                ->where('category', 'grade_school')
                ->orWhere('code', 'ELEM')
                ->orWhere('name', 'like', '%Elementary%')
                ->orWhere('name', 'like', '%Grade School%')),
            'junior_high' => $query->whereHas('program.educationLevel', fn (Builder $builder): Builder => $builder
                ->where(function (Builder $builder): void {
                    $builder
                        ->whereIn('code', ['JHS', 'G7', 'G8', 'G9', 'G10'])
                        ->orWhere('name', 'like', '%Junior High%')
                        ->orWhere('name', 'like', '%Middle School%')
                        ->orWhere('name', 'like', '%Grade 7%')
                        ->orWhere('name', 'like', '%Grade 8%')
                        ->orWhere('name', 'like', '%Grade 9%')
                        ->orWhere('name', 'like', '%Grade 10%');
                })),
            'senior_high' => $query->whereHas('program.educationLevel', fn (Builder $builder): Builder => $builder
                ->where(function (Builder $builder): void {
                    $builder
                        ->whereIn('code', ['SHS', 'G11', 'G12'])
                        ->orWhere('name', 'like', '%Senior High%')
                        ->orWhere('name', 'like', '%Grade 11%')
                        ->orWhere('name', 'like', '%Grade 12%');
                })),
            'college' => $query->whereHas('program.educationLevel', fn (Builder $builder): Builder => $builder
                ->where('category', 'college')
                ->orWhere('code', 'COL')
                ->orWhere('code', 'UG')
                ->orWhere('name', 'like', '%College%')
                ->orWhere('name', 'like', '%Undergraduate%')
                ->orWhere('name', 'like', '%Graduate%')),
            'other' => $query
                ->whereDoesntHave('program.educationLevel', fn (Builder $builder): Builder => $builder
                    ->where('category', 'grade_school')
                    ->orWhere('code', 'ELEM')
                    ->orWhere('name', 'like', '%Elementary%')
                    ->orWhere('name', 'like', '%Grade School%'))
                ->whereDoesntHave('program.educationLevel', fn (Builder $builder): Builder => $builder
                    ->whereIn('code', ['JHS', 'G7', 'G8', 'G9', 'G10'])
                    ->orWhere('name', 'like', '%Junior High%')
                    ->orWhere('name', 'like', '%Middle School%')
                    ->orWhere('name', 'like', '%Grade 7%')
                    ->orWhere('name', 'like', '%Grade 8%')
                    ->orWhere('name', 'like', '%Grade 9%')
                    ->orWhere('name', 'like', '%Grade 10%'))
                ->whereDoesntHave('program.educationLevel', fn (Builder $builder): Builder => $builder
                    ->whereIn('code', ['SHS', 'G11', 'G12'])
                    ->orWhere('name', 'like', '%Senior High%')
                    ->orWhere('name', 'like', '%Grade 11%')
                    ->orWhere('name', 'like', '%Grade 12%'))
                ->whereDoesntHave('program.educationLevel', fn (Builder $builder): Builder => $builder
                    ->where('category', 'college')
                    ->orWhere('code', 'COL')
                    ->orWhere('code', 'UG')
                    ->orWhere('name', 'like', '%College%')
                    ->orWhere('name', 'like', '%Undergraduate%')
                    ->orWhere('name', 'like', '%Graduate%')),
            default => $query,
        };
    }

    private static function schoolTypeLabel(Curriculum $curriculum, ?string $educationLevelName): string
    {
        $educationLevel = $curriculum->program?->educationLevel;

        if ($educationLevel === null) {
            return 'Other';
        }

        $campusAcademicConfiguration = app(CampusAcademicConfiguration::class);

        return $campusAcademicConfiguration->schoolTypeName(
            $campusAcademicConfiguration->schoolTypeKey($educationLevel),
            $educationLevelName,
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCurricula::route('/'),
            'create' => CreateCurriculum::route('/create'),
            'edit' => EditCurriculum::route('/{record}/edit'),
        ];
    }
}
