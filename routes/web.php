<?php

use App\Http\Controllers\Site\EventController;
use App\Http\Controllers\Site\LegalController;
use App\Http\Controllers\Site\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/volunteer', [PageController::class, 'volunteer'])->name('volunteer');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::get('/donate', [PageController::class, 'donate'])->name('donate');
Route::get('/testimonials', [PageController::class, 'testimonials'])->name('testimonials');
Route::get('/media', [PageController::class, 'media'])->name('media');

Route::get('/events', [EventController::class, 'index'])->name('events.index');
Route::get('/events/{event:slug}', [EventController::class, 'show'])->name('events.show');

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
