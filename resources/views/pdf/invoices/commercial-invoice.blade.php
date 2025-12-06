<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commercial Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9pt;
            line-height: 1.2;
            color: #333;
        }
        
        .container {
            padding: 20px;
        }
        
        /* Header */
        .header {
            margin-bottom: 10px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        
        .header-row {
            width: 100%;
        }
        
        .company-logo {
            max-height: 60px;
            margin-bottom: 10px;
        }
        
        .document-title {
            font-size: 15pt;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 5px;
        }
        
        .document-number {
            font-size: 12pt;
            font-weight: bold;
        }
        
        /* Info boxes */
        .info-section {
            margin: 20px 0;
        }
        
        .info-row {
            width: 100%;
            margin-bottom: 20px;
        }
        
        .info-box {
            vertical-align: top;
            width: 48%;
            padding: 5px;
            border: 1px solid #ddd;
            background: #f9fafb;
        }
        
        .info-box-title {
            font-weight: bold;
            font-size: 9pt;
            margin-bottom: 6px;
            color: #1f2937;
        }
        
        .info-box-content {
            font-size: 8pt;
        }
        
        .info-box-content p {
            margin: 3px 0;
        }
        
        /* Shipping details */
        .shipping-section {
            margin: 15px 0;
            padding: 8px;
            background: #f3f4f6;
            border: 1px solid #ddd;
        }
        
        .shipping-row {
            margin: 3px 0;
            font-size: 8pt;
        }
        
        .shipping-label {
            display: inline-block;
            width: 140px;
            font-weight: bold;
            color: #666;
        }
        
        /* Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        
        .items-table thead {
            background: #1f2937;
            color: white;
        }
        
        .items-table th {
            padding: 8px 4px;
            text-align: left;
            font-weight: bold;
            font-size: 8pt;
        }
        
        .items-table td {
            padding: 6px 4px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 8pt;
        }
        
        .items-table tbody tr:nth-child(even) {
            background: #f9fafb;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        /* Totals */
        .totals-section {
            margin-top: 20px;
            float: right;
            width: 40%;
        }
        
        .total-row {
            padding: 5px 0;
            border-bottom: 1px solid #e5e7eb;
            font-size: 9pt;
        }
        
        .total-row table {
            width: 100%;
            margin: 0;
        }
        
        .total-row.grand-total {
            background: #1f2937;
            color: white;
            padding: 8px;
            font-size: 10pt;
            font-weight: bold;
            margin-top: 5px;
        }
        
        .total-label {
            font-weight: bold;
        }
        
        /* Payment info */
        .payment-section {
            margin-top: 20px;
            clear: both;
            padding: 8px;
            background: #f9fafb;
            border: 1px solid #ddd;
        }
        
        .payment-title {
            font-weight: bold;
            font-size: 9pt;
            margin-bottom: 6px;
            color: #1f2937;
        }
        
        .payment-row {
            margin: 3px 0;
            font-size: 8pt;
        }
        
        .payment-label {
            display: inline-block;
            width: 120px;
            font-weight: bold;
            color: #666;
        }
        
        /* Notes */
        .notes-section {
            margin-top: 20px;
            clear: both;
        }
        
        .notes-title {
            font-weight: bold;
            font-size: 9pt;
            margin-bottom: 5px;
            color: #1f2937;
        }
        
        .notes-content {
            padding: 8px;
            background: #f9fafb;
            border-left: 3px solid #2563eb;
            white-space: pre-wrap;
            font-size: 8pt;
        }
        
        /* Footer */
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 7pt;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <table class="header-row" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="width: 50%; vertical-align: top;">
                        @php
                            $companySettings = \App\Models\CompanySetting::current();
                        @endphp
                        @if($companySettings && $companySettings->logo_full_path)
                        <img src="{{ $companySettings->logo_full_path }}" class="company-logo" alt="Company Logo">
                        @endif
                    </td>
                    <td style="width: 50%; vertical-align: top; text-align: right;">
                        <div class="document-title">COMMERCIAL INVOICE</div>
                        <div class="document-number">{{ $invoice->invoice_number }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Parties -->
        <div class="info-section">
            <table class="info-row" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="info-box">
                        <div class="info-box-title">Exporter:</div>
                        <div class="info-box-content">
                            <p><strong>{{ $invoice->exporter_name ?? ($companySettings->company_name ?? config('app.name')) }}</strong></p>
                            @if($invoice->exporter_address)
                            <p>{{ $invoice->exporter_address }}</p>
                            @endif
                            @if($invoice->exporter_country)
                            <p>{{ $invoice->exporter_country }}</p>
                            @endif
                            @if($invoice->exporter_tax_id)
                            <p>Tax ID: {{ $invoice->exporter_tax_id }}</p>
                            @endif
                        </div>
                    </td>
                    <td style="width: 4%;"></td>
                    <td class="info-box">
                        <div class="info-box-title">Importer / Consignee:</div>
                        <div class="info-box-content">
                            <p><strong>{{ $invoice->importer_name ?? $invoice->client->name }}</strong></p>
                            @if($invoice->importer_address)
                            <p>{{ $invoice->importer_address }}</p>
                            @endif
                            @if($invoice->importer_country)
                            <p>{{ $invoice->importer_country }}</p>
                            @endif
                            @if($invoice->importer_tax_id)
                            <p>Tax ID: {{ $invoice->importer_tax_id }}</p>
                            @endif
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Invoice & Shipping Details -->
        <div class="info-section">
            <table class="info-row" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="info-box">
                        <div class="info-box-title">Invoice Details:</div>
                        <div class="info-box-content">
                            <p><strong>Invoice Date:</strong> {{ $invoice->invoice_date?->format('M d, Y') ?? date('M d, Y') }}</p>
                            @if($invoice->shipment_date)
                            <p><strong>Shipment Date:</strong> {{ $invoice->shipment_date->format('M d, Y') }}</p>
                            @endif
                            @if($invoice->due_date)
                            <p><strong>Due Date:</strong> {{ $invoice->due_date->format('M d, Y') }}</p>
                            @endif
                            @if($invoice->currency)
                            <p><strong>Currency:</strong> {{ $invoice->currency->code }}</p>
                            @endif
                            @if($invoice->incoterm)
                            <p><strong>INCOTERMS:</strong> {{ $invoice->incoterm }}@if($invoice->incoterm_location) - {{ $invoice->incoterm_location }}@endif</p>
                            @endif
                        </div>
                    </td>
                    <td style="width: 4%;"></td>
                    <td class="info-box">
                        <div class="info-box-title">Shipping Details:</div>
                        <div class="info-box-content">
                            @if($invoice->port_of_loading)
                            <p><strong>Port of Loading:</strong> {{ $invoice->port_of_loading }}</p>
                            @endif
                            @if($invoice->port_of_discharge)
                            <p><strong>Port of Discharge:</strong> {{ $invoice->port_of_discharge }}</p>
                            @endif
                            @if($invoice->final_destination)
                            <p><strong>Final Destination:</strong> {{ $invoice->final_destination }}</p>
                            @endif
                            @if($invoice->bl_number)
                            <p><strong>B/L Number:</strong> {{ $invoice->bl_number }}</p>
                            @endif
                            @if($invoice->container_numbers)
                            <p><strong>Container(s):</strong> {{ $invoice->container_numbers }}</p>
                            @endif
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 30%;">Description</th>
                    @if($invoice->display_options['show_hs_codes'] ?? true)
                    <th style="width: 10%;">HS Code</th>
                    @endif
                    @if($invoice->display_options['show_country_of_origin'] ?? true)
                    <th style="width: 10%;">Origin</th>
                    @endif
                    <th style="width: 8%;" class="text-right">Qty</th>
                    <th style="width: 5%;">Unit</th>
                    <th style="width: 12%;" class="text-right">Unit Price</th>
                    <th style="width: 12%;" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $item->product_name }}</strong>
                        @if($item->description)
                        <br><span style="font-size: 7pt; color: #666;">{{ $item->description }}</span>
                        @endif
                    </td>
                    @if($invoice->display_options['show_hs_codes'] ?? true)
                    <td>{{ $item->hs_code }}</td>
                    @endif
                    @if($invoice->display_options['show_country_of_origin'] ?? true)
                    <td>{{ $item->country_of_origin }}</td>
                    @endif
                    <td class="text-right">{{ number_format($item->quantity, 0) }}</td>
                    <td>{{ $item->unit ?? 'pcs' }}</td>
                    <td class="text-right">
                        @if($version === 'customs' && $invoice->customs_discount_percentage > 0)
                            {{ $invoice->currency->symbol }}{{ number_format(($item->unit_price / 100) * (1 - $invoice->customs_discount_percentage / 100), 2) }}
                        @else
                            {{ $invoice->currency->symbol }}{{ number_format($item->unit_price / 100, 2) }}
                        @endif
                    </td>
                    <td class="text-right">
                        @if($version === 'customs' && $invoice->customs_discount_percentage > 0)
                            {{ $invoice->currency->symbol }}{{ number_format(($item->total / 100) * (1 - $invoice->customs_discount_percentage / 100), 2) }}
                        @else
                            {{ $invoice->currency->symbol }}{{ number_format($item->total / 100, 2) }}
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals-section">
            <div class="total-row">
                <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="total-label">Subtotal:</td>
                        <td class="text-right">
                            @if($version === 'customs' && $invoice->customs_discount_percentage > 0)
                                {{ $invoice->currency->symbol }}{{ number_format($invoice->getCustomsSubtotal(), 2) }}
                            @else
                                {{ $invoice->currency->symbol }}{{ number_format($invoice->getSubtotal(), 2) }}
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
            <div class="total-row grand-total">
                <table cellpadding="0" cellspacing="0">
                    <tr>
                        <td>TOTAL:</td>
                        <td class="text-right">
                            @if($version === 'customs' && $invoice->customs_discount_percentage > 0)
                                {{ $invoice->currency->symbol }}{{ number_format($invoice->getCustomsTotal(), 2) }}
                            @else
                                {{ $invoice->currency->symbol }}{{ number_format($invoice->getTotal(), 2) }}
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Payment Information -->
        @if(($invoice->display_options['show_payment_terms'] ?? true) || ($invoice->display_options['show_bank_info'] ?? true))
        <div class="payment-section">
            <div class="payment-title">Payment Information:</div>
            @if($invoice->paymentTerm && ($invoice->display_options['show_payment_terms'] ?? true))
            <div class="payment-row">
                <span class="payment-label">Payment Terms:</span>
                <span>{{ $invoice->paymentTerm->name }}</span>
            </div>
            @endif
            @if(($invoice->display_options['show_bank_info'] ?? true) && $invoice->bank_name)
            <div class="payment-row">
                <span class="payment-label">Bank Name:</span>
                <span>{{ $invoice->bank_name }}</span>
            </div>
            @if($invoice->bank_account)
            <div class="payment-row">
                <span class="payment-label">Account Number:</span>
                <span>{{ $invoice->bank_account }}</span>
            </div>
            @endif
            @if($invoice->bank_swift)
            <div class="payment-row">
                <span class="payment-label">SWIFT Code:</span>
                <span>{{ $invoice->bank_swift }}</span>
            </div>
            @endif
            @if($invoice->bank_address)
            <div class="payment-row">
                <span class="payment-label">Bank Address:</span>
                <span>{{ $invoice->bank_address }}</span>
            </div>
            @endif
            @endif
        </div>
        @endif

        <!-- Notes -->
        @if($invoice->notes)
        <div class="notes-section">
            <div class="notes-title">Notes:</div>
            <div class="notes-content">{{ $invoice->notes }}</div>
        </div>
        @endif

        <!-- Terms and Conditions -->
        @if($invoice->terms_and_conditions)
        <div class="notes-section">
            <div class="notes-title">Terms and Conditions:</div>
            <div class="notes-content">{{ $invoice->terms_and_conditions }}</div>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>This is a computer-generated document. No signature is required.</p>
            <p>Generated on {{ now()->format('M d, Y H:i:s') }}</p>
        </div>
    </div>
</body>
</html>
