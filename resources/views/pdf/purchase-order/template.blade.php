@extends('pdf.layout')

@section('content')
@php
    $company = \App\Models\CompanySetting::first();
@endphp

{{-- Header --}}
<table class="header-row" style="width: 100%; margin-bottom: 30px;">
    <tr>
        <td class="header-col left" style="width: 50%; vertical-align: top;">
            @if($company && $company->logo)
                <img src="{{ storage_path('app/public/' . $company->logo) }}" style="max-width: 150px; margin-bottom: 10px;">
            @endif
            <div class="company-name">{{ $company->company_name ?? config('app.name') }}</div>
            <div class="company-info">
                @if($company)
                    <p>{{ $company->address }}</p>
                    <p>{{ $company->city }}, {{ $company->state }} {{ $company->postal_code }}</p>
                    <p>{{ $company->country }}</p>
                    <p>Email: {{ $company->email }}</p>
                    <p>Phone: {{ $company->phone }}</p>
                    @if($company->website)
                        <p>Website: {{ $company->website }}</p>
                    @endif
                @endif
            </div>
        </td>
        <td class="header-col right" style="width: 50%; vertical-align: top; text-align: right;">
            <div class="document-title">PURCHASE ORDER</div>
            <div class="document-number">PO #{{ $model->po_number }}</div>
        </td>
    </tr>
</table>

{{-- Supplier and PO Details --}}
<table class="info-row" style="width: 100%; margin-bottom: 20px;">
    <tr>
        <td class="info-box" style="width: 48%; vertical-align: top;">
            <h3>Supplier (Vendor)</h3>
            <p><strong>{{ $model->supplier->name }}</strong></p>
            @if($model->supplier->address)
                <p>{{ $model->supplier->address }}</p>
            @endif
            @if($model->supplier->city || $model->supplier->state)
                <p>{{ $model->supplier->city }}@if($model->supplier->city && $model->supplier->state), @endif{{ $model->supplier->state }}</p>
            @endif
            @if($model->supplier->email)
                <p>Email: {{ $model->supplier->email }}</p>
            @endif
            @if($model->supplier->phone)
                <p>Phone: {{ $model->supplier->phone }}</p>
            @endif
        </td>
        <td style="width: 4%;"></td>
        <td class="info-box" style="width: 48%; vertical-align: top;">
            <h3>Purchase Order Details</h3>
            <p><strong>PO Date:</strong> {{ $model->po_date->format('M d, Y') }}</p>
            <p><strong>Expected Delivery:</strong> {{ $model->expected_delivery_date ? $model->expected_delivery_date->format('M d, Y') : 'TBD' }}</p>
            <p><strong>Currency:</strong> {{ $model->currency->code ?? 'USD' }}</p>
            @if($model->payment_term)
                <p><strong>Payment Terms:</strong> {{ $model->payment_term->name }}</p>
            @endif
            @if($model->incoterm)
                <p><strong>INCOTERMS:</strong> {{ $model->incoterm }}@if($model->incoterm_location) - {{ $model->incoterm_location }}@endif</p>
            @endif
            <p><strong>Status:</strong> <span style="text-transform: capitalize;">{{ $model->status }}</span></p>
        </td>
    </tr>
</table>

{{-- Items Table --}}
<table class="items-table" style="width: 100%; margin-bottom: 20px;">
    <thead>
        <tr>
            <th style="width: 5%;">#</th>
            <th style="width: 40%;">Product</th>
            <th style="width: 15%; text-align: center;">Quantity</th>
            <th style="width: 15%; text-align: right;">Unit Price</th>
            <th style="width: 15%; text-align: right;">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($model->items as $index => $item)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>
                <strong>{{ $item->product->name }}</strong>
                @if($item->product->sku)
                    <br><small>SKU: {{ $item->product->sku }}</small>
                @endif
                @if($item->notes)
                    <br><small>{{ $item->notes }}</small>
                @endif
            </td>
            <td style="text-align: center;">{{ number_format($item->quantity) }} {{ $item->product->unit ?? 'pcs' }}</td>
            <td style="text-align: right;">{{ $model->currency->symbol ?? '$' }}{{ number_format($item->unit_price, 2) }}</td>
            <td style="text-align: right;">{{ $model->currency->symbol ?? '$' }}{{ number_format($item->total_price, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- Totals --}}
<table class="totals-row" style="width: 100%; margin-bottom: 20px;">
    <tr>
        <td style="width: 60%;"></td>
        <td style="width: 40%;">
            <table style="width: 100%;">
                <tr>
                    <td style="text-align: right; padding: 5px;"><strong>Subtotal:</strong></td>
                    <td style="text-align: right; padding: 5px; width: 40%;">{{ $model->currency->symbol ?? '$' }}{{ number_format($model->subtotal, 2) }}</td>
                </tr>
                @if($model->tax)
                <tr>
                    <td style="text-align: right; padding: 5px;"><strong>Tax:</strong></td>
                    <td style="text-align: right; padding: 5px;">{{ $model->currency->symbol ?? '$' }}{{ number_format($model->tax, 2) }}</td>
                </tr>
                @endif
                @if($model->shipping_cost)
                <tr>
                    <td style="text-align: right; padding: 5px;"><strong>Shipping:</strong></td>
                    <td style="text-align: right; padding: 5px;">{{ $model->currency->symbol ?? '$' }}{{ number_format($model->shipping_cost, 2) }}</td>
                </tr>
                @endif
                <tr style="border-top: 2px solid #333;">
                    <td style="text-align: right; padding: 10px 5px;"><strong>TOTAL:</strong></td>
                    <td style="text-align: right; padding: 10px 5px; font-size: 16px;"><strong>{{ $model->currency->symbol ?? '$' }}{{ number_format($model->total, 2) }}</strong></td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- Shipping Information --}}
@if($model->shipping_address)
<table style="width: 100%; margin-bottom: 20px;">
    <tr>
        <td>
            <div class="info-box">
                <h3>Shipping Address</h3>
                <p>{{ $model->shipping_address }}</p>
            </div>
        </td>
    </tr>
</table>
@endif

{{-- Notes --}}
@if($model->notes || $model->terms_and_conditions)
<table style="width: 100%; margin-top: 20px;">
    <tr>
        <td>
            @if($model->notes)
            <div class="notes-section">
                <h3>Notes</h3>
                <p>{{ $model->notes }}</p>
            </div>
            @endif
            
            @if($model->terms_and_conditions)
            <div class="notes-section" style="margin-top: 15px;">
                <h3>Terms and Conditions</h3>
                <p>{{ $model->terms_and_conditions }}</p>
            </div>
            @endif
        </td>
    </tr>
</table>
@endif

{{-- Footer --}}
<div class="footer">
    <p>Please confirm receipt of this Purchase Order and provide estimated delivery date.</p>
    <p style="margin-top: 10px;"><small>Generated on {{ now()->format('M d, Y H:i:s') }}</small></p>
</div>

@endsection
