<x-mail::message>
# Subscription renewal overdue

Hi {{ $user->name }},

Your Pro subscription has expired. You have **{{ 7 - $graceDay }} days** remaining in your grace period before Pro features are locked.

**Important:** Your QR codes will keep redirecting — we never break scans for your audience.

<x-mail::button :url="url('/billing')">
Renew Now
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
