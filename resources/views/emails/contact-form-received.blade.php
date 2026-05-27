<x-mail::message>
# New contact message: {{ $subjectLine }}

**From:** {{ $senderName }} &lt;{{ $senderEmail }}&gt;
@if (filled($senderPhone))
**Phone:** {{ $senderPhone }}
@endif

---

{{ $messageBody }}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
