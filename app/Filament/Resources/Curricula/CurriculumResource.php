<?php

declare(strict_types=1);

namespace App\Filament\Resources\Curricula;

use App\Filament\Resources\Curricula\Pages\EditCurriculum;
use App\Filament\Resources\Curricula\Pages\ListCurricula;
use App\Models\Curriculum;
use App\Models\Subject;
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

    protected static bool $shouldRegisterNavigation = false;

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
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('program.name')->label('Program')->searchable(),
                TextColumn::make('effective_year')->label('Effective')->sortable(),
                TextColumn::make('template_authority')->label('Foundation')->placeholder('Custom'),
                TextColumn::make('items_count')->counts('items')->label('Subjects')->badge(),
                IconColumn::make('is_customized')->label('Customized')->boolean(),
                TextColumn::make('status')->badge(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                    'archived' => 'Archived',
                ]),
            ])
            ->recordUrl(fn (Curriculum $record): string => self::getUrl('edit', [
                'record' => $record,
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
                fn (Builder $programQuery): Builder => $programQuery->where('campus_id', $campus->getKey()),
            ),
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCurricula::route('/'),
            'edit' => EditCurriculum::route('/{record}/edit'),
        ];
    }
}
