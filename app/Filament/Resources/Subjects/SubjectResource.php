<?php

declare(strict_types=1);

namespace App\Filament\Resources\Subjects;

use App\Models\Subject;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class SubjectResource extends Resource
{
    protected static ?string $model = Subject::class;

    protected static bool $isScopedToTenant = false;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|\UnitEnum|null $navigationGroup = 'Academic Setup';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Subject identity')
                ->description('Reusable catalog details shared by curricula in this institution.')
                ->icon('heroicon-o-book-open')
                ->schema([
                    Select::make('institution_id')
                        ->relationship('institution', 'name')
                        ->required()
                        ->disabled()
                        ->saved(),
                    TextInput::make('code')->required()->maxLength(50),
                    TextInput::make('name')->required()->maxLength(255)->columnSpanFull(),
                    Textarea::make('description')->rows(3)->columnSpanFull(),
                    Select::make('subject_type')
                        ->options([
                            'academic' => 'Academic',
                            'laboratory' => 'Laboratory',
                            'competency' => 'TESDA Competency',
                        ])
                        ->required(),
                    Select::make('status')
                        ->options([
                            'active' => 'Active',
                            'inactive' => 'Inactive',
                            'archived' => 'Archived',
                        ])
                        ->required(),
                ])
                ->columns(2),
            Section::make('Default academic load')
                ->description('These defaults are copied when the subject is added to another curriculum.')
                ->icon('heroicon-o-clock')
                ->schema([
                    TextInput::make('default_credit_units')->label('Credit units')->numeric()->minValue(0)->default(0),
                    TextInput::make('default_contact_hours')->label('Lecture hours')->numeric()->minValue(0)->default(0),
                    TextInput::make('default_lab_hours')->label('Laboratory hours')->numeric()->minValue(0)->default(0),
                    TextInput::make('default_competency_hours')->label('Competency hours')->numeric()->minValue(0)->default(0),
                ])
                ->columns(4),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->searchable()->copyable()->weight('semibold'),
                TextColumn::make('name')->searchable()->sortable()->description(fn (Subject $subject): ?string => $subject->description),
                TextColumn::make('subject_type')->badge(),
                TextColumn::make('default_credit_units')->label('Units')->numeric(decimalPlaces: 2),
                TextColumn::make('curriculum_items_count')->counts('curriculumItems')->label('Curricula')->badge(),
                IconColumn::make('status')->boolean()->getStateUsing(fn (Subject $subject): bool => $subject->status === 'active')->label('Active'),
            ])
            ->filters([
                SelectFilter::make('subject_type')->options([
                    'academic' => 'Academic',
                    'laboratory' => 'Laboratory',
                    'competency' => 'TESDA Competency',
                ]),
                SelectFilter::make('status')->options([
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                    'archived' => 'Archived',
                ]),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListSubjects::route('/'), 'create' => Pages\CreateSubject::route('/create'), 'edit' => Pages\EditSubject::route('/{record}/edit')];
    }
}
