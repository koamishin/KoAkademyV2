<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\UserManagement\Http\Controllers\AdminUserController;
use Modules\UserManagement\Http\Controllers\AdminUserEmailVerificationController;
use Modules\UserManagement\Http\Controllers\AdminUserForceLogoutController;
use Modules\UserManagement\Http\Controllers\AdminUserImpersonationController;
use Modules\UserManagement\Http\Controllers\AdminUserPasswordResetController;

Route::prefix('campus/{campus:slug}/admin/users')
    ->middleware(['auth', 'verified', 'campus'])
    ->scopeBindings()
    ->name('admin.users.')
    ->group(function (): void {
        Route::get('/', [AdminUserController::class, 'index'])->name('index');
        Route::post('/', [AdminUserController::class, 'store'])->name('store');
        Route::patch('{user}', [AdminUserController::class, 'update'])->name('update');
        Route::post('{user}/verify-email', AdminUserEmailVerificationController::class)->name('verify-email');
        Route::post('{user}/unverify-email', [AdminUserEmailVerificationController::class, 'destroy'])->name('unverify-email');
        Route::post('{user}/send-password-reset', AdminUserPasswordResetController::class)->name('send-password-reset');
        Route::post('{user}/impersonate', AdminUserImpersonationController::class)->name('impersonate');
        Route::delete('{user}/sessions', AdminUserForceLogoutController::class)->name('force-logout');
    });
