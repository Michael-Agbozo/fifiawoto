<x-mail::message>
# You're signed up, {{ $registration->full_name }}

We've saved your place at **{{ $registration->event->title }}**.

- **Date:** {{ $registration->event->starts_at?->format('F j, Y') }}
- **Where:** {{ $registration->event->location }}, {{ $registration->event->country }}
- **People:** {{ $registration->party_size }}

<x-mail::button :url="route('events.show', $registration->event)">
View event details
</x-mail::button>

Can't make it after all? Just reply to this email and we'll free up your place.

With gratitude,<br>
{{ config('app.name') }}
</x-mail::message>
