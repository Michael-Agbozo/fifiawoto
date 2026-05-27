<x-mail::message>
# Hello {{ $volunteer->full_name }},

We're putting together a new event and would love your help.

## {{ $event->title }}

- **When:** {{ $event->starts_at?->format('F j, Y') }}@if ($event->ends_at) – {{ $event->ends_at->format('F j, Y') }}@endif
- **Where:** {{ $event->location }}, {{ $event->country }}

{{ \Illuminate\Support\Str::limit($event->description, 600) }}

@if (filled($event->volunteer_opportunities))
### What we need help with

{{ $event->volunteer_opportunities }}
@endif

If you'd like to volunteer for this one, reply to this email and we'll get back to you with details. No pressure if the timing doesn't work — we'll keep you in the loop on future opportunities.

With gratitude,<br>
{{ config('app.name') }}
</x-mail::message>
