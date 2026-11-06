<x-mail::message>
# {{ __('Confirm your subscription') }}

{{ __("Someone (hopefully you) asked to subscribe **:v1** to the :v2 newsletter. This address previously unsubscribed, so we won't send anything until you confirm.", ['v1' => $subscriber->email, 'v2' => config('app.name')]) }}

<x-mail::button :url="$confirmUrl">
{{ __('Yes, subscribe me again') }}
</x-mail::button>

{{ __("If you didn't request this, you can ignore this email and you'll stay unsubscribed. The link expires in :v1 hours.", ['v1' => \App\Mail\NewsletterResubscribeConfirmation::LINK_TTL_HOURS]) }}

{{ __('With gratitude,') }}<br>
{{ config('app.name') }}

<x-slot:subcopy>
{{ __("Don't want to hear from us?") }} [{{ __('Unsubscribe') }}]({{ $unsubscribeUrl }}).
</x-slot:subcopy>
</x-mail::message>
