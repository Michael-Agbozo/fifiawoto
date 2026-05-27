<x-mail::message>
# Hello {{ $application->full_name }},

We received your request for support and our team has approved you onto our beneficiary roster. A case worker will reach out within the next few days to coordinate next steps.

If anything urgent comes up in the meantime, reply to this email and we will respond as soon as we can.

With care,<br>
{{ config('app.name') }}
</x-mail::message>
