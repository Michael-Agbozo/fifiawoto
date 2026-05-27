<x-mail::message>
# Welcome, {{ $user->name }}!

You have been invited to the **{{ config('app.name') }}** admin dashboard as **{{ $user->role?->label() ?? 'team member' }}**.

Sign in with the credentials below — please change your password immediately after your first login.

- **Email:** {{ $user->email }}
- **Temporary password:** `{{ $temporaryPassword }}`

<x-mail::button :url="route('login')">
Sign in to the dashboard
</x-mail::button>

If you did not expect this invitation, please ignore this email and reply so we can investigate.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
