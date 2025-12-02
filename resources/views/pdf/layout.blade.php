<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #333;
        }
        
        .container {
            padding: 20px;
        }
        
        /* Header */
        .header {
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        
        .header-row {
            width: 100%;
            overflow: hidden;
        }
        
        .header-col {
            float: left;
            vertical-align: top;
        }
        
        .header-col.left {
            width: 50%;
        }
        
        .header-col.right {
            width: 50%;
            text-align: right;
        }
        
        .company-name {
            font-size: 18pt;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .company-info {
            font-size: 9pt;
            color: #666;
        }
        
        .document-title {
            font-size: 20pt;
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
            overflow: hidden;
        }
        
        .info-box {
            float: left;
            width: 48%;
            padding: 10px;
            border: 1px solid #ddd;
            background: #f9fafb;
        }
        
        .info-box + .info-box {
            margin-left: 4%;
        }
        
        .info-box-title {
            font-weight: bold;
            font-size: 11pt;
            margin-bottom: 8px;
            color: #1f2937;
        }
        
        .info-box-content {
            font-size: 9pt;
        }
        
        .info-box-content p {
            margin: 3px 0;
        }
        
        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        table thead {
            background: #1f2937;
            color: white;
        }
        
        table th {
            padding: 8px;
            text-align: left;
            font-weight: bold;
            font-size: 9pt;
        }
        
        table td {
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 9pt;
        }
        
        table tbody tr:nth-child(even) {
            background: #f9fafb;
        }
        
        table tfoot {
            background: #f3f4f6;
            font-weight: bold;
        }
        
        table tfoot td {
            border-top: 2px solid #333;
            border-bottom: none;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        /* Totals */
        .totals-section {
            margin: 20px 0;
            float: right;
            width: 40%;
        }
        
        .totals-row {
            width: 100%;
            padding: 5px 0;
            overflow: hidden;
        }
        
        .totals-label {
            float: left;
            text-align: right;
            padding-right: 10px;
            width: 60%;
        }
        
        .totals-value {
            float: left;
            text-align: right;
            font-weight: bold;
            width: 40%;
        }
        
        .totals-row.grand-total {
            border-top: 2px solid #333;
            padding-top: 8px;
            margin-top: 5px;
            font-size: 12pt;
        }
        
        /* Footer */
        .footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            font-size: 8pt;
            color: #666;
        }
        
        .notes-section {
            margin: 20px 0;
            padding: 10px;
            background: #fffbeb;
            border-left: 3px solid #f59e0b;
        }
        
        .notes-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .terms-section {
            margin: 20px 0;
            font-size: 8pt;
        }
        
        .terms-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        /* Utilities */
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        @page {
            margin: 15mm;
        }
    </style>
    @yield('styles')
</head>
<body>
    <div class="container">
        @yield('content')
    </div>
</body>
</html>
