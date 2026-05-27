<x-mail::message>
# Thank you, {{ $donation->donor_name }}!

We have recorded your gift of **{{ $donation->currency }} {{ number_format($donation->amount_cents / 100, 2) }}** received on {{ $donation->received_at?->format('F j, Y') }}.

@if ($donation->event)
This contribution is supporting our **{{ $donation->event->title }}** program.
@endif

@if ($donation->external_reference)
**Reference:** {{ $donation->external_reference }}
@endif

Your generosity helps us walk alongside widows, children, and families across the community. We are grateful.

With gratitude,<br>
{{ config('app.name') }}
</x-mail::message>
