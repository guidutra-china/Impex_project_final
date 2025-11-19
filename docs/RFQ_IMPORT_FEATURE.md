# RFQ Excel Import Feature

## Overview

This feature allows users to import products and prices from an Excel file directly into an existing RFQ (Request for Quotation).

## Architecture

### Files Created

1. **`app/Services/RFQImportService.php`**
   - Main service responsible for parsing Excel files and importing data
   - Handles product matching/creation
   - Manages OrderItem creation/updates
   - Provides error handling and validation

2. **`app/Filament/Resources/Orders/Pages/EditOrder.php`** (Modified)
   - Added "Import from Excel" action button in header
   - Handles file upload and user feedback
   - Integrates with RFQImportService

## How It Works

### 1. User Flow

1. User navigates to an existing RFQ in edit mode
2. Clicks "Import from Excel" button in the header
3. Uploads an Excel file (.xlsx format)
4. System processes the file and imports products/prices
5. User receives success/error notifications
6. Page refreshes to show imported items

### 2. Excel File Format

The import expects the same format as the RFQ export:

```
| Product Name | Quantity | Target Price | Features |
|--------------|----------|--------------|----------|
| Product A    | 100      | 25.50        | ...      |
| Product B    | 50       | 15.00        | ...      |
```

**Required Columns:**
- Column A: Product Name (required)
- Column B: Quantity (required, numeric)
- Column C: Target Price (optional, numeric or formatted)
- Column D: Features (ignored during import)

### 3. Import Logic

**Product Matching:**
- First tries exact name match
- Falls back to case-insensitive search
- Creates new product if not found (with auto-generated SKU)

**Price Parsing:**
- Handles various formats: "25.50", "$25.50", "25,50"
- Converts to cents for storage (25.50 → 2550)
- Accepts "N/A" or empty values (stored as null)

**OrderItem Handling:**
- Updates existing items if product already in RFQ
- Creates new items for new products
- Uses database transactions for data integrity

### 4. Error Handling

**Validation:**
- Checks for "ORDER ITEMS" section in Excel
- Validates quantity is numeric and positive
- Skips empty rows and invalid data

**Error Reporting:**
- Returns detailed error messages for each failed row
- Shows up to 5 errors in notification
- Logs all errors for debugging

**Transaction Safety:**
- Uses DB transactions to ensure all-or-nothing imports
- Rolls back on critical errors
- Cleans up uploaded files after processing

## Security Considerations

1. **File Upload:**
   - Limited to .xlsx and .xls formats
   - Maximum file size: 5MB
   - Stored in temporary directory
   - Automatically deleted after processing

2. **Data Validation:**
   - All inputs sanitized and validated
   - SQL injection protection via Eloquent ORM
   - Product creation limited to basic fields

3. **Authorization:**
   - Only users with RFQ edit permissions can import
   - Inherits Filament's built-in authorization

## Usage Example

```php
// In controller or service
$importService = new RFQImportService();
$result = $importService->importFromExcel($order, $filePath);

if ($result['success']) {
    // Handle success
    echo "Imported {$result['imported']} items";
} else {
    // Handle errors
    echo "Import failed: {$result['message']}";
}
```

## Future Enhancements

1. **Batch Import:** Support importing multiple RFQs at once
2. **Product Features:** Import product features from Excel
3. **Validation Preview:** Show preview before confirming import
4. **Template Download:** Provide Excel template for users
5. **Mapping Configuration:** Allow custom column mapping
6. **Duplicate Detection:** More sophisticated duplicate handling

## Testing Checklist

- [ ] Import with valid Excel file
- [ ] Import with existing products
- [ ] Import with new products
- [ ] Import with invalid quantity
- [ ] Import with various price formats
- [ ] Import with missing "ORDER ITEMS" section
- [ ] Import with empty file
- [ ] Import with corrupted file
- [ ] Import with file size > 5MB
- [ ] Verify transaction rollback on error
- [ ] Verify file cleanup after import
- [ ] Verify notifications display correctly

## Dependencies

- `phpoffice/phpspreadsheet`: Excel file parsing
- Filament v3: UI framework
- Laravel 10+: Framework
