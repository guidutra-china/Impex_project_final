<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Packing List {{ $shipment->packingList->packing_list_number ?? "PL-" . $shipment->shipment_number }}</title>
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
        
        .document-title {
            font-size: 16pt;
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
            margin-bottom: 15px;
        }
        
        .info-box {
            vertical-align: top;
            width: 48%;
            padding: 8px;
            border: 1px solid #ddd;
            background: #f9fafb;
        }
        
        .info-box-title {
            font-weight: bold;
            font-size: 9pt;
            margin-bottom: 6px;
            color: #1f2937;
            border-bottom: 1px solid #ddd;
            padding-bottom: 3px;
        }
        
        .info-box-content {
            font-size: 8pt;
            line-height: 1.4;
        }
        
        .info-box-content p {
            margin: 3px 0;
        }
        
        /* Shipping details */
        .shipping-section {
            margin: 15px 0;
            padding: 10px;
            background: #eff6ff;
            border: 1px solid #2563eb;
        }
        
        .shipping-row {
            margin: 4px 0;
            font-size: 8pt;
        }
        
        .shipping-label {
            display: inline-block;
            width: 140px;
            font-weight: bold;
            color: #1e40af;
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
            border: 1px solid #fff;
        }
        
        .items-table td {
            padding: 6px 4px;
            border: 1px solid #d1d5db;
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
            padding: 10px;
            background: #f3f4f6;
            border: 1px solid #ddd;
        }
        
        .total-row {
            margin: 5px 0;
            font-size: 9pt;
        }
        
        .total-label {
            display: inline-block;
            width: 200px;
            font-weight: bold;
        }
        
        .total-value {
            font-weight: bold;
        }
        
        /* Footer */
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 7pt;
            color: #666;
        }
        
        .notes-section {
            margin-top: 20px;
            padding: 10px;
            background: #fffbeb;
            border: 1px solid #f59e0b;
        }
        
        .notes-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: #92400e;
        }
        
        .notes-content {
            font-size: 8pt;
            line-height: 1.4;
        }
    </style>
</head>
<body>
    <div class="container">
        @php
            $packingList = $shipment->packingList;
            $commercialInvoice = $shipment->commercialInvoice;
            $displayOptions = $packingList->display_options ?? [];
            
            // Use Commercial Invoice number and date
            $invoiceNumber = $commercialInvoice?->invoice_number ?? 'N/A';
            $invoiceDate = $commercialInvoice?->invoice_date ?? now();
            
            // Fallback to Company Settings for Exporter
            $exporterName = $packingList->exporter_name ?? $companySettings?->company_name ?? 'N/A';
            $exporterAddress = $packingList->exporter_address ?? $companySettings?->full_address ?? 'N/A';
            $exporterTaxId = $packingList->exporter_tax_id ?? $companySettings?->tax_id ?? 'N/A';
            $exporterCountry = $packingList->exporter_country ?? $companySettings?->country ?? 'N/A';
            
            // Fallback to Customer for Importer
            $customer = $shipment->customer;
            $importerName = $packingList->importer_name ?? $customer?->name ?? 'N/A';
            $importerAddress = $packingList->importer_address ?? ($customer ? implode(', ', array_filter([$customer->address, $customer->city, $customer->state, $customer->zip, $customer->country])) : 'N/A');
            $importerTaxId = $packingList->importer_tax_id ?? $customer?->tax_id ?? 'N/A';
            $importerCountry = $packingList->importer_country ?? $customer?->country ?? 'N/A';
            
            // Get all containers with items
            $containers = $shipment->containers()->with('items.product')->get();
        @endphp

        <!-- Header -->
        <div class="header">
            <div class="document-title">PACKING LIST</div>
            <div class="document-number">{{ $invoiceNumber }}</div>
            <div style="font-size: 8pt; margin-top: 5px;">
                <strong>Date:</strong> {{ $invoiceDate->format('d/m/Y') }}
            </div>
        </div>

        <!-- Exporter & Importer Info -->
        @if(($displayOptions['show_exporter_details'] ?? true) || ($displayOptions['show_importer_details'] ?? true))
        <div class="info-section">
            <table class="info-row">
                <tr>
                    @if($displayOptions['show_exporter_details'] ?? true)
                    <td class="info-box">
                        <div class="info-box-title">EXPORTER (SHIPPER)</div>
                        <div class="info-box-content">
                            <p><strong>{{ $exporterName }}</strong></p>
                            <p>{{ $exporterAddress }}</p>
                            @if($exporterTaxId && $exporterTaxId !== 'N/A')
                            <p><strong>Tax ID:</strong> {{ $exporterTaxId }}</p>
                            @endif
                            <p><strong>Country:</strong> {{ $exporterCountry }}</p>
                        </div>
                    </td>
                    @endif
                    
                    <td style="width: 4%;"></td>
                    
                    @if($displayOptions['show_importer_details'] ?? true)
                    <td class="info-box">
                        <div class="info-box-title">IMPORTER (CONSIGNEE)</div>
                        <div class="info-box-content">
                            <p><strong>{{ $importerName }}</strong></p>
                            <p>{{ $importerAddress }}</p>
                            @if($importerTaxId && $importerTaxId !== 'N/A')
                            <p><strong>Tax ID:</strong> {{ $importerTaxId }}</p>
                            @endif
                            <p><strong>Country:</strong> {{ $importerCountry }}</p>
                        </div>
                    </td>
                    @endif
                </tr>
            </table>
        </div>
        @endif

        <!-- Shipping Details -->
        @if($displayOptions['show_shipping_details'] ?? true)
        <div class="shipping-section">
            <div class="shipping-row">
                <span class="shipping-label">Port of Loading:</span>
                <span>{{ $packingList->port_of_loading ?? $shipment->origin_port ?? 'N/A' }}</span>
            </div>
            <div class="shipping-row">
                <span class="shipping-label">Port of Discharge:</span>
                <span>{{ $packingList->port_of_discharge ?? $shipment->destination_port ?? 'N/A' }}</span>
            </div>
            <div class="shipping-row">
                <span class="shipping-label">Final Destination:</span>
                <span>{{ $packingList->final_destination ?? $shipment->destination_address ?? 'N/A' }}</span>
            </div>
            @if($packingList->bl_number ?? $shipment->bl_number)
            <div class="shipping-row">
                <span class="shipping-label">B/L Number:</span>
                <span>{{ $packingList->bl_number ?? $shipment->bl_number }}</span>
            </div>
            @endif
            @if($packingList->container_numbers)
            <div class="shipping-row">
                <span class="shipping-label">Container Numbers:</span>
                <span>{{ $packingList->container_numbers }}</span>
            </div>
            @endif
        </div>
        @endif

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No.</th>
                    <th style="width: 25%;">Product Description</th>
                    @if($displayOptions['show_supplier_code'] ?? false)
                    <th style="width: 10%;">Supplier Code</th>
                    @endif
                    @if($displayOptions['show_customer_code'] ?? true)
                    <th style="width: 10%;">Customer Code</th>
                    @endif
                    <th style="width: 8%;" class="text-center">Qty/Carton</th>
                    <th style="width: 8%;" class="text-center">Qty</th>
                    <th style="width: 8%;" class="text-center">Cartons</th>
                    @if($displayOptions['show_weight_volume'] ?? true)
                    <th style="width: 10%;" class="text-right">N.W. (kg)</th>
                    <th style="width: 10%;" class="text-right">G.W. (kg)</th>
                    <th style="width: 10%;" class="text-right">CBM</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @php
                    $itemNumber = 1;
                    $totalQty = 0;
                    $totalCartons = 0;
                    $totalNetWeight = 0;
                    $totalGrossWeight = 0;
                    $totalVolume = 0;
                @endphp
                
                @foreach($containers as $container)
                    @foreach($container->items as $item)
                        @php
                            $product = $item->product;
                            $qty = $item->quantity ?? 0;
                            
                            // Calculate cartons: quantity / pcs_per_carton (rounded up)
                            $pcsPerCarton = $product->pcs_per_carton ?? 1;
                            $cartons = $pcsPerCarton > 0 ? ceil($qty / $pcsPerCarton) : 0;
                            
                            // Use total_volume from item (already calculated) or calculate from product
                            $volume = $item->total_volume ?? (($product->volume ?? 0) * $qty);
                            
                            // Calculate weights
                            $netWeight = ($product->net_weight ?? 0) * $qty;
                            $grossWeight = ($product->gross_weight ?? 0) * $qty;
                            
                            $totalQty += $qty;
                            $totalCartons += $cartons;
                            $totalNetWeight += $netWeight;
                            $totalGrossWeight += $grossWeight;
                            $totalVolume += $volume;
                        @endphp
                        <tr>
                            <td class="text-center">{{ $itemNumber++ }}</td>
                            <td>
                                <strong>{{ $product->name }}</strong>
                                @if($product->description)
                                <br><small style="color: #666;">{{ $product->description }}</small>
                                @endif
                            </td>
                            @if($displayOptions['show_supplier_code'] ?? false)
                            <td>{{ $product->supplier_code ?? 'N/A' }}</td>
                            @endif
                            @if($displayOptions['show_customer_code'] ?? true)
                            <td>{{ $product->customer_code ?? 'N/A' }}</td>
                            @endif
                            <td class="text-center">{{ number_format($pcsPerCarton, 0) }}</td>
                            <td class="text-center">{{ number_format($qty, 0) }}</td>
                            <td class="text-center">{{ number_format($cartons, 0) }}</td>
                            @if($displayOptions['show_weight_volume'] ?? true)
                            <td class="text-right">{{ number_format($netWeight, 2) }}</td>
                            <td class="text-right">{{ number_format($grossWeight, 2) }}</td>
                            <td class="text-right">{{ number_format($volume, 3) }}</td>
                            @endif
                        </tr>
                    @endforeach
                @endforeach
                
                <!-- Totals Row -->
                <tr style="background: #e5e7eb; font-weight: bold;">
                    @php
                        // Calculate colspan: No. (1) + Product Description (1) + optional columns
                        $colspan = 2; // No. + Product Description
                        if ($displayOptions['show_supplier_code'] ?? false) $colspan++;
                        if ($displayOptions['show_customer_code'] ?? true) $colspan++;
                    @endphp
                    <td colspan="{{ $colspan }}" class="text-right">TOTAL:</td>
                    <td class="text-center">{{ number_format($totalQty, 0) }}</td>
                    <td class="text-center">{{ number_format($totalCartons, 0) }}</td>
                    @if($displayOptions['show_weight_volume'] ?? true)
                    <td class="text-right">{{ number_format($totalNetWeight, 2) }}</td>
                    <td class="text-right">{{ number_format($totalGrossWeight, 2) }}</td>
                    <td class="text-right">{{ number_format($totalVolume, 3) }}</td>
                    @endif
                </tr>
            </tbody>
        </table>

        <!-- Summary -->
        @if($displayOptions['show_weight_volume'] ?? true)
        <div class="totals-section">
            <div class="total-row">
                <span class="total-label">Total Packages:</span>
                <span class="total-value">{{ number_format($totalCartons, 0) }} Cartons</span>
            </div>
            <div class="total-row">
                <span class="total-label">Total Net Weight:</span>
                <span class="total-value">{{ number_format($totalNetWeight, 2) }} kg</span>
            </div>
            <div class="total-row">
                <span class="total-label">Total Gross Weight:</span>
                <span class="total-value">{{ number_format($totalGrossWeight, 2) }} kg</span>
            </div>
            <div class="total-row">
                <span class="total-label">Total Volume:</span>
                <span class="total-value">{{ number_format($totalVolume, 3) }} CBM</span>
            </div>
        </div>
        <div style="clear: both;"></div>
        @endif

        <!-- Notes -->
        @if($packingList->notes)
        <div class="notes-section">
            <div class="notes-title">NOTES:</div>
            <div class="notes-content">{{ $packingList->notes }}</div>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>This packing list is generated electronically and is valid without signature.</p>
            <p>Generated on {{ now()->format('d/m/Y H:i:s') }}</p>
        </div>
    </div>
</body>
</html>
