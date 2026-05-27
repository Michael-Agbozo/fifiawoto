<x-mail::message>
# New volunteer application

**{{ $application->full_name }}** just submitted a volunteer application.

- **Email:** {{ $application->email }}
- **Phone:** {{ $application->phone }}
- **Country:** {{ $application->country }}
- **Availability:** {{ $application->availability->label() }}

**Motivation:**

> {{ $application->motivation }}

<x-mail::button :url="route('admin.volunteers.index')">
Open in dashboard
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
