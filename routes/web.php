<?php

use App\Enums\SocialLoginProvider;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\ImpersonateController;
use App\Http\Controllers\NotificationController;
use App\Models\User;
use App\Support\CampusMembershipProvisioner;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', fn () => Inertia::render('Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
]))->name('home');

Route::get('dashboard', function (CampusMembershipProvisioner $campusMembershipProvisioner) {
    /** @var User $user */
    $user = request()->user();
    $campus = $campusMembershipProvisioner->provision($user);

    if (!$campus instanceof \App\Models\Campus) {
        return to_route('campus.assignment.pending');
    }

    return to_route('campus.dashboard', ['campus' => $campus]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('campus-assignment-required', fn () => Inertia::render('CampusAssignmentRequired'))
    ->middleware(['auth', 'verified'])
    ->name('campus.assignment.pending');

Route::prefix('campus/{campus:slug}')
    ->middleware(['auth', 'verified', 'campus'])
    ->scopeBindings()
    ->group(function (): void {
        Route::get('dashboard', fn () => Inertia::render('Dashboard'))->name('campus.dashboard');
        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-as-read');
        Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    });

Route::middleware('web')->group(function (): void {
    /** @phpstan-ignore-next-line */
    Route::impersonate();
    Route::get('impersonate/take-redirect', [ImpersonateController::class, 'takeRedirect'])->name('impersonate.take-redirect');
    Route::get('impersonate/leave-redirect', [ImpersonateController::class, 'leaveRedirect'])->name('impersonate.leave-redirect');
});

$socialProviders = implode('|', SocialLoginProvider::values());

Route::get('auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
    ->where('provider', $socialProviders)
    ->name('auth.social.redirect');

Route::get('auth/{provider}/callback', [SocialAuthController::class, 'callback'])
    ->where('provider', $socialProviders)
    ->name('auth.social.callback');

require __DIR__.'/settings.php';
