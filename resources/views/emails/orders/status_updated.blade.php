<x-mail::message>
# Order Status Updated

Hello {{ $order->user->name }},

The status of your order **{{ $order->id }}** has been updated to: **{{ ucfirst($order->status) }}**.

@if($order->status === 'shipped' && $order->tracking_number)
Your tracking number is: **{{ $order->tracking_number }}**
@endif

<x-mail::button :url="config('app.url') . '/orders/' . $order->id">
View Order Details
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
