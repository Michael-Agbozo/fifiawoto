<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Signed newsletter links. GET renders a confirmation page (so link scanners
 * that prefetch URLs can't change anything); POST to the same signed URL acts.
 */
class NewsletterController extends Controller
{
    public function showUnsubscribe(Request $request, NewsletterSubscriber $subscriber): View
    {
        return view('site.newsletter', [
            'action' => 'unsubscribe',
            'done' => false,
            'subscriber' => $subscriber,
            'formUrl' => $request->fullUrl(),
        ]);
    }

    public function unsubscribe(NewsletterSubscriber $subscriber): View
    {
        if ($subscriber->unsubscribed_at === null) {
            $subscriber->forceFill(['unsubscribed_at' => now()])->save();
        }

        return view('site.newsletter', [
            'action' => 'unsubscribe',
            'done' => true,
            'subscriber' => $subscriber,
            'formUrl' => null,
        ]);
    }

    public function showResubscribe(Request $request, NewsletterSubscriber $subscriber): View
    {
        return view('site.newsletter', [
            'action' => 'resubscribe',
            'done' => false,
            'subscriber' => $subscriber,
            'formUrl' => $request->fullUrl(),
        ]);
    }

    public function resubscribe(NewsletterSubscriber $subscriber): View
    {
        $subscriber->forceFill([
            'subscribed_at' => now(),
            'unsubscribed_at' => null,
        ])->save();

        return view('site.newsletter', [
            'action' => 'resubscribe',
            'done' => true,
            'subscriber' => $subscriber,
            'formUrl' => null,
        ]);
    }
}
