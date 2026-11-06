<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DocumentDownloadController;
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

/*
| Each page is gated on the same "{resource}.view" key the sidebar and its Livewire
| component check, so role defaults and per-user grants behave alike. See
| docs/PERMISSIONS.md. Three things stay locked to a role list on purpose: identity and
| medical files, user management, and system logs. Per-user grants don't open those.
*/

Route::view('/beneficiaries', 'admin.beneficiaries.index')
    ->middleware('permission:beneficiaries.view')
    ->name('beneficiaries.index');

Route::get('/beneficiaries/{beneficiary}', function (Beneficiary $beneficiary) {
    return view('admin.beneficiaries.show', ['beneficiary' => $beneficiary]);
})->middleware('permission:beneficiaries.view')->name('beneficiaries.show');

// Record photo: shown wherever the record is, so it's gated on the same view permission (checked in the controller).
Route::get('/beneficiaries/{beneficiary}/photo', [DocumentDownloadController::class, 'beneficiaryPhoto'])
    ->name('beneficiaries.photo');

// Private beneficiary + application files (Owner · Admin · Foundation Staff)
Route::middleware('role:owner,super_admin,foundation_staff')->group(function () {
    Route::get('/beneficiaries/{beneficiary}/documents/{document}', [DocumentDownloadController::class, 'beneficiaryDocument'])
        ->middleware('signed')
        ->scopeBindings()
        ->name('beneficiaries.documents.show');

    Route::get('/beneficiary-applications/{application}/documents/{document}', [DocumentDownloadController::class, 'applicationDocument'])
        ->middleware('signed')
        ->scopeBindings()
        ->name('beneficiary-applications.documents.show');
});

Route::view('/beneficiary-applications', 'admin.beneficiary-applications.index')
    ->middleware('permission:beneficiary_applications.view')
    ->name('beneficiary-applications.index');

Route::view('/volunteers', 'admin.volunteers.index')
    ->middleware('permission:volunteers.view')
    ->name('volunteers.index');

Route::view('/events', 'admin.events.index')
    ->middleware('permission:events.view')
    ->name('events.index');

Route::view('/donations', 'admin.donations.index')
    ->middleware('permission:donations.view')
    ->name('donations.index');

Route::view('/sponsorships', 'admin.sponsorships.index')
    ->middleware('permission:sponsorships.view')
    ->name('sponsorships.index');

Route::view('/reports', 'admin.reports.index')
    ->middleware('permission:reports.view')
    ->name('reports.index');

Route::view('/testimonials', 'admin.testimonials.index')
    ->middleware('permission:testimonials.view')
    ->name('testimonials.index');

Route::view('/leaders', 'admin.leaders.index')
    ->middleware('permission:leaders.view')
    ->name('leaders.index');

Route::view('/faqs', 'admin.faqs.index')
    ->middleware('permission:faqs.view')
    ->name('faqs.index');

Route::view('/media', 'admin.media.index')
    ->middleware('permission:media.view')
    ->name('media.index');

Route::view('/instagram', 'admin.instagram.index')
    ->middleware('permission:instagram.view')
    ->name('instagram.index');

Route::view('/newsletter', 'admin.newsletter.index')
    ->middleware('permission:newsletter.view')
    ->name('newsletter.index');

Route::view('/newsletter/campaigns', 'admin.newsletter.campaigns')
    ->middleware('permission:newsletter.view')
    ->name('newsletter.campaigns');

Route::view('/analytics', 'admin.analytics.index')
    ->middleware('permission:analytics.view')
    ->name('analytics.index');

Route::view('/inbox', 'admin.inbox.index')
    ->middleware('permission:inbox.view')
    ->name('inbox.index');

Route::view('/settings/site', 'admin.settings.site')
    ->middleware('permission:site_settings.view')
    ->name('settings.site');

Route::view('/settings/popup', 'admin.settings.popup')
    ->middleware('permission:site_popup.view')
    ->name('settings.popup');

// User management (Owner · Admin)
Route::view('/users', 'admin.users.index')
    ->middleware('role:owner,super_admin')
    ->name('users.index');

// System logs (Owner only — top-level super admin access)
Route::view('/system-logs', 'admin.system-logs.index')
    ->middleware('role:owner')
    ->name('system-logs.index');
