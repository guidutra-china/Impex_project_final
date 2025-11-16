<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bill of Materials - {{ $product->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
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
    </style>
</head>
<body>
    <div class="header">
        <h1>Bill of Materials</h1>
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
                <td class="text-right">${{ number_format($item->unit_cost / 100, 2) }}</td>
                <td class="text-right">${{ number_format($item->total_cost / 100, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="cost-summary">
        <table>
            <tr>
                <td>BOM Material Cost:</td>
                <td>${{ number_format($product->bom_material_cost / 100, 2) }}</td>
            </tr>
            <tr>
                <td>Direct Labor Cost:</td>
                <td>${{ number_format($product->direct_labor_cost / 100, 2) }}</td>
            </tr>
            <tr>
                <td>Direct Overhead Cost:</td>
                <td>${{ number_format($product->direct_overhead_cost / 100, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td>Total Manufacturing Cost:</td>
                <td>${{ number_format($product->total_manufacturing_cost / 100, 2) }}</td>
            </tr>
        </table>
    </div>

    <div style="clear: both;"></div>

    <div class="footer">
        Generated by Impex Management System on {{ $exportDate }}
    </div>
</body>
</html>
