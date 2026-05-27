<x-mail::message>
# Hello {{ $recipientName }},

{!! nl2br(e($bodyText)) !!}

---

> {{ \Illuminate\Support\Str::limit($originalMessage, 400) }}

With gratitude,<br>
{{ config('app.name') }}
</x-mail::message>
