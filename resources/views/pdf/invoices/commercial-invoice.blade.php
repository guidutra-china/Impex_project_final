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
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            color: #333;
            line-height: 1.3;
        }
        .container {
            padding: 15px;
        }
        .header {
            margin-bottom: 20px;
            border-bottom: 3px solid #10b981;
            padding-bottom: 10px;
        }
        .company-info {
            float: left;
            width: 50%;
        }
        .company-name {
            font-size: 18pt;
            font-weight: bold;
            color: #10b981;
            margin-bottom: 3px;
        }
        .invoice-info {
            float: right;
            width: 45%;
            text-align: right;
        }
        .invoice-title {
            font-size: 22pt;
            font-weight: bold;
            color: #10b981;
            margin-bottom: 3px;
        }
        .invoice-number {
            font-size: 11pt;
            color: #666;
        }
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
        .parties {
            margin: 15px 0;
        }
        .party-box {
            float: left;
            width: 48%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 3px;
            background: #f9fafb;
            min-height: 120px;
        }
        .party-box.from {
            margin-right: 4%;
        }
        .party-title {
            font-weight: bold;
            font-size: 10pt;
            color: #10b981;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        .party-name {
            font-weight: bold;
            font-size: 11pt;
            margin-bottom: 3px;
        }
        .shipping-details {
            margin: 15px 0;
            padding: 10px;
            background: #ecfdf5;
            border-left: 3px solid #10b981;
        }
        .shipping-row {
            margin-bottom: 3px;
        }
        .shipping-label {
            display: inline-block;
            width: 140px;
            font-weight: bold;
            color: #666;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 8pt;
        }
        .items-table thead {
            background: #10b981;
            color: white;
        }
        .items-table th {
            padding: 6px 4px;
            text-align: left;
            font-weight: bold;
        }
        .items-table td {
            padding: 5px 4px;
            border-bottom: 1px solid #e5e7eb;
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
        .totals {
            float: right;
            width: 40%;
            margin-top: 15px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .total-row.grand-total {
            background: #10b981;
            color: white;
            padding: 10px;
            font-size: 11pt;
            font-weight: bold;
            border-radius: 3px;
            margin-top: 5px;
        }
        .total-label {
            font-weight: bold;
        }
        .customs-notice {
            background: #fef3c7;
            border-left: 3px solid #f59e0b;
            padding: 8px;
            margin: 10px 0;
            font-size: 9pt;
            font-weight: bold;
            color: #92400e;
        }
        .payment-info {
            margin-top: 15px;
            padding: 10px;
            background: #f3f4f6;
            border-radius: 3px;
        }
        .payment-row {
            margin-bottom: 3px;
        }
        .payment-label {
            display: inline-block;
            width: 120px;
            font-weight: bold;
            color: #666;
        }
        .notes {
            margin-top: 20px;
            clear: both;
        }
        .notes-title {
            font-weight: bold;
            font-size: 10pt;
            color: #10b981;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        .notes-content {
            padding: 8px;
            background: #f9fafb;
            border-left: 3px solid #10b981;
            white-space: pre-wrap;
            font-size: 8pt;
        }
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            font-size: 8pt;
            color: #666;
        }
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 100pt;
            color: rgba(0, 0, 0, 0.05);
            z-index: -1;
            font-weight: bold;
        }
    </style>
</head>
<body>
    @if($version === 'customs')
    <div class="watermark">CUSTOMS</div>
    @endif

    <div class="container">
        <!-- Header -->
        <div class="header clearfix">
            <div class="company-info">
                @if(companyLogo())
                <img src="{{ companyLogo() }}" alt="Logo" style="max-width: 120px; max-height: 50px; margin-bottom: 5px;">
                @endif
                <div class="company-name">{{ $invoice->exporter_name ?? companyName() }}</div>
                <div style="font-size: 8pt;">
                    @if($invoice->exporter_address)
                    <div>{{ $invoice->exporter_address }}</div>
                    @endif
                    @if($invoice->exporter_country)
                    <div>{{ $invoice->exporter_country }}</div>
                    @endif
                    @if($invoice->exporter_tax_id)
                    <div>Tax ID: {{ $invoice->exporter_tax_id }}</div>
                    @endif
                </div>
            </div>
            <div class="invoice-info">
                <div class="invoice-title">COMMERCIAL INVOICE</div>
                <div class="invoice-number">
                    {{ $invoice->invoice_number }}
                </div>
                @if($version === 'customs')
                <div style="color: #f59e0b; font-weight: bold; margin-top: 5px;">FOR CUSTOMS PURPOSES ONLY</div>
                @endif
            </div>
        </div>

        <!-- Parties -->
        <div class="parties clearfix">
            <div class="party-box from">
                <div class="party-title">Exporter</div>
                <div class="party-name">{{ $invoice->exporter_name ?? companyName() }}</div>
                @if($invoice->exporter_address)
                <div>{{ $invoice->exporter_address }}</div>
                @endif
                @if($invoice->exporter_country)
                <div>{{ $invoice->exporter_country }}</div>
                @endif
                @if($invoice->exporter_tax_id)
                <div>Tax ID: {{ $invoice->exporter_tax_id }}</div>
                @endif
            </div>
            <div class="party-box">
                <div class="party-title">Importer / Consignee</div>
                <div class="party-name">{{ $invoice->importer_name ?? $invoice->client->name }}</div>
                @if($invoice->importer_address)
                <div>{{ $invoice->importer_address }}</div>
                @endif
                @if($invoice->importer_country)
                <div>{{ $invoice->importer_country }}</div>
                @endif
                @if($invoice->importer_tax_id)
                <div>Tax ID: {{ $invoice->importer_tax_id }}</div>
                @endif
            </div>
        </div>

        <!-- Shipping Details -->
        <div class="shipping-details clearfix">
            <div style="float: left; width: 48%; margin-right: 4%;">
                <div class="shipping-row">
                    <span class="shipping-label">Invoice Date:</span>
                    <span>{{ $invoice->invoice_date?->format('d/m/Y') ?? date('d/m/Y') }}</span>
                </div>
                @if($invoice->shipment_date)
                <div class="shipping-row">
                    <span class="shipping-label">Shipment Date:</span>
                    <span>{{ $invoice->shipment_date->format('d/m/Y') }}</span>
                </div>
                @endif
                <div class="shipping-row">
                    <span class="shipping-label">Incoterm:</span>
                    <span>{{ $invoice->incoterm }} {{ $invoice->incoterm_location }}</span>
                </div>
                @if($invoice->currency)
                <div class="shipping-row">
                    <span class="shipping-label">Currency:</span>
                    <span>{{ $invoice->currency->code }}</span>
                </div>
                @endif
            </div>
            <div style="float: left; width: 48%;">
                @if($invoice->port_of_loading)
                <div class="shipping-row">
                    <span class="shipping-label">Port of Loading:</span>
                    <span>{{ $invoice->port_of_loading }}</span>
                </div>
                @endif
                @if($invoice->port_of_discharge)
                <div class="shipping-row">
                    <span class="shipping-label">Port of Discharge:</span>
                    <span>{{ $invoice->port_of_discharge }}</span>
                </div>
                @endif
                @if($invoice->final_destination)
                <div class="shipping-row">
                    <span class="shipping-label">Final Destination:</span>
                    <span>{{ $invoice->final_destination }}</span>
                </div>
                @endif
                @if($invoice->bl_number)
                <div class="shipping-row">
                    <span class="shipping-label">B/L Number:</span>
                    <span>{{ $invoice->bl_number }}</span>
                </div>
                @endif
            </div>
        </div>

        @if($version === 'customs' && $invoice->customs_discount_percentage > 0)
        <div class="customs-notice">
            CUSTOMS DISCOUNT APPLIED: {{ number_format($invoice->customs_discount_percentage, 2) }}% - FOR CUSTOMS DECLARATION ONLY
        </div>
        @endif

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 25%;">Description</th>
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
                            {{ $invoice->currency->symbol }}{{ number_format($item->unit_price * (1 - $invoice->customs_discount_percentage / 100) / 100, 2) }}
                        @else
                            {{ $invoice->currency->symbol }}{{ number_format($item->unit_price / 100, 2) }}
                        @endif
                    </td>
                    <td class="text-right">
                        @if($version === 'customs' && $invoice->customs_discount_percentage > 0)
                            {{ $invoice->currency->symbol }}{{ number_format($item->total * (1 - $invoice->customs_discount_percentage / 100) / 100, 2) }}
                        @else
                            {{ $invoice->currency->symbol }}{{ number_format($item->total / 100, 2) }}
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals">
            <div class="total-row">
                <span class="total-label">Subtotal:</span>
                <span>
                    @if($version === 'customs')
                        {{ $invoice->currency->symbol }}{{ number_format($invoice->getCustomsSubtotal(), 2) }}
                    @else
                        {{ $invoice->currency->symbol }}{{ number_format($invoice->getSubtotal(), 2) }}
                    @endif
                </span>
            </div>
            <div class="total-row grand-total">
                <span>TOTAL:</span>
                <span>
                    @if($version === 'customs')
                        {{ $invoice->currency->symbol }}{{ number_format($invoice->getCustomsTotal(), 2) }}
                    @else
                        {{ $invoice->currency->symbol }}{{ number_format($invoice->getTotal(), 2) }}
                    @endif
                </span>
            </div>
        </div>

        <!-- Payment Information -->
        @if($invoice->display_options['show_payment_terms'] ?? true)
        <div class="payment-info clearfix">
            @if($invoice->paymentTerm)
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
            @endif
        </div>
        @endif

        <!-- Notes -->
        @if($invoice->notes)
        <div class="notes">
            <div class="notes-title">Notes</div>
            <div class="notes-content">{{ $invoice->notes }}</div>
        </div>
        @endif

        <!-- Terms and Conditions -->
        @if($invoice->terms_and_conditions)
        <div class="notes">
            <div class="notes-title">Terms and Conditions</div>
            <div class="notes-content">{{ $invoice->terms_and_conditions }}</div>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <div>This is a computer-generated document. No signature is required.</div>
            <div>Generated on {{ now()->format('d/m/Y H:i:s') }}</div>
            @if($version === 'customs')
            <div style="color: #f59e0b; font-weight: bold; margin-top: 5px;">
                FOR CUSTOMS PURPOSES ONLY - NOT FOR PAYMENT
            </div>
            @endif
        </div>
    </div>
</body>
</html>
