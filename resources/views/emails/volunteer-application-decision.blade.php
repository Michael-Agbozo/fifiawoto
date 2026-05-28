<x-mail::message>
@if ($approved)
# Welcome, {{ $application->full_name }}

Thank you for applying to volunteer with the **Dadaa Fifiawoto Nyamadi Foundation**. We are delighted to welcome you to the team{!! $roleLabel ? ' as a **'.e($roleLabel).'**' : '' !!}.

A coordinator will reach out shortly with onboarding details and your first assignment.
@else
# Hello {{ $application->full_name }}

Thank you for applying to volunteer with the **Dadaa Fifiawoto Nyamadi Foundation**. After reviewing your application, we are not able to bring you onto the roster at this time.

We are grateful you considered us, and we encourage you to reapply when new opportunities open.
@endif

With gratitude,<br>
{{ config('app.name') }}
</x-mail::message>
