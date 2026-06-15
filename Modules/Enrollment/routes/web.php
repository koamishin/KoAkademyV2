<?php

use Illuminate\Support\Facades\Route;
use Modules\Enrollment\Http\Controllers\AcademicHistoryController;

Route::prefix('campus/{campus:slug}')
    ->middleware(['auth', 'verified', 'campus'])
    ->scopeBindings()
    ->group(function (): void {
        Route::get('/academic-history', AcademicHistoryController::class)
            ->name('academic-history.show');
    });
