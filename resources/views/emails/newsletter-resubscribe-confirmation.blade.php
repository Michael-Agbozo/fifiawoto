<x-mail::message>
# Confirm your subscription

Someone (hopefully you) asked to subscribe **{{ $subscriber->email }}** to the {{ config('app.name') }} newsletter. This address previously unsubscribed, so we won't send anything until you confirm.

<x-mail::button :url="$confirmUrl">
Yes, subscribe me again
</x-mail::button>

If you didn't request this, you can ignore this email and you'll stay unsubscribed. The link expires in {{ \App\Mail\NewsletterResubscribeConfirmation::LINK_TTL_HOURS }} hours.

With gratitude,<br>
{{ config('app.name') }}

<x-slot:subcopy>
Don't want to hear from us? [Unsubscribe]({{ $unsubscribeUrl }}).
</x-slot:subcopy>
</x-mail::message>
