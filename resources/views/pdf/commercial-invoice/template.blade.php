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
            <div class="document-title">COMMERCIAL INVOICE</div>
            <div class="document-number">Invoice #{{ $model->invoice_number }}</div>
        </td>
    </tr>
</table>

{{-- Customer and Invoice Details --}}
<table class="info-row" style="width: 100%; margin-bottom: 20px;">
    <tr>
        <td class="info-box" style="width: 48%; vertical-align: top;">
            <h3>Bill To</h3>
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
            <h3>Invoice Details</h3>
            <p><strong>Invoice Date:</strong> {{ $model->invoice_date ? $model->invoice_date->format('M d, Y') : 'N/A' }}</p>
            <p><strong>Due Date:</strong> {{ $model->due_date ? $model->due_date->format('M d, Y') : 'Upon Receipt' }}</p>
            @if($model->payment_term)
                <p><strong>Payment Terms:</strong> {{ $model->payment_term->name }}</p>
            @endif
            <p><strong>Currency:</strong> {{ $model->currency->code ?? 'USD' }}</p>
            @if($model->purchase_order)
                <p><strong>PO #:</strong> {{ $model->purchase_order->po_number }}</p>
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
            <th style="width: 12%; text-align: right;">Tax</th>
            <th style="width: 12%; text-align: right;">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($model->items as $index => $item)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>
                <strong>{{ $item->product->name ?? $item->product_name }}</strong>
                @if($item->product_sku)
                    <br><small>SKU: {{ $item->product_sku }}</small>
                @endif
                @if($item->notes)
                    <br><small>{{ $item->notes }}</small>
                @endif
            </td>
            <td style="text-align: center;">{{ number_format($item->quantity) }} {{ $item->product->unit ?? 'pcs' }}</td>
            <td style="text-align: right;">{{ $model->currency->symbol ?? '$' }}{{ number_format($item->unit_price, 2) }}</td>
            <td style="text-align: right;">{{ $model->currency->symbol ?? '$' }}{{ number_format($item->tax_amount ?? 0, 2) }}</td>
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
                @if($model->discount_amount)
                <tr>
                    <td style="text-align: right; padding: 5px;"><strong>Discount:</strong></td>
                    <td style="text-align: right; padding: 5px;">-{{ $model->currency->symbol ?? '$' }}{{ number_format($model->discount_amount, 2) }}</td>
                </tr>
                @endif
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
                @if($model->amount_paid)
                <tr>
                    <td style="text-align: right; padding: 5px;"><strong>Amount Paid:</strong></td>
                    <td style="text-align: right; padding: 5px;">-{{ $model->currency->symbol ?? '$' }}{{ number_format($model->amount_paid, 2) }}</td>
                </tr>
                <tr style="border-top: 1px solid #666;">
                    <td style="text-align: right; padding: 5px;"><strong>Balance Due:</strong></td>
                    <td style="text-align: right; padding: 5px; color: #d32f2f;"><strong>{{ $model->currency->symbol ?? '$' }}{{ number_format($model->balance_due, 2) }}</strong></td>
                </tr>
                @endif
            </table>
        </td>
    </tr>
</table>

{{-- Bank Information --}}
@if($companySettings && ($companySettings->bank_name || $companySettings->bank_account_number))
<div style="background-color: #e3f2fd; padding: 15px; margin-bottom: 20px; border-radius: 5px; width: 60%;">
    <h3 style="margin-top: 0; color: #1976d2;">Bank Information for Payment</h3>
    <table style="width: 100%;">
        @if($companySettings->bank_name)
        <tr>
            <td style="padding: 3px 0; width: 40%;"><strong>Bank Name:</strong></td>
            <td style="padding: 3px 0;">{{ $companySettings->bank_name }}</td>
        </tr>
        @endif
        @if($companySettings->bank_account_number)
        <tr>
            <td style="padding: 3px 0;"><strong>Account Number:</strong></td>
            <td style="padding: 3px 0;">{{ $companySettings->bank_account_number }}</td>
        </tr>
        @endif
        @if($companySettings->bank_routing_number)
        <tr>
            <td style="padding: 3px 0;"><strong>Routing Number:</strong></td>
            <td style="padding: 3px 0;">{{ $companySettings->bank_routing_number }}</td>
        </tr>
        @endif
        @if($companySettings->bank_swift_code)
        <tr>
            <td style="padding: 3px 0;"><strong>SWIFT Code:</strong></td>
            <td style="padding: 3px 0;">{{ $companySettings->bank_swift_code }}</td>
        </tr>
        @endif
        @if($companySettings->tax_id)
        <tr>
            <td style="padding: 3px 0;"><strong>Tax ID:</strong></td>
            <td style="padding: 3px 0;">{{ $companySettings->tax_id }}</td>
        </tr>
        @endif
    </table>
</div>
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
    <p>Thank you for your business!</p>
    <p style="margin-top: 10px;"><small>Generated on {{ now()->format('M d, Y H:i:s') }}</small></p>
</div>

@endsection
