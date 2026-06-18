<?php

use Illuminate\Support\Facades\Route;
use Modules\Admissions\Http\Controllers\AdminApplicationQueueController;
use Modules\Admissions\Http\Controllers\ApplicationController;
use Modules\Admissions\Http\Controllers\ApplicationDocumentController;

Route::prefix('campus/{campus:slug}')->middleware(['auth', 'verified', 'campus'])->scopeBindings()->group(function (): void {
    Route::get('/applications', [ApplicationController::class, 'index'])->name('applications.index');
    Route::post('/applications', [ApplicationController::class, 'store'])->name('applications.store');
    Route::get('/admin/applications', AdminApplicationQueueController::class)->name('admin.applications.index');
    Route::post('/applications/{application}/documents', [ApplicationDocumentController::class, 'store'])->name('application-documents.store');
    Route::get('/application-documents/{applicationDocument}', ApplicationDocumentController::class)->name('application-documents.download');
});
