<?php

use Illuminate\Support\Facades\Route;
use Modules\Enrollment\Http\Controllers\AcademicHistoryController;
use Modules\Enrollment\Http\Controllers\AdminEnrollmentQueueController;

Route::prefix('campus/{campus:slug}')
    ->middleware(['auth', 'verified', 'campus'])
    ->scopeBindings()
    ->group(function (): void {
        Route::get('/academic-history', AcademicHistoryController::class)
            ->name('academic-history.show');
        Route::get('/admin/enrollments', AdminEnrollmentQueueController::class)
            ->name('admin.enrollments.index');
    });
