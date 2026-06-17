<x-mail::message>
# Payment failed

Hi {{ $user->name }},

We couldn't process your subscription payment. Please update your payment method to keep your Pro features active.

Your QR codes will keep working — only management features may be limited if payment isn't resolved.

<x-mail::button :url="url('/billing')">
Update Payment
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
