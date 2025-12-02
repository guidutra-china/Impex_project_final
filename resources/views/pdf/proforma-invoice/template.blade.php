@extends('pdf.layout')

@section('title', 'Proforma Invoice - ' . $model->proforma_number)

@section('content')
<!-- Header -->
<div class="header">
    <div class="header-row">
        <div class="header-col left">
            <div class="company-name">{{ config('app.name') }}</div>
            <div class="company-info">
                {{-- Add company address and contact info here --}}
                <p>Your Company Address</p>
                <p>City, State, ZIP</p>
                <p>Email: info@company.com</p>
                <p>Phone: +1 234 567 8900</p>
            </div>
        </div>
        <div class="header-col right">
            <div class="document-title">PROFORMA INVOICE</div>
            <div class="document-number">{{ $model->proforma_number }}</div>
            @if($model->revision_number > 1)
            <div style="color: #dc2626; font-weight: bold;">Revision {{ $model->revision_number }}</div>
            @endif
        </div>
    </div>
</div>

<!-- Customer and Invoice Info -->
<div class="info-section">
    <div class="info-row">
        <div class="info-box">
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
        </div>
        
        <div class="info-box">
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
                <p><strong>Currency:</strong> {{ $model->currency->code ?? 'USD' }}</p>
                @if($model->exchange_rate != 1.0)
                <p><strong>Exchange Rate:</strong> {{ number_format($model->exchange_rate, 6) }}</p>
                @endif
                <p><strong>Status:</strong> <span style="color: {{ $model->status === 'approved' ? '#16a34a' : '#6b7280' }};">{{ strtoupper($model->status) }}</span></p>
            </div>
        </div>
    </div>
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
    <div class="totals-section">
        <div class="totals-row">
            <div class="totals-label">Subtotal:</div>
            <div class="totals-value">{{ $model->currency->symbol ?? '$' }}{{ number_format($model->subtotal, 2) }}</div>
        </div>
        
        @if($model->tax > 0)
        <div class="totals-row">
            <div class="totals-label">Tax:</div>
            <div class="totals-value">{{ $model->currency->symbol ?? '$' }}{{ number_format($model->tax, 2) }}</div>
        </div>
        @endif
        
        <div class="totals-row grand-total">
            <div class="totals-label">TOTAL:</div>
            <div class="totals-value">{{ $model->currency->symbol ?? '$' }}{{ number_format($model->total, 2) }}</div>
        </div>
        
        @if($model->deposit_required)
        <div class="totals-row" style="margin-top: 10px; color: #dc2626;">
            <div class="totals-label">Deposit Required ({{ $model->deposit_percent }}%):</div>
            <div class="totals-value">{{ $model->currency->symbol ?? '$' }}{{ number_format($model->deposit_amount, 2) }}</div>
        </div>
        @if($model->deposit_received)
        <div class="totals-row" style="color: #16a34a;">
            <div class="totals-label">✓ Deposit Received:</div>
            <div class="totals-value">{{ $model->deposit_received_at->format('M d, Y') }}</div>
        </div>
        @endif
        @endif
    </div>
</div>

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
