<x-mail::message>
# Hello {{ $sponsorship->sponsor_name }}

@if ($isOverdue)
Our records show your monthly pledge for **{{ $childName }}** was due on **{{ $sponsorship->next_due_on->format('F j, Y') }}**. If you've already given, thank you, and please ignore this note: your gift may still be on its way to us.
@else
Your monthly pledge for **{{ $childName }}** is due on **{{ $sponsorship->next_due_on->format('F j, Y') }}**.
@endif

Pledge amount: **{{ \App\Support\Money::fromCents($sponsorship->monthly_amount_cents, 2) }}**

<x-mail::button :url="route('donate').'#how-to-give'">
See how to give
</x-mail::button>

If your circumstances have changed, just reply to this email. We'd love to hear from you.

With gratitude,<br>
{{ config('app.name') }}

<x-slot:subcopy>
Prefer not to get reminders? [Stop reminder emails]({{ $stopRemindersUrl }}).
</x-slot:subcopy>
</x-mail::message>
