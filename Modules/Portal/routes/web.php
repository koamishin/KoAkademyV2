<?php

use App\Http\Controllers\AdminCurriculumManagerController;
use Illuminate\Support\Facades\Route;
use Modules\Portal\Http\Controllers\CampusDashboardController;
use Modules\Portal\Http\Controllers\DashboardRedirectController;

Route::get('dashboard', DashboardRedirectController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('campus-assignment-required', fn () => inertia('CampusAssignmentRequired'))
    ->middleware(['auth', 'verified'])
    ->name('campus.assignment.pending');

Route::prefix('campus/{campus:slug}')
    ->middleware(['auth', 'verified', 'campus'])
    ->scopeBindings()
    ->group(function (): void {
        Route::get('dashboard', CampusDashboardController::class)->name('campus.dashboard');
        Route::get('admin/curricula', AdminCurriculumManagerController::class)->name('admin.curricula.index');
    });
