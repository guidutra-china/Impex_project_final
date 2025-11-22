<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10pt;
            color: #333;
            line-height: 1.4;
        }
        .container {
            padding: 20px;
        }
        .header {
            margin-bottom: 30px;
            border-bottom: 3px solid #10b981;
            padding-bottom: 15px;
        }
        .company-info {
            float: left;
            width: 50%;
        }
        .company-name {
            font-size: 20pt;
            font-weight: bold;
            color: #10b981;
            margin-bottom: 5px;
        }
        .invoice-info {
            float: right;
            width: 45%;
            text-align: right;
        }
        .invoice-title {
            font-size: 24pt;
            font-weight: bold;
            color: #10b981;
            margin-bottom: 5px;
        }
        .invoice-number {
            font-size: 12pt;
            color: #666;
        }
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
        .parties {
            margin: 30px 0;
        }
        .party-box {
            float: left;
            width: 48%;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #f9fafb;
        }
        .party-box.from {
            margin-right: 4%;
        }
        .party-title {
            font-weight: bold;
            font-size: 11pt;
            color: #10b981;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        .party-name {
            font-weight: bold;
            font-size: 12pt;
            margin-bottom: 5px;
        }
        .invoice-details {
            margin: 20px 0;
            padding: 15px;
            background: #f3f4f6;
            border-radius: 5px;
        }
        .detail-row {
            margin-bottom: 5px;
        }
        .detail-label {
            display: inline-block;
            width: 150px;
            font-weight: bold;
            color: #666;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .items-table thead {
            background: #10b981;
            color: white;
        }
        .items-table th {
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }
        .items-table td {
            padding: 8px 10px;
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
            margin-top: 20px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .total-row.grand-total {
            background: #10b981;
            color: white;
            padding: 12px 10px;
            font-size: 12pt;
            font-weight: bold;
            border-radius: 5px;
            margin-top: 5px;
        }
        .total-label {
            font-weight: bold;
        }
        .notes {
            margin-top: 40px;
            clear: both;
        }
        .notes-title {
            font-weight: bold;
            font-size: 11pt;
            color: #10b981;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        .notes-content {
            padding: 10px;
            background: #f9fafb;
            border-left: 3px solid #10b981;
            white-space: pre-wrap;
        }
        .footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            font-size: 9pt;
            color: #666;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-left: 10px;
        }
        .status-draft { background: #e5e7eb; color: #374151; }
        .status-sent { background: #fef3c7; color: #92400e; }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-overdue { background: #fee2e2; color: #991b1b; }
        .status-cancelled { background: #f3f4f6; color: #6b7280; text-decoration: line-through; }
        .status-superseded { background: #fef3c7; color: #92400e; }
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
        .po-references {
            margin: 15px 0;
            padding: 10px;
            background: #ecfdf5;
            border-left: 3px solid #10b981;
            font-size: 9pt;
        }
    </style>
</head>
<body>
    @if(in_array($invoice->status, ['draft', 'cancelled', 'superseded']))
    <div class="watermark">{{ strtoupper($invoice->status) }}</div>
    @endif

    <div class="container">
        <!-- Header -->
        <div class="header clearfix">
            <div class="company-info">
                <div class="company-name">Your Company Name</div>
                <div>123 Business Street</div>
                <div>City, State 12345</div>
                <div>Phone: (123) 456-7890</div>
                <div>Email: info@yourcompany.com</div>
            </div>
            <div class="invoice-info">
                <div class="invoice-title">SALES INVOICE</div>
                <div class="invoice-number">
                    {{ $invoice->invoice_number }} - Rev {{ $invoice->revision_number }}
                    <span class="status-badge status-{{ $invoice->status }}">{{ ucfirst($invoice->status) }}</span>
                </div>
            </div>
        </div>

        <!-- Parties -->
        <div class="parties clearfix">
            <div class="party-box from">
                <div class="party-title">From</div>
                <div class="party-name">Your Company Name</div>
                <div>123 Business Street</div>
                <div>City, State 12345</div>
                <div>Phone: (123) 456-7890</div>
                <div>Email: info@yourcompany.com</div>
            </div>
            <div class="party-box">
                <div class="party-title">Bill To</div>
                <div class="party-name">{{ $invoice->client->name }}</div>
                @if($invoice->client->address)
                <div>{{ $invoice->client->address }}</div>
                @endif
                @if($invoice->client->email)
                <div>Email: {{ $invoice->client->email }}</div>
                @endif
                @if($invoice->client->phone)
                <div>Phone: {{ $invoice->client->phone }}</div>
                @endif
            </div>
        </div>

        <!-- Purchase Order References -->
        @if($invoice->purchaseOrders->isNotEmpty())
        <div class="po-references">
            <strong>Reference Purchase Orders:</strong>
            {{ $invoice->purchaseOrders->pluck('po_number')->join(', ') }}
        </div>
        @endif

        <!-- Invoice Details -->
        <div class="invoice-details clearfix">
            <div class="detail-row">
                <span class="detail-label">Invoice Date:</span>
                <span>{{ $invoice->invoice_date->format('F d, Y') }}</span>
            </div>
            @if($invoice->shipment_date)
            <div class="detail-row">
                <span class="detail-label">Shipment Date:</span>
                <span>{{ $invoice->shipment_date->format('F d, Y') }}</span>
            </div>
            @endif
            <div class="detail-row">
                <span class="detail-label">Due Date:</span>
                <span>{{ $invoice->due_date->format('F d, Y') }}</span>
            </div>
            @if($invoice->paymentTerm)
            <div class="detail-row">
                <span class="detail-label">Payment Terms:</span>
                <span>{{ $invoice->paymentTerm->name }}</span>
            </div>
            @endif
            @if($invoice->quote)
            <div class="detail-row">
                <span class="detail-label">Quote Reference:</span>
                <span>{{ $invoice->quote->quote_number }}</span>
            </div>
            @endif
            <div class="detail-row">
                <span class="detail-label">Currency:</span>
                <span>{{ $invoice->currency->code }}</span>
            </div>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 30%;">Description</th>
                    <th style="width: 12%;" class="text-center">Quantity</th>
                    <th style="width: 13%;" class="text-right">Unit Price</th>
                    <th style="width: 13%;" class="text-right">Commission</th>
                    <th style="width: 12%;" class="text-right">Tax</th>
                    <th style="width: 15%;" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $item->product->name ?? 'N/A' }}</strong>
                        @if($item->description)
                        <br><small style="color: #666;">{{ $item->description }}</small>
                        @endif
                    </td>
                    <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                    <td class="text-right">{{ $invoice->currency->symbol }}{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">{{ $invoice->currency->symbol }}{{ number_format($item->commission ?? 0, 2) }}</td>
                    <td class="text-right">{{ $invoice->currency->symbol }}{{ number_format($item->tax ?? 0, 2) }}</td>
                    <td class="text-right">{{ $invoice->currency->symbol }}{{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals">
            <div class="total-row">
                <span class="total-label">Subtotal:</span>
                <span>{{ $invoice->currency->symbol }}{{ number_format($invoice->subtotal, 2) }}</span>
            </div>
            @if($invoice->commission > 0)
            <div class="total-row">
                <span class="total-label">Commission:</span>
                <span>{{ $invoice->currency->symbol }}{{ number_format($invoice->commission, 2) }}</span>
            </div>
            @endif
            @if($invoice->tax > 0)
            <div class="total-row">
                <span class="total-label">Tax:</span>
                <span>{{ $invoice->currency->symbol }}{{ number_format($invoice->tax, 2) }}</span>
            </div>
            @endif
            <div class="total-row grand-total">
                <span>TOTAL:</span>
                <span>{{ $invoice->currency->symbol }}{{ number_format($invoice->total, 2) }}</span>
            </div>
        </div>

        <!-- Notes -->
        @if($invoice->notes)
        <div class="notes">
            <div class="notes-title">Notes</div>
            <div class="notes-content">{{ $invoice->notes }}</div>
        </div>
        @endif

        <!-- Terms & Conditions -->
        @if($invoice->terms_and_conditions)
        <div class="notes" style="margin-top: 20px;">
            <div class="notes-title">Terms & Conditions</div>
            <div class="notes-content">{{ $invoice->terms_and_conditions }}</div>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <div>Thank you for your business!</div>
            <div style="margin-top: 5px;">
                Generated on {{ now()->format('F d, Y \a\t H:i') }}
                @if($invoice->revision_number > 1)
                | Revision {{ $invoice->revision_number }}
                @if($invoice->revision_reason)
                | Reason: {{ $invoice->revision_reason }}
                @endif
                @endif
            </div>
        </div>
    </div>
</body>
</html>
