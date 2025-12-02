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
            <div class="document-title">REQUEST FOR QUOTATION</div>
            <div class="document-number">RFQ #{{ $model->order_number }}</div>
        </td>
    </tr>
</table>

{{-- Customer and RFQ Details --}}
<table class="info-row" style="width: 100%; margin-bottom: 20px;">
    <tr>
        <td class="info-box" style="width: 48%; vertical-align: top;">
            <h3>Customer Information</h3>
            @if($model->customer)
                <p><strong>{{ $model->customer->name }}</strong></p>
                @if($model->customer->address)
                    <p>{{ $model->customer->address }}</p>
                @endif
                @if($model->customer->city || $model->customer->state)
                    <p>{{ $model->customer->city }}@if($model->customer->city && $model->customer->state), @endif{{ $model->customer->state }}</p>
                @endif
                @if($model->customer->email)
                    <p>Email: {{ $model->customer->email }}</p>
                @endif
                @if($model->customer->phone)
                    <p>Phone: {{ $model->customer->phone }}</p>
                @endif
            @else
                <p>No customer assigned</p>
            @endif
        </td>
        <td style="width: 4%;"></td>
        <td class="info-box" style="width: 48%; vertical-align: top;">
            <h3>RFQ Details</h3>
            <p><strong>Date:</strong> {{ $model->order_date ? $model->order_date->format('M d, Y') : 'N/A' }}</p>
            <p><strong>Valid Until:</strong> {{ $model->valid_until ? $model->valid_until->format('M d, Y') : 'N/A' }}</p>
            <p><strong>Currency:</strong> {{ $model->currency->code ?? 'USD' }}</p>
            @if($model->incoterm)
                <p><strong>INCOTERMS:</strong> {{ $model->incoterm }}@if($model->incoterm_location) - {{ $model->incoterm_location }}@endif</p>
            @endif
            @if($model->commission_type)
                <p><strong>Commission:</strong> {{ number_format($model->commission_percent_average, 2) }}% ({{ ucfirst($model->commission_type) }})</p>
            @endif
        </td>
    </tr>
</table>

{{-- Items Table --}}
<table class="items-table" style="width: 100%; margin-bottom: 20px;">
    <thead>
        <tr>
            <th style="width: 5%;">#</th>
            <th style="width: 35%;">Product</th>
            <th style="width: 15%; text-align: center;">Quantity</th>
            <th style="width: 15%; text-align: right;">Target Price</th>
            <th style="width: 15%; text-align: center;">Commission</th>
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
            <td style="text-align: right;">{{ $model->currency->symbol ?? '$' }}{{ number_format($item->target_price, 2) }}</td>
            <td style="text-align: center;">
                @if($item->commission_percent)
                    {{ number_format($item->commission_percent, 2) }}%
                    <br><small>({{ ucfirst($item->commission_type ?? $model->commission_type) }})</small>
                @else
                    -
                @endif
            </td>
            <td style="text-align: right;">{{ $model->currency->symbol ?? '$' }}{{ number_format($item->target_price * $item->quantity, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- Notes --}}
@if($model->notes || $model->terms_and_conditions)
<table style="width: 100%; margin-top: 30px;">
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
    <p>This is a Request for Quotation. Please submit your best quote by {{ $model->valid_until ? $model->valid_until->format('M d, Y') : 'the specified date' }}.</p>
    <p style="margin-top: 10px;"><small>Generated on {{ now()->format('M d, Y H:i:s') }}</small></p>
</div>

@endsection
