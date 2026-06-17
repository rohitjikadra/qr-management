<x-mail::message>
# Payment received

Hi {{ $user->name }},

We've received your payment of **₹{{ number_format($payment->amount, 2) }}** for your Pro subscription.

@if($payment->invoice_number)
**Invoice:** {{ $payment->invoice_number }}
@endif

<x-mail::button :url="url('/billing')">
View Billing
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
