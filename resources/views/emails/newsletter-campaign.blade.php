<x-mail::message>
{!! $bodyHtml !!}

<x-slot:subcopy>
You're receiving this because you subscribed to updates from {{ config('app.name') }}.
@if ($unsubscribeUrl)
[Unsubscribe]({{ $unsubscribeUrl }}).
@endif
</x-slot:subcopy>
</x-mail::message>
