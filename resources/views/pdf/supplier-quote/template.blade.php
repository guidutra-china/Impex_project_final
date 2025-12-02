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
            <div class="document-title">SUPPLIER QUOTATION</div>
            <div class="document-number">Quote #{{ $model->quote_number }}</div>
            @if($model->revision_number)
                <div style="font-size: 12px; margin-top: 5px;">Revision {{ $model->revision_number }}</div>
            @endif
        </td>
    </tr>
</table>

{{-- Supplier and Quote Details --}}
<table class="info-row" style="width: 100%; margin-bottom: 20px;">
    <tr>
        <td class="info-box" style="width: 48%; vertical-align: top;">
            <h3>Supplier Information</h3>
            @if($model->supplier)
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
            @else
                <p>No supplier assigned</p>
            @endif
        </td>
        <td style="width: 4%;"></td>
        <td class="info-box" style="width: 48%; vertical-align: top;">
            <h3>Quote Details</h3>
            <p><strong>RFQ #:</strong> {{ $model->order->order_number }}</p>
            <p><strong>Quote Date:</strong> {{ $model->quote_date ? $model->quote_date->format('M d, Y') : 'N/A' }}</p>
            <p><strong>Valid Until:</strong> {{ $model->valid_until ? $model->valid_until->format('M d, Y') : 'N/A' }}</p>
            <p><strong>Currency:</strong> {{ $model->currency->code ?? 'USD' }}</p>
            @if($model->payment_term)
                <p><strong>Payment Terms:</strong> {{ $model->payment_term->name }}</p>
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
            <th style="width: 35%;">Product</th>
            <th style="width: 12%; text-align: center;">Quantity</th>
            <th style="width: 12%; text-align: right;">Unit Price</th>
            @if($model->order->commission_type === 'separate')
                <th style="width: 12%; text-align: right;">Commission</th>
            @endif
            <th style="width: 12%; text-align: right;">Total</th>
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
            <td style="text-align: right;">
                {{ $model->currency->symbol ?? '$' }}{{ number_format($item->unit_price_after_dollars, 2) }}
                @if($item->commission_type === 'embedded' && $item->commission_percent)
                    <br><small style="color: #666;">(incl. {{ number_format($item->commission_percent, 2) }}% comm.)</small>
                @endif
            </td>
            @if($model->order->commission_type === 'separate')
                <td style="text-align: right;">{{ $model->currency->symbol ?? '$' }}{{ number_format($item->commission_amount, 2) }}</td>
            @endif
            <td style="text-align: right;">{{ $model->currency->symbol ?? '$' }}{{ number_format($item->total_price_after_dollars, 2) }}</td>
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
                @if($model->order->commission_type === 'separate')
                <tr>
                    <td style="text-align: right; padding: 5px;"><strong>Commission:</strong></td>
                    <td style="text-align: right; padding: 5px;">{{ $model->currency->symbol ?? '$' }}{{ number_format($model->commission_amount, 2) }}</td>
                </tr>
                @endif
                @if($model->tax)
                <tr>
                    <td style="text-align: right; padding: 5px;"><strong>Tax:</strong></td>
                    <td style="text-align: right; padding: 5px;">{{ $model->currency->symbol ?? '$' }}{{ number_format($model->tax, 2) }}</td>
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
    <p>Thank you for your quotation. This quote is valid until {{ $model->valid_until ? $model->valid_until->format('M d, Y') : 'the specified date' }}.</p>
    <p style="margin-top: 10px;"><small>Generated on {{ now()->format('M d, Y H:i:s') }}</small></p>
</div>

@endsection
