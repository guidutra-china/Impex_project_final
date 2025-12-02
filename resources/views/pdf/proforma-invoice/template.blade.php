@extends('pdf.layout')

@section('title', 'Proforma Invoice - ' . $model->proforma_number)

@php
    $companySettings = \App\Models\CompanySetting::current();
@endphp

@section('content')
<!-- Header -->
<div class="header">
    <table class="header-row" cellpadding="0" cellspacing="0" style="width: 100%;">
        <tr>
        <td class="header-col left" style="width: 50%; vertical-align: top;">
            @if($companySettings && $companySettings->logo_full_path)
            <img src="{{ $companySettings->logo_full_path }}" style="max-height: 60px; margin-bottom: 10px;" alt="Company Logo">
            @endif
            <div class="company-name">{{ $companySettings->company_name ?? config('app.name') }}</div>
            <div class="company-info">
                @if($companySettings)
                    @if($companySettings->address)
                    <p>{{ $companySettings->address }}</p>
                    @endif
                    @if($companySettings->city || $companySettings->state || $companySettings->zip_code)
                    <p>{{ $companySettings->city }}@if($companySettings->state), {{ $companySettings->state }}@endif @if($companySettings->zip_code) {{ $companySettings->zip_code }}@endif</p>
                    @endif
                    @if($companySettings->country)
                    <p>{{ $companySettings->country }}</p>
                    @endif
                    @if($companySettings->email)
                    <p>Email: {{ $companySettings->email }}</p>
                    @endif
                    @if($companySettings->phone)
                    <p>Phone: {{ $companySettings->phone }}</p>
                    @endif
                    @if($companySettings->website)
                    <p>{{ $companySettings->website }}</p>
                    @endif
                @else
                    <p>Company information not configured</p>
                @endif
            @endif
        </td>
        <td class="header-col right" style="width: 50%; vertical-align: top; text-align: right;">
            <div class="document-title">PROFORMA INVOICE</div>
            <div class="document-number">{{ $model->proforma_number }}</div>
            @if($model->revision_number > 1)
            <div style="color: #dc2626; font-weight: bold;">Revision {{ $model->revision_number }}</div>
            @endif
        </td>
        </tr>
    </table>
</div>

<!-- Customer and Invoice Info -->
<div class="info-section">
    <table class="info-row" cellpadding="0" cellspacing="0" style="width: 100%;">
        <tr>
        <td class="info-box" style="width: 48%; vertical-align: top; padding: 10px; border: 1px solid #ddd; background: #f9fafb;">
            <div class="info-box-title">Bill To:</div>
            <div class="info-box-content">
                <p><strong>{{ $model->customer->name }}</strong></p>
                @if($model->customer->address)
                <p>{{ $model->customer->address }}</p>
                @endif
                @if($model->customer->city || $model->customer->state || $model->customer->postal_code)
                <p>{{ $model->customer->city }}@if($model->customer->state), {{ $model->customer->state }}@endif @if($model->customer->postal_code) {{ $model->customer->postal_code }}@endif</p>
                @endif
                @if($model->customer->country)
                <p>{{ $model->customer->country->name ?? $model->customer->country }}</p>
                @endif
                @if($model->customer->email)
                <p>Email: {{ $model->customer->email }}</p>
                @endif
                @if($model->customer->phone)
                <p>Phone: {{ $model->customer->phone }}</p>
                @endif
            </div>
        </td>
        <td style="width: 4%;"></td>
        <td class="info-box" style="width: 48%; vertical-align: top; padding: 10px; border: 1px solid #ddd; background: #f9fafb;">
            <div class="info-box-title">Invoice Details:</div>
            <div class="info-box-content">
                <p><strong>Issue Date:</strong> {{ $model->issue_date->format('M d, Y') }}</p>
                <p><strong>Valid Until:</strong> {{ $model->valid_until->format('M d, Y') }}</p>
                @if($model->due_date)
                <p><strong>Due Date:</strong> {{ $model->due_date->format('M d, Y') }}</p>
                @endif
                @if($model->paymentTerm)
                <p><strong>Payment Terms:</strong> {{ $model->paymentTerm->name }}</p>
                @endif
                @if($model->incoterm)
                <p><strong>INCOTERMS:</strong> {{ $model->incoterm }}@if($model->incoterm_location) - {{ $model->incoterm_location }}@endif</p>
                @endif
                <p><strong>Currency:</strong> {{ $model->currency->code ?? 'USD' }}</p>
                @if($model->exchange_rate != 1.0)
                <p><strong>Exchange Rate:</strong> {{ number_format($model->exchange_rate, 6) }}</p>
                @endif
                <p><strong>Status:</strong> <span style="color: {{ $model->status === 'approved' ? '#16a34a' : '#6b7280' }};">{{ strtoupper($model->status) }}</span></p>
            </div>
        </td>
        </tr>
    </table>
</div>

<!-- Items Table -->
<table>
    <thead>
        <tr>
            <th style="width: 5%;">#</th>
            <th style="width: 10%;">Code</th>
            <th style="width: 35%;">Description</th>
            <th style="width: 10%;" class="text-center">Qty</th>
            <th style="width: 15%;" class="text-right">Unit Price</th>
            <th style="width: 10%;" class="text-center">Delivery</th>
            <th style="width: 15%;" class="text-right">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($model->items as $index => $item)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ $item->product->code ?? 'N/A' }}</td>
            <td>
                <strong>{{ $item->product->name ?? $item->product_name }}</strong>
                @if($item->notes)
                <br><small style="color: #666;">{{ $item->notes }}</small>
                @endif
            </td>
            <td class="text-center">{{ number_format($item->quantity) }}</td>
            <td class="text-right">{{ $model->currency->symbol ?? '$' }}{{ number_format($item->unit_price, 2) }}</td>
            <td class="text-center">{{ $item->delivery_days }} days</td>
            <td class="text-right">{{ $model->currency->symbol ?? '$' }}{{ number_format($item->total, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<!-- Totals -->
<div class="clearfix">
    <table class="totals-section" cellpadding="0" cellspacing="0" style="float: right; width: 40%;">
        <tr class="totals-row">
            <td class="totals-label" style="text-align: right; padding: 5px 10px 5px 0;">Subtotal:</td>
            <td class="totals-value" style="text-align: right; font-weight: bold; padding: 5px 0;">{{ $model->currency->symbol ?? '$' }}{{ number_format($model->subtotal, 2) }}</td>
        </tr>
        
        @if($model->tax > 0)
        <tr class="totals-row">
            <td class="totals-label" style="text-align: right; padding: 5px 10px 5px 0;">Tax:</td>
            <td class="totals-value" style="text-align: right; font-weight: bold; padding: 5px 0;">{{ $model->currency->symbol ?? '$' }}{{ number_format($model->tax, 2) }}</td>
        </tr>
        @endif
        
        <tr class="totals-row grand-total" style="border-top: 2px solid #333;">
            <td class="totals-label" style="text-align: right; padding: 8px 10px 5px 0; font-size: 12pt;">TOTAL:</td>
            <td class="totals-value" style="text-align: right; font-weight: bold; padding: 8px 0 5px 0; font-size: 12pt;">{{ $model->currency->symbol ?? '$' }}{{ number_format($model->total, 2) }}</td>
        </tr>
        
        @if($model->deposit_required)
        <tr class="totals-row" style="color: #dc2626;">
            <td class="totals-label" style="text-align: right; padding: 10px 10px 5px 0;">Deposit Required ({{ $model->deposit_percent }}%):</td>
            <td class="totals-value" style="text-align: right; font-weight: bold; padding: 10px 0 5px 0;">{{ $model->currency->symbol ?? '$' }}{{ number_format($model->deposit_amount, 2) }}</td>
        </tr>
        @if($model->deposit_received)
        <tr class="totals-row" style="color: #16a34a;">
            <td class="totals-label" style="text-align: right; padding: 5px 10px 5px 0;">✓ Deposit Received:</td>
            <td class="totals-value" style="text-align: right; font-weight: bold; padding: 5px 0;">{{ $model->deposit_received_at->format('M d, Y') }}</td>
        </tr>
        @endif
        @endif
    </table>
</div>

<!-- Bank Information -->
@if($companySettings && ($companySettings->bank_name || $companySettings->bank_account_number))
<div class="info-section" style="margin-top: 30px; clear: both;">
    <div class="info-box" style="width: 60%; background: #f0f9ff; border: 1px solid #0ea5e9; padding: 10px;">
        <div class="info-box-title" style="color: #0369a1;">Bank Information for Payment:</div>
        <div class="info-box-content">
            @if($companySettings->bank_name)
            <p><strong>Bank Name:</strong> {{ $companySettings->bank_name }}</p>
            @endif
            @if($companySettings->bank_account_number)
            <p><strong>Account Number:</strong> {{ $companySettings->bank_account_number }}</p>
            @endif
            @if($companySettings->bank_routing_number)
            <p><strong>Routing Number:</strong> {{ $companySettings->bank_routing_number }}</p>
            @endif
            @if($companySettings->bank_swift_code)
            <p><strong>SWIFT Code:</strong> {{ $companySettings->bank_swift_code }}</p>
            @endif
            @if($companySettings->tax_id)
            <p><strong>Tax ID:</strong> {{ $companySettings->tax_id }}</p>
            @endif
        </div>
    </div>
</div>
@endif

<!-- Notes -->
@if($model->customer_notes)
<div class="notes-section">
    <div class="notes-title">Notes:</div>
    <div>{{ $model->customer_notes }}</div>
</div>
@endif

<!-- Terms and Conditions -->
@if($model->terms_and_conditions)
<div class="terms-section">
    <div class="terms-title">Terms and Conditions:</div>
    <div>{!! nl2br(e($model->terms_and_conditions)) !!}</div>
</div>
@endif

<!-- Footer -->
<div class="footer">
    <p><strong>Thank you for your business!</strong></p>
    <p>This is a Proforma Invoice and not a tax invoice. Payment should be made according to the terms specified above.</p>
    <p style="margin-top: 10px;">Generated on {{ $generated_at->format('F d, Y \a\t H:i:s') }}</p>
</div>
@endsection
