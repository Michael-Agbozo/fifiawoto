<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Sponsorship;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Signed link in reminder emails. GET renders a confirmation page (so link
 * scanners that prefetch URLs can't change anything); POST to the same URL acts.
 */
class SponsorshipReminderController extends Controller
{
    public function show(Request $request, Sponsorship $sponsorship): View
    {
        return view('site.sponsorship-reminders', ['done' => false, 'formUrl' => $request->fullUrl()]);
    }

    public function stop(Sponsorship $sponsorship): View
    {
        $sponsorship->update(['reminders_enabled' => false]);

        return view('site.sponsorship-reminders', ['done' => true, 'formUrl' => null]);
    }
}
