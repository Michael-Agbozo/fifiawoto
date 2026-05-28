<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $query = Event::query()
            ->published()
            ->withSum('donations as raised_cents', 'amount_cents');

        if ($search !== '') {
            $needle = '%'.$search.'%';
            $query->where(function ($q) use ($needle) {
                $q->where('title', 'like', $needle)
                    ->orWhere('location', 'like', $needle)
                    ->orWhere('country', 'like', $needle)
                    ->orWhere('description', 'like', $needle);
            });
        }

        $events = $query->orderBy('starts_at')->get();

        [$upcoming, $past] = $events->partition(fn (Event $e) => $e->starts_at?->isFuture() ?? false);

        return view('site.events.index', [
            'upcoming' => $upcoming->values(),
            'past' => $past->values(),
            'search' => $search,
        ]);
    }

    public function show(Event $event): View
    {
        abort_unless($event->trashed() === false && $event->status->value === 'published', 404);

        $event->load(['images', 'donations']);

        return view('site.events.show', [
            'event' => $event,
        ]);
    }
}
