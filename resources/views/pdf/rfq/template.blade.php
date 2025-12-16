@extends('pdf.layout')

@section('content')
@php
    $companySettings = \App\Models\CompanySetting::current();
@endphp

{{-- Header --}}
<table class="header-row" style="width: 100%; margin-bottom: 30px;">
    <tr>
        <td class="header-col left" style="width: 50%; vertical-align: top;">
            @if($companySettings && $companySettings->logo_full_path)
                <img src="{{ $companySettings->logo_full_path }}" style="max-height: 60px; margin-bottom: 10px;" alt="Company Logo">
            @endif
            <div class="company-name">{{ $companySettings->company_name ?? config('app.name') }}</div>
            <div class="company-info">
                @if($companySettings)
                    <p>{{ $companySettings->address }}</p>
                    <p>{{ $companySettings->city }}, {{ $companySettings->state }} {{ $companySettings->postal_code }}</p>
                    <p>{{ $companySettings->country }}</p>
                    <p>Email: {{ $companySettings->email }}</p>
                    <p>Phone: {{ $companySettings->phone }}</p>
                    @if($companySettings->website)
                        <p>Website: {{ $companySettings->website }}</p>
                    @endif
                @endif
            </div>
        </td>
        <td class="header-col right" style="width: 50%; vertical-align: top; text-align: right;">
            <div class="document-title">REQUEST FOR QUOTATION</div>
            <div class="document-number">RFQ #{{ $model->order_number }}</div>
            <div style="margin-top: 15px;">
                <p><strong>Date:</strong> {{ ($model->order_date ?? $model->created_at)->format('M d, Y') }}</p>
                <p><strong>Valid Until:</strong> {{ $model->valid_until ? $model->valid_until->format('M d, Y') : 'N/A' }}</p>
            </div>
        </td>
    </tr>
</table>

{{-- RFQ Details --}}
<table class="info-row" style="width: 100%; margin-bottom: 20px;">
    <tr>
        <td class="info-box" style="width: 100%; vertical-align: top;">
            <h3>Quotation Requirements</h3>
            <table style="width: 100%;">
                <tr>
                    <td style="width: 25%;"><strong>Currency:</strong></td>
                    <td style="width: 25%;">{{ $model->currency->code ?? 'USD' }}</td>
                    <td style="width: 25%;"><strong>INCOTERMS:</strong></td>
                    <td style="width: 25%;">{{ $model->incoterm ?? 'TBD' }}@if($model->incoterm_location) - {{ $model->incoterm_location }}@endif</td>
                </tr>
                <tr>
                    <td><strong>Payment Terms:</strong></td>
                    <td>{{ $model->payment_term->name ?? 'To be discussed' }}</td>
                    <td><strong>Delivery Required:</strong></td>
                    <td>{{ $model->required_delivery_date ? $model->required_delivery_date->format('M d, Y') : 'ASAP' }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- Items Table --}}
<table class="items-table" style="width: 100%; margin-bottom: 20px;">
    <thead>
        <tr>
            <th style="width: 5%;">#</th>
            <th style="width: 40%;">Product Description</th>
            <th style="width: 15%; text-align: center;">Quantity</th>
            <th style="width: 20%; text-align: right;">Target Price</th>
            <th style="width: 20%; text-align: right;">Total Target</th>
        </tr>
    </thead>
    <tbody>
        @php
            $grandTotal = 0;
        @endphp
        @foreach($model->items as $index => $item)
        @php
            $itemTotal = $item->requested_unit_price * $item->quantity;
            $grandTotal += $itemTotal;
        @endphp
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>
                <strong>{{ $item->product->name }}</strong>
                @if($item->product->code)
                    <br><small>Code: {{ $item->product->code }}</small>
                @endif
                @if($item->product->sku)
                    <br><small>SKU: {{ $item->product->sku }}</small>
                @endif
                @if($item->notes)
                    <br><small style="color: #666;">Note: {{ $item->notes }}</small>
                @endif
            </td>
            <td style="text-align: center;">{{ number_format($item->quantity) }} {{ $item->product->unit ?? 'pcs' }}</td>
            <td style="text-align: right;">
                @if($item->requested_unit_price)
                    {{ $model->currency->symbol ?? '$' }}{{ number_format($item->requested_unit_price, 2) }}
                @else
                    <span style="color: #999;">TBD</span>
                @endif
            </td>
            <td style="text-align: right;">
                @if($item->requested_unit_price)
                    {{ $model->currency->symbol ?? '$' }}{{ number_format($itemTotal, 2) }}
                @else
                    <span style="color: #999;">TBD</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="4" style="text-align: right; padding-top: 15px;"><strong>Total Target Value:</strong></td>
            <td style="text-align: right; padding-top: 15px;"><strong>{{ $model->currency->symbol ?? '$' }}{{ number_format($grandTotal, 2) }}</strong></td>
        </tr>
    </tfoot>
</table>

{{-- Important Instructions --}}
<table style="width: 100%; margin-top: 20px; margin-bottom: 20px;">
    <tr>
        <td class="info-box">
            <h3>Quotation Instructions</h3>
            <p>Please provide your best quotation including:</p>
            <ul style="margin-left: 20px; margin-top: 10px;">
                <li>Unit price and total price for each item</li>
                <li>Lead time / delivery time</li>
                <li>Minimum Order Quantity (MOQ) if applicable</li>
                <li>Payment terms and conditions</li>
                <li>Validity period of your quotation</li>
                <li>Any additional costs (tooling, setup, shipping, etc.)</li>
            </ul>
            <p style="margin-top: 15px;"><strong>Please submit your quotation by: {{ $model->valid_until ? $model->valid_until->format('M d, Y') : 'the specified date' }}</strong></p>
        </td>
    </tr>
</table>

{{-- Notes --}}
@if($model->notes || $model->customer_notes)
<table style="width: 100%; margin-top: 20px;">
    <tr>
        <td>
            @if($model->notes)
            <div class="notes-section">
                <h3>Additional Notes</h3>
                <p>{{ $model->notes }}</p>
            </div>
            @endif
            
            @if($model->customer_notes)
            <div class="notes-section" style="margin-top: 15px;">
                <h3>Special Requirements</h3>
                <p>{{ $model->customer_notes }}</p>
            </div>
            @endif
        </td>
    </tr>
</table>
@endif

{{-- Footer --}}
<div class="footer">
    <p><strong>Thank you for your quotation!</strong></p>
    <p style="margin-top: 5px;">For any questions or clarifications, please contact us at {{ $companySettings->email ?? config('mail.from.address') }}</p>
    <p style="margin-top: 15px; color: #999;"><small>Document generated on {{ now()->format('M d, Y H:i:s') }}</small></p>
</div>

@endsection
