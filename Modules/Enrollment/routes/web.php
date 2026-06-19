<?php

use Illuminate\Support\Facades\Route;
use Modules\Enrollment\Http\Controllers\AcademicHistoryController;
use Modules\Enrollment\Http\Controllers\AdminEnrollmentQueueController;
use Modules\Enrollment\Http\Controllers\AdminStudentController;
use Modules\Enrollment\Http\Controllers\EnrollmentSubjectController;
use Modules\Enrollment\Http\Controllers\StudentEnrollmentApprovalController;
use Modules\Enrollment\Http\Controllers\StudentEnrollmentController;

Route::prefix('campus/{campus:slug}')
    ->middleware(['auth', 'verified', 'campus'])
    ->scopeBindings()
    ->group(function (): void {
        Route::get('/academic-history', AcademicHistoryController::class)
            ->name('academic-history.show');
        Route::get('/admin/enrollments', AdminEnrollmentQueueController::class)
            ->name('admin.enrollments.index');
        Route::prefix('/admin/students')
            ->name('admin.students.')
            ->group(function (): void {
                Route::get('/', [AdminStudentController::class, 'index'])->name('index');
                Route::post('/', [AdminStudentController::class, 'store'])->name('store');
                Route::get('/{student}', [AdminStudentController::class, 'show'])->name('show');
                Route::patch('/{student}', [AdminStudentController::class, 'update'])->name('update');
                Route::post('/{student}/enrollments', [StudentEnrollmentController::class, 'store'])->name('enrollments.store');
                Route::post('/{student}/enrollments/{enrollment}/approve', [StudentEnrollmentApprovalController::class, 'store'])->name('enrollments.approve');
                Route::post('/{student}/enrollments/{enrollment}/cancel', [StudentEnrollmentController::class, 'cancel'])->name('enrollments.cancel');
                Route::patch('/{student}/enrollments/{enrollment}/subjects/{enrollmentSubject}', [EnrollmentSubjectController::class, 'update'])->name('enrollment-subjects.update');
            });
    });
