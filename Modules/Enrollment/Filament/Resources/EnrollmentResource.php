<?php

declare(strict_types=1);

namespace Modules\Enrollment\Filament\Resources;

use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Enrollment\Actions\ApproveEnrollment;
use Modules\Enrollment\Enums\EnrollmentStatus;
use Modules\Enrollment\Filament\Resources\EnrollmentResource\Pages;
use Modules\Enrollment\Models\Enrollment;

final class EnrollmentResource extends Resource
{
    protected static ?string $model = Enrollment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|\UnitEnum|null $navigationGroup = 'Enrollment';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('student.full_name')->label('Student'), TextColumn::make('student_number')->searchable(), TextColumn::make('classification')->badge(), TextColumn::make('status')->badge(), TextColumn::make('approved_at')->dateTime(),
        ])->recordActions([Action::make('approve')->color('success')->requiresConfirmation()->visible(fn (Enrollment $record) => $record->status !== EnrollmentStatus::Approved)->action(fn (Enrollment $record, ApproveEnrollment $approve) => $approve->execute($record, auth()->user()))]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListEnrollments::route('/')];
    }
}
