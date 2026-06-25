<?php

use Illuminate\Support\Facades\Route;
use Modules\Enrollment\Http\Controllers\AcademicHistoryController;
use Modules\Enrollment\Http\Controllers\AdminEnrollmentQueueController;
use Modules\Enrollment\Http\Controllers\AdminStudentController;
use Modules\Enrollment\Http\Controllers\EnrollmentSubjectController;
use Modules\Enrollment\Http\Controllers\StudentDocumentController;
use Modules\Enrollment\Http\Controllers\StudentEnrollmentApprovalController;
use Modules\Enrollment\Http\Controllers\StudentEnrollmentController;
use Modules\Enrollment\Http\Controllers\TransferCreditEvaluationController;

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
                Route::get('/create', [AdminStudentController::class, 'create'])->name('create');
                Route::post('/', [AdminStudentController::class, 'store'])->name('store');
                Route::get('/{student}', [AdminStudentController::class, 'show'])->withTrashed()->name('show');
                Route::get('/{student}/edit', [AdminStudentController::class, 'edit'])->withTrashed()->name('edit');
                Route::patch('/{student}', [AdminStudentController::class, 'update'])->withTrashed()->name('update');
                Route::delete('/{student}', [AdminStudentController::class, 'destroy'])->name('destroy');
                Route::post('/{student}/restore', [AdminStudentController::class, 'restore'])->withTrashed()->name('restore');
                Route::post('/{student}/documents', [StudentDocumentController::class, 'store'])->name('documents.store');
                Route::patch('/{student}/documents/{document}', [StudentDocumentController::class, 'update'])->name('documents.update');
                Route::post('/{student}/transfer-credits', [TransferCreditEvaluationController::class, 'store'])->name('transfer-credits.store');
                Route::post('/{student}/enrollments', [StudentEnrollmentController::class, 'store'])->name('enrollments.store');
                Route::post('/{student}/enrollments/{enrollment}/approve', [StudentEnrollmentApprovalController::class, 'store'])->name('enrollments.approve');
                Route::post('/{student}/enrollments/{enrollment}/cancel', [StudentEnrollmentController::class, 'cancel'])->name('enrollments.cancel');
                Route::patch('/{student}/enrollments/{enrollment}/subjects/{enrollmentSubject}', [EnrollmentSubjectController::class, 'update'])->name('enrollment-subjects.update');
            });
    });
