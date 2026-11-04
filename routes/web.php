<?php

use App\Http\Controllers\Site\EventController;
use App\Http\Controllers\Site\LegalController;
use App\Http\Controllers\Site\NewsletterController;
use App\Http\Controllers\Site\PageController;
use App\Http\Controllers\Site\SitemapController;
use App\Http\Controllers\Site\SponsorshipController;
use App\Http\Controllers\Site\SponsorshipReminderController;
use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/sponsor-a-child', [SponsorshipController::class, 'index'])->name('sponsor-a-child');
Route::get('/sponsor-a-child/{profile}/sponsor', [SponsorshipController::class, 'checkout'])->where('profile', '[a-z0-9-]+')->name('sponsor-a-child.checkout');
Route::get('/sponsor-a-child/{profile}', [SponsorshipController::class, 'show'])->where('profile', '[a-z0-9-]+')->name('sponsor-a-child.show');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/volunteer', [PageController::class, 'volunteer'])->name('volunteer');
Route::get('/apply-for-support', [PageController::class, 'applyForSupport'])->name('apply-for-support');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::get('/donate', [PageController::class, 'donate'])->name('donate');
Route::get('/testimonials', [PageController::class, 'testimonials'])->name('testimonials');
Route::get('/media', [PageController::class, 'media'])->name('media');
Route::get('/media/events/{event:slug}', [PageController::class, 'mediaEvent'])->name('media.event');
Route::get('/media/collections/{category}', [PageController::class, 'mediaCollection'])->where('category', '[a-z_]+')->name('media.collection');

Route::get('/events', [EventController::class, 'index'])->name('events.index');
Route::get('/events/{event:slug}', [EventController::class, 'show'])->name('events.show');

Route::post('/language/{locale}', function (Request $request, string $locale) {
    $request->session()->put('locale', $locale);

    return back();
})->whereIn('locale', SetLocale::LOCALES)->name('language.switch');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

Route::prefix('newsletter')->as('newsletter.')->middleware('signed')->group(function () {
    Route::get('/unsubscribe/{subscriber}', [NewsletterController::class, 'showUnsubscribe'])->name('unsubscribe');
    Route::post('/unsubscribe/{subscriber}', [NewsletterController::class, 'unsubscribe'])->name('unsubscribe.confirm');
    Route::get('/resubscribe/{subscriber}', [NewsletterController::class, 'showResubscribe'])->name('resubscribe');
    Route::post('/resubscribe/{subscriber}', [NewsletterController::class, 'resubscribe'])->name('resubscribe.confirm');
});

Route::prefix('sponsorship-reminders')->as('sponsorship-reminders.')->middleware('signed')->group(function () {
    Route::get('/stop/{sponsorship}', [SponsorshipReminderController::class, 'show'])->name('stop');
    Route::post('/stop/{sponsorship}', [SponsorshipReminderController::class, 'stop'])->name('stop.confirm');
});

Route::prefix('legal')->as('legal.')->group(function () {
    Route::get('/privacy', [LegalController::class, 'privacy'])->name('privacy');
    Route::get('/terms', [LegalController::class, 'terms'])->name('terms');
    Route::get('/cookies', [LegalController::class, 'cookies'])->name('cookies');
    Route::get('/disclaimer', [LegalController::class, 'disclaimer'])->name('disclaimer');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        $user = auth()->user();

        if ($user?->canAccessAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return view('dashboard');
    })->name('dashboard');
});

Route::middleware(['auth', 'verified', 'role'])
    ->prefix('admin')
    ->as('admin.')
    ->group(__DIR__.'/admin.php');

require __DIR__.'/settings.php';
