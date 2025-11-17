@php
    // Inline helper functions for PDF export
    if (!function_exists('logo_base64')) {
        function logo_base64() {
            $logoPath = public_path('images/logo.svg');
            if (file_exists($logoPath)) {
                $imageData = base64_encode(file_get_contents($logoPath));
                $mimeType = 'image/svg+xml';
                return "data:{$mimeType};base64,{$imageData}";
            }
            return '';
        }
    }

    if (!function_exists('company_name')) {
        function company_name() {
            return config('app.name', 'Impex Management System');
        }
    }
@endphp
        <!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bill of Materials - {{ $product->name }} - {{ $version->version_display }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header .logo {
            max-height: 30px;
            width: auto;
        }
        .header .title-section {
            flex: 1;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header .version {
            margin-top: 5px;
            font-size: 14px;
            color: #666;
        }
        .header .spacer {
            width: 120px;
        }
        .product-info {
            margin-bottom: 20px;
        }
        .product-info table {
            width: 100%;
        }
        .product-info td {
            padding: 5px;
        }
        .product-info td:first-child {
            font-weight: bold;
            width: 150px;
        }
        .version-info {
            background-color: #f0f9ff;
            padding: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #3b82f6;
        }
        .version-info h3 {
            margin: 0 0 10px 0;
            color: #1e40af;
        }
        .version-info p {
            margin: 5px 0;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
        }
        .status-active {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-draft {
            background-color: #fef3c7;
            color: #92400e;
        }
        .status-archived {
            background-color: #e5e7eb;
            color: #374151;
        }
        .bom-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .bom-table th {
            background-color: #4B5563;
            color: white;
            padding: 10px;
            text-align: left;
            font-size: 11px;
        }
        .bom-table td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        .bom-table tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .text-right {
            text-align: right;
        }
        .cost-summary {
            margin-top: 20px;
            float: right;
            width: 300px;
        }
        .cost-summary table {
            width: 100%;
            border-collapse: collapse;
        }
        .cost-summary td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        .cost-summary td:first-child {
            font-weight: bold;
        }
        .cost-summary td:last-child {
            text-align: right;
        }
        .total-row {
            background-color: #D1FAE5;
            font-weight: bold;
            font-size: 14px;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .snapshot-notice {
            background-color: #fffbeb;
            border: 1px solid #fbbf24;
            padding: 8px;
            margin-bottom: 15px;
            border-radius: 4px;
            font-size: 11px;
        }
    </style>
</head>
<body>
<div class="header">
    <div>
        <img src="{{ logo_base64() }}" alt="{{ company_name() }}" class="logo">
    </div>
    <div class="title-section">
        <h1>Bill of Materials</h1>
        <div class="version">{{ $version->version_display }}</div>
    </div>
    <div class="spacer"></div>
</div>

<div class="version-info">
    <h3>Version Information</h3>
    <p>
        <strong>Version:</strong> {{ $version->version_display }}
        <span class="status-badge status-{{ $version->status }}">
                {{ ucfirst($version->status) }}
            </span>
    </p>
    <p><strong>Created:</strong> {{ $version->created_at->format('M d, Y H:i') }}</p>
    @if($version->created_by_user)
        <p><strong>Created By:</strong> {{ $version->created_by_user->name }}</p>
    @endif
    @if($version->change_notes)
        <p><strong>Change Notes:</strong> {{ $version->change_notes }}</p>
    @endif
</div>

<div class="snapshot-notice">
    ⓘ This is a historical snapshot. Costs shown are from {{ $version->created_at->format('M d, Y') }}.
</div>

<div class="product-info">
    <table>
        <tr>
            <td>Product Name:</td>
            <td>{{ $product->name }}</td>
        </tr>
        <tr>
            <td>SKU:</td>
            <td>{{ $product->sku }}</td>
        </tr>
        @if($product->supplier)
            <tr>
                <td>Supplier:</td>
                <td>{{ $product->supplier->name }}</td>
            </tr>
        @endif
        @if($product->customer)
            <tr>
                <td>Customer:</td>
                <td>{{ $product->customer->name }}</td>
            </tr>
        @endif
        <tr>
            <td>Export Date:</td>
            <td>{{ $exportDate }}</td>
        </tr>
    </table>
</div>

<table class="bom-table">
    <thead>
    <tr>
        <th>Code</th>
        <th>Component</th>
        <th class="text-right">Quantity</th>
        <th>UOM</th>
        <th class="text-right">Waste %</th>
        <th class="text-right">Actual Qty</th>
        <th class="text-right">Unit Cost</th>
        <th class="text-right">Total Cost</th>
    </tr>
    </thead>
    <tbody>
    @foreach($bomItems as $item)
        <tr>
            <td>{{ $item->component->code }}</td>
            <td>{{ $item->component->name }}</td>
            <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
            <td>{{ $item->unit_of_measure }}</td>
            <td class="text-right">{{ number_format($item->waste_factor, 1) }}%</td>
            <td class="text-right">{{ number_format($item->actual_quantity, 2) }}</td>
            <td class="text-right">${{ number_format($item->unit_cost_snapshot / 100, 2) }}</td>
            <td class="text-right">${{ number_format($item->total_cost_snapshot / 100, 2) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="cost-summary">
    <table>
        <tr>
            <td>BOM Material Cost:</td>
            <td>${{ number_format($version->bom_material_cost_snapshot / 100, 2) }}</td>
        </tr>
        <tr>
            <td>Direct Labor Cost:</td>
            <td>${{ number_format($version->direct_labor_cost_snapshot / 100, 2) }}</td>
        </tr>
        <tr>
            <td>Direct Overhead Cost:</td>
            <td>${{ number_format($version->direct_overhead_cost_snapshot / 100, 2) }}</td>
        </tr>
        <tr class="total-row">
            <td>Total Manufacturing Cost:</td>
            <td>${{ number_format($version->total_manufacturing_cost_snapshot / 100, 2) }}</td>
        </tr>
    </table>
</div>

<div style="clear: both;"></div>

<div class="footer">
    Generated by Impex Management System on {{ $exportDate }}<br>
    BOM Version: {{ $version->version_display }} ({{ ucfirst($version->status) }})
</div>
</body>
</html>