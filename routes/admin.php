<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Models\Beneficiary;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Mounted under `/admin` with the `auth` + `role` middleware applied
| from routes/web.php. Anything in here is reachable only to users whose
| role is in App\Enums\UserRole::adminRoles().
|
| Module index routes below use Route::view() while we scaffold each
| module out as a placeholder. They will be converted to resource
| controllers in the upcoming phases (beneficiaries, events, donations,
| volunteers, testimonials, media, instagram, reports, users).
|
*/

Route::redirect('/', '/admin/dashboard');

Route::get('/dashboard', DashboardController::class)->name('dashboard');
Route::get('/dashboard/export', [DashboardController::class, 'exportActivity'])->name('dashboard.export');

// Beneficiaries + applications · Events · Donations · Reports (Owner · Admin · Foundation Staff)
Route::middleware('role:owner,super_admin,foundation_staff')->group(function () {
    Route::view('/beneficiaries', 'admin.beneficiaries.index')
        ->name('beneficiaries.index');

    Route::get('/beneficiaries/{beneficiary}', function (Beneficiary $beneficiary) {
        return view('admin.beneficiaries.show', ['beneficiary' => $beneficiary]);
    })->name('beneficiaries.show');

    Route::view('/beneficiary-applications', 'admin.beneficiary-applications.index')
        ->name('beneficiary-applications.index');

    Route::view('/events', 'admin.events.index')
        ->name('events.index');

    Route::view('/donations', 'admin.donations.index')
        ->name('donations.index');

    Route::view('/reports', 'admin.reports.index')
        ->name('reports.index');
});

// Volunteers (Owner · Admin · Volunteer Coordinator)
Route::middleware('role:owner,super_admin,volunteer_coordinator')->group(function () {
    Route::view('/volunteers', 'admin.volunteers.index')
        ->name('volunteers.index');
});

// Testimonials · Media · Instagram · Newsletter (Owner · Admin · Media Manager · Foundation Staff for newsletter)
Route::middleware('role:owner,super_admin,media_manager,foundation_staff')->group(function () {
    Route::view('/testimonials', 'admin.testimonials.index')
        ->name('testimonials.index');

    Route::view('/leaders', 'admin.leaders.index')
        ->name('leaders.index');

    Route::view('/media', 'admin.media.index')
        ->name('media.index');

    Route::view('/instagram', 'admin.instagram.index')
        ->name('instagram.index');

    Route::view('/newsletter', 'admin.newsletter.index')
        ->name('newsletter.index');
});

// Email inbox (Owner · Admin · Foundation Staff · Volunteer Coordinator · Media Manager)
Route::middleware('role:owner,super_admin,foundation_staff,volunteer_coordinator,media_manager')->group(function () {
    Route::view('/inbox', 'admin.inbox.index')
        ->name('inbox.index');
});

// User management (Owner · Admin)
Route::middleware('role:owner,super_admin')->group(function () {
    Route::view('/users', 'admin.users.index')
        ->name('users.index');
});

// System logs (Owner only — top-level super admin access)
Route::middleware('role:owner')->group(function () {
    Route::view('/system-logs', 'admin.system-logs.index')
        ->name('system-logs.index');
});
