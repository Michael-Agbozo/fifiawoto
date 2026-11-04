<x-mail::message>
# Thank you, {{ $sponsorship->sponsor_name }}

Your sponsorship of **{{ $childName }}** is now active. Your monthly pledge is **{{ \App\Support\Money::fromCents($sponsorship->monthly_amount_cents, 2) }}**, and your first gift is due on **{{ $sponsorship->next_due_on->format('F j, Y') }}**.

<x-mail::button :url="route('donate').'#how-to-give'">
See how to give
</x-mail::button>

We'll send you a short reminder a few days before each monthly gift. Reply to this email if anything changes.

With gratitude,<br>
{{ config('app.name') }}

<x-slot:subcopy>
Prefer not to get reminders? [Stop reminder emails]({{ $stopRemindersUrl }}).
</x-slot:subcopy>
</x-mail::message>
