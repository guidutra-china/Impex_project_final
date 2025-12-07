<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commercial Invoice {{ $shipment->commercialInvoice->invoice_number ?? "CI-" . $shipment->shipment_number }}</title>
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
    @php
        // Get display options with defaults
        $displayOptions = $shipment->commercialInvoice->display_options ?? [];
        // Use array_key_exists to properly handle false values
        $showPaymentTerms = array_key_exists('show_payment_terms', $displayOptions) ? (bool)$displayOptions['show_payment_terms'] : true;
        $showBankInfo = array_key_exists('show_bank_info', $displayOptions) ? (bool)$displayOptions['show_bank_info'] : true;
        $showExporterDetails = array_key_exists('show_exporter_details', $displayOptions) ? (bool)$displayOptions['show_exporter_details'] : true;
        $showImporterDetails = array_key_exists('show_importer_details', $displayOptions) ? (bool)$displayOptions['show_importer_details'] : true;
        $showShippingDetails = array_key_exists('show_shipping_details', $displayOptions) ? (bool)$displayOptions['show_shipping_details'] : true;
        $showSupplierCode = array_key_exists('show_supplier_code', $displayOptions) ? (bool)$displayOptions['show_supplier_code'] : false;
        $showHsCodes = array_key_exists('show_hs_codes', $displayOptions) ? (bool)$displayOptions['show_hs_codes'] : true;
        $showCountryOfOrigin = array_key_exists('show_country_of_origin', $displayOptions) ? (bool)$displayOptions['show_country_of_origin'] : true;
        $showWeightVolume = array_key_exists('show_weight_volume', $displayOptions) ? (bool)$displayOptions['show_weight_volume'] : true;
    @endphp
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
                        <div class="document-number">{{ $shipment->commercialInvoice->invoice_number ?? "CI-" . $shipment->shipment_number }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Parties -->
        @if($showExporterDetails || $showImporterDetails)
        <div class="info-section">
            <table class="info-row" cellpadding="0" cellspacing="0">
                <tr>
                    @if($showExporterDetails)
                    <td class="info-box">
                        <div class="info-box-title">Exporter:</div>
                        <div class="info-box-content">
                            @php
                                $exporterName = $shipment->commercialInvoice->exporter_name ?? $companySettings->company_name ?? config('app.name');
                                $exporterAddress = $shipment->commercialInvoice->exporter_address ?? $companySettings->address ?? '';
                                $exporterCountry = $shipment->commercialInvoice->exporter_country ?? $companySettings->country ?? '';
                                $exporterTaxId = $shipment->commercialInvoice->exporter_tax_id ?? $companySettings->tax_id ?? '';
                            @endphp
                            <p><strong>{{ $exporterName }}</strong></p>
                            @if($exporterAddress)
                            <p>{{ $exporterAddress }}</p>
                            @endif
                            @if($exporterCountry)
                            <p>{{ $exporterCountry }}</p>
                            @endif
                            @if($exporterTaxId)
                            <p>Tax ID: {{ $exporterTaxId }}</p>
                            @endif
                        </div>
                    </td>
                    @endif
                    @if($showExporterDetails && $showImporterDetails)
                    <td style="width: 4%;"></td>
                    @endif
                    @if($showImporterDetails)
                    <td class="info-box">
                        <div class="info-box-title">Importer / Consignee:</div>
                        <div class="info-box-content">
                            @php
                                $importerName = $shipment->commercialInvoice->importer_name ?? $shipment->customer->name ?? '';
                                $importerAddress = $shipment->commercialInvoice->importer_address ?? $shipment->customer->address ?? '';
                                $importerCountry = $shipment->commercialInvoice->importer_country ?? $shipment->customer->country ?? '';
                                $importerTaxId = $shipment->commercialInvoice->importer_tax_id ?? $shipment->customer->tax_id ?? '';
                            @endphp
                            <p><strong>{{ $importerName }}</strong></p>
                            @if($importerAddress)
                            <p>{{ $importerAddress }}</p>
                            @endif
                            @if($importerCountry)
                            <p>{{ $importerCountry }}</p>
                            @endif
                            @if($importerTaxId)
                            <p>Tax ID: {{ $importerTaxId }}</p>
                            @endif
                        </div>
                    </td>
                    @endif
                </tr>
            </table>
        </div>
        @endif

        <!-- Invoice & Shipping Details -->
        <div class="info-section">
            <table class="info-row" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="info-box">
                        <div class="info-box-title">Invoice Details:</div>
                        <div class="info-box-content">
                            <p><strong>Invoice Date:</strong> {{ $shipment->actual_departure_date ?? $shipment->estimated_departure_date?->format('M d, Y') ?? date('M d, Y') }}</p>
                            @if($shipment->actual_departure_date ?? $shipment->estimated_departure_date)
                            <p><strong>Shipment Date:</strong> {{ $shipment->actual_departure_date ?? $shipment->estimated_departure_date->format('M d, Y') }}</p>
                            @endif
                            @if($shipment->commercialInvoice->due_date ?? null)
                            <p><strong>Due Date:</strong> {{ $shipment->commercialInvoice->due_date ?? null->format('M d, Y') }}</p>
                            @endif
                            @if($shipment->proformaInvoices->first()?->currency)
                            <p><strong>Currency:</strong> {{ $shipment->proformaInvoices->first()?->currency->code }}</p>
                            @endif
                            @if($shipment->incoterm)
                            <p><strong>INCOTERMS:</strong> {{ $shipment->incoterm }}@if($shipment->incoterm_location) - {{ $shipment->incoterm_location }}@endif</p>
                            @endif
                        </div>
                    </td>
                    <td style="width: 4%;"></td>
                    @if($showShippingDetails)
                    <td class="info-box">
                        <div class="info-box-title">Shipping Details:</div>
                        <div class="info-box-content">
                            @if($shipment->origin_port)
                            <p><strong>Port of Loading:</strong> {{ $shipment->origin_port }}</p>
                            @endif
                            @if($shipment->destination_port)
                            <p><strong>Port of Discharge:</strong> {{ $shipment->destination_port }}</p>
                            @endif
                            @if($shipment->destination_address)
                            <p><strong>Final Destination:</strong> {{ $shipment->destination_address }}</p>
                            @endif
                            @if($shipment->bill_of_lading_number)
                            <p><strong>B/L Number:</strong> {{ $shipment->bill_of_lading_number }}</p>
                            @endif
                            @if($shipment->containers->pluck("container_number")->join(", "))
                            <p><strong>Container(s):</strong> {{ $shipment->containers->pluck("container_number")->join(", ") }}</p>
                            @endif
                        </div>
                    </td>
                    @else
                    <td class="info-box">
                        <!-- Empty space if shipping details hidden -->
                    </td>
                    @endif
                </tr>
            </table>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 10%;">Customer Code</th>
                    @if($showSupplierCode)
                    <th style="width: 10%;">Supplier Code</th>
                    @endif
                    <th style="width: 25%;">Description</th>
                    @if($showHsCodes)
                    <th style="width: 10%;">HS Code</th>
                    @endif
                    @if($showCountryOfOrigin)
                    <th style="width: 8%;">Origin</th>
                    @endif
                    <th style="width: 6%;" class="text-right">Qty</th>
                    <th style="width: 5%;">Unit</th>
                    <th style="width: 10%;" class="text-right">Unit Price</th>
                    <th style="width: 10%;" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($shipment->getAggregatedItems() as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item['customer_code'] }}</td>
                    @if($showSupplierCode)
                    <td>{{ $item['supplier_code'] }}</td>
                    @endif
                    <td>
                        <strong>{{ $item['display_name'] }}</strong>
                    </td>
                    @if($showHsCodes)
                    <td>{{ $item['hs_code'] }}</td>
                    @endif
                    @if($showCountryOfOrigin)
                    <td>{{ $item['country_of_origin'] }}</td>
                    @endif
                    <td class="text-right">{{ number_format($item['quantity'], 0) }}</td>
                    <td>{{ $item['unit'] ?? 'pcs' }}</td>
                    <td class="text-right">
                        @php
                            $discount = ($version === 'customs' && $shipment->commercialInvoice && $shipment->commercialInvoice->customs_discount_percentage > 0) 
                                ? $shipment->commercialInvoice->customs_discount_percentage 
                                : 0;
                            $unitPrice = $item['unit_price'] * (1 - $discount / 100);
                        @endphp
                        {{ $shipment->proformaInvoices->first()?->currency->symbol }}{{ number_format($unitPrice, 2) }}
                    </td>
                    <td class="text-right">
                        @php
                            $discount = ($version === 'customs' && $shipment->commercialInvoice && $shipment->commercialInvoice->customs_discount_percentage > 0) 
                                ? $shipment->commercialInvoice->customs_discount_percentage 
                                : 0;
                            $lineTotal = ($item['quantity'] * $item['unit_price']) * (1 - $discount / 100);
                        @endphp
                        {{ $shipment->proformaInvoices->first()?->currency->symbol }}{{ number_format($lineTotal, 2) }}
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
                            @php
                                $itemsTotal = $shipment->getAggregatedItems()->sum(function($item) {
                                    return $item['quantity'] * $item['unit_price'];
                                });
                            @endphp
                            @if($version === 'customs' && $shipment->commercialInvoice->customs_discount_percentage ?? 0 > 0)
                                {{ $shipment->proformaInvoices->first()?->currency->symbol }}{{ number_format($itemsTotal * (1 - ($shipment->commercialInvoice->customs_discount_percentage ?? 0) / 100), 2) }}
                            @else
                                {{ $shipment->proformaInvoices->first()?->currency->symbol }}{{ number_format($itemsTotal, 2) }}
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
                            @php
                                $itemsTotal = $shipment->getAggregatedItems()->sum(function($item) {
                                    return $item['quantity'] * $item['unit_price'];
                                });
                            @endphp
                            @if($version === 'customs' && $shipment->commercialInvoice->customs_discount_percentage ?? 0 > 0)
                                {{ $shipment->proformaInvoices->first()?->currency->symbol }}{{ number_format($itemsTotal * (1 - ($shipment->commercialInvoice->customs_discount_percentage ?? 0) / 100), 2) }}
                            @else
                                {{ $shipment->proformaInvoices->first()?->currency->symbol }}{{ number_format($itemsTotal, 2) }}
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Payment Information -->
        @if($showPaymentTerms || $showBankInfo)
        <div class="payment-section">
            <div class="payment-title">Payment Information:</div>
            @if($shipment->proformaInvoices->first()?->paymentTerm && $showPaymentTerms)
            <div class="payment-row">
                <span class="payment-label">Payment Terms:</span>
                <span>{{ $shipment->proformaInvoices->first()?->paymentTerm->name }}</span>
            </div>
            @endif
            @if($showBankInfo)
            @php
                $bankName = $shipment->commercialInvoice->bank_name ?? $companySettings->bank_name ?? '';
                $bankAccount = $shipment->commercialInvoice->bank_account ?? $companySettings->bank_account ?? '';
                $bankSwift = $shipment->commercialInvoice->bank_swift ?? $companySettings->bank_swift ?? '';
                $bankAddress = $shipment->commercialInvoice->bank_address ?? $companySettings->bank_address ?? '';
            @endphp
            @if($bankName)
            <div class="payment-row">
                <span class="payment-label">Bank Name:</span>
                <span>{{ $bankName }}</span>
            </div>
            @endif
            @if($bankAccount)
            <div class="payment-row">
                <span class="payment-label">Account Number:</span>
                <span>{{ $bankAccount }}</span>
            </div>
            @endif
            @if($bankSwift)
            <div class="payment-row">
                <span class="payment-label">SWIFT Code:</span>
                <span>{{ $bankSwift }}</span>
            </div>
            @endif
            @if($bankAddress)
            <div class="payment-row">
                <span class="payment-label">Bank Address:</span>
                <span>{{ $bankAddress }}</span>
            </div>
            @endif
            @endif
        </div>
        @endif

        <!-- Notes -->
        @if($shipment->commercialInvoice->notes ?? "")
        <div class="notes-section">
            <div class="notes-title">Notes:</div>
            <div class="notes-content">{{ $shipment->commercialInvoice->notes ?? "" }}</div>
        </div>
        @endif

        <!-- Terms and Conditions -->
        @if($shipment->commercialInvoice->terms_and_conditions ?? "")
        <div class="notes-section">
            <div class="notes-title">Terms and Conditions:</div>
            <div class="notes-content">{{ $shipment->commercialInvoice->terms_and_conditions ?? "" }}</div>
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
