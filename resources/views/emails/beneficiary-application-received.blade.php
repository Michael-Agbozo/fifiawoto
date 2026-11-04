<x-mail::message>
# New support application

**{{ $application->full_name }}** has applied for **{{ $application->assistance_type->label() }}**.

- **Phone:** {{ $application->phone }}
@if ($application->email)
- **Email:** {{ $application->email }}
@endif
- **Location:** {{ collect([$application->region, $application->country])->filter()->implode(', ') }}
- **Documents attached:** {{ $application->documents()->count() }}

Supporting documents are kept private — open the application in the dashboard to review them.

<x-mail::button :url="route('admin.beneficiary-applications.index')">
Open in dashboard
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
