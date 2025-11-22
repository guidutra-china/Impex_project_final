<x-mail::message>
# Quote Request - RFQ {{ $order->order_number }}

Dear {{ $supplier->name }},

{{ $message }}

## RFQ Details

**RFQ Number:** {{ $order->order_number }}  
**Customer Reference:** {{ $order->customer_nr_rfq ?? 'N/A' }}  
**Customer:** {{ $order->customer->name }}  
**Currency:** {{ $order->currency->code }}  
**Commission:** {{ $order->commission_percent }}%  

## Items Requested

@if($items->count() > 0)
<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
<thead>
<tr style="background-color: #f3f4f6;">
<th style="padding: 10px; text-align: left; border: 1px solid #e5e7eb;">Product</th>
<th style="padding: 10px; text-align: center; border: 1px solid #e5e7eb;">Quantity</th>
<th style="padding: 10px; text-align: left; border: 1px solid #e5e7eb;">Notes</th>
</tr>
</thead>
<tbody>
@foreach($items as $item)
<tr>
<td style="padding: 10px; border: 1px solid #e5e7eb;">
<strong>{{ $item->product->name ?? 'N/A' }}</strong><br>
<small style="color: #6b7280;">SKU: {{ $item->product->sku ?? 'N/A' }}</small>
</td>
<td style="padding: 10px; text-align: center; border: 1px solid #e5e7eb;">{{ $item->quantity }}</td>
<td style="padding: 10px; border: 1px solid #e5e7eb;">{{ $item->notes ?? '—' }}</td>
</tr>
@endforeach
</tbody>
</table>
@else
<p style="color: #6b7280; font-style: italic;">No items specified.</p>
@endif

## Next Steps

Please review the items above and provide your best quote including:
- Unit prices
- Lead time
- Minimum order quantity (if applicable)
- Payment terms
- Shipping costs

<x-mail::button :url="config('app.url')">
View RFQ in System
</x-mail::button>

If you have any questions, please don't hesitate to contact us.

Best regards,<br>
{{ config('app.name') }}
</x-mail::message>
