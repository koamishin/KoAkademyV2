<?php

use Illuminate\Support\Facades\Route;
use Modules\Classroom\Http\Controllers\AdminClassOperationsController;
use Modules\Classroom\Http\Controllers\ClassroomController;

Route::prefix('campus/{campus:slug}')->middleware(['auth', 'verified', 'campus'])->scopeBindings()->group(function (): void {
    Route::get('/classes', [ClassroomController::class, 'index'])->name('classroom.index');
    Route::get('/classes/{classOffering}', [ClassroomController::class, 'show'])->name('classroom.show');
    Route::get('/admin/classes', AdminClassOperationsController::class)->name('admin.classes.index');
});
