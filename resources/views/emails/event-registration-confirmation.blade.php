<x-mail::message>
# {{ __("You're signed up, :v1", ['v1' => $registration->full_name]) }}

{{ __("We've saved your place at **:v1**.", ['v1' => $registration->event->title]) }}

- **{{ __('Date:') }}** {{ $registration->event->starts_at?->isoFormat('LL') }}
- **{{ __('Where:') }}** {{ $registration->event->location }}, {{ $registration->event->country }}
- **{{ __('People:') }}** {{ $registration->party_size }}

<x-mail::button :url="route('events.show', $registration->event)">
{{ __('View event details') }}
</x-mail::button>

{{ __("Can't make it after all? Just reply to this email and we'll free up your place.") }}

{{ __('With gratitude,') }}<br>
{{ config('app.name') }}
</x-mail::message>
