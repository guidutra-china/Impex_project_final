# AI-Powered Document Import System

## Overview

The Document Import system uses **DeepSeek AI** to automatically analyze Excel and PDF files, detect document structure, extract supplier information, and intelligently map columns to product fields for automated import.

## Architecture

### Components

1. **Models**
   - `ImportHistory` - Tracks import records with AI analysis results

2. **Services**
   - `DeepSeekService` - API integration with DeepSeek AI
   - `AIFileAnalyzerService` - Orchestrates file parsing and AI analysis
   - `ExcelParser` - Parses Excel files (.xlsx, .xls)
   - `PDFParser` - Parses PDF files
   - `DynamicProductImporter` - Imports products based on AI mapping

3. **Jobs (Queue-based)**
   - `AnalyzeImportFileJob` - Analyzes uploaded files with AI
   - `ProcessProductImportJob` - Imports products to database

4. **Filament Resources**
   - `DocumentImportResource` - Main resource for managing imports
   - `CreateDocumentImport` - Upload and create import records
   - `ViewDocumentImport` - View analysis and trigger import
   - `ListDocumentImports` - List all imports with status

## Workflow

### 1. File Upload (Status: pending)

User uploads Excel or PDF file through the DocumentImport form:
- File is stored in `storage/app/private/imports/`
- File metadata extracted (name, size, type)
- Import record created with status `pending`
- User redirected to view page

### 2. AI Analysis (Status: analyzing → ready)

Automatically triggered after upload:
- `AnalyzeImportFileJob` dispatched to queue
- File parsed by `ExcelParser` or `PDFParser`
- Structured data sent to DeepSeek AI for analysis
- AI detects:
  - Document type (Proforma Invoice, Catalog, Price List, etc.)
  - Supplier information (name, email, country, phone)
  - Column mapping with confidence scores
  - Product count
  - Currency
  - Data start row
  - Suggested tags

**AI Analysis Result Example:**
```json
{
  "document_type": "Product Catalog",
  "confidence": 0.95,
  "supplier": {
    "name": "ABC Electronics Co.",
    "email": "sales@abc.com",
    "country": "CN"
  },
  "products_count": 70,
  "currency": "USD",
  "start_row": 7,
  "column_mapping": {
    "A": {
      "field": "sku",
      "confidence": 0.95,
      "label": "Model NO"
    },
    "B": {
      "field": "name",
      "confidence": 0.98,
      "label": "PRODUCT"
    },
    "C": {
      "field": "price",
      "confidence": 0.92,
      "label": "Unit Price"
    }
  },
  "suggested_tags": ["Electronics", "Fitness Equipment"]
}
```

### 3. Review Analysis (Status: ready)

User can:
- **View AI Analysis** - Modal showing all detected information
- **Start Import** - Trigger product import
- **Re-analyze** - Re-run AI analysis if needed

### 4. Product Import (Status: importing → completed)

When user clicks "Start Import":
- `ProcessProductImportJob` dispatched to queue
- File re-parsed to get all data rows
- For each row:
  - Map columns to product fields using AI mapping
  - Handle special fields (price conversion, dimensions, weights)
  - Extract embedded images if available
  - Create or update product
  - Link to supplier
  - Attach tags
- Track statistics:
  - Success count (new products)
  - Updated count (existing products updated)
  - Skipped count (errors)
  - Error details
  - Warning messages

### 5. Results (Status: completed/failed)

Import results displayed in view page:
- Total rows processed
- Success/updated/skipped counts
- Error and warning lists
- Success rate percentage
- Timestamps (analyzed_at, imported_at)

## Supported File Types

### Excel Files (.xlsx, .xls)
- Reads all sheets
- Extracts embedded images
- Handles merged cells
- Detects headers automatically
- Supports various price formats

### PDF Files (.pdf)
- Extracts text content
- Detects tables
- Extracts product information from unstructured text
- (Note: Image extraction from PDF not yet implemented)

## Field Mapping

AI can detect and map these product fields:

| Field | Description | Type |
|-------|-------------|------|
| `sku` | Product code/SKU | String |
| `name` | Product name | String |
| `description` | Product description | Text |
| `price` | Unit price (converted to cents) | Integer |
| `photo` | Product photo column | File |
| `gross_weight` | Weight in kg | Decimal |
| `net_weight` | Net weight in kg | Decimal |
| `dimensions` | Product dimensions | String |
| `moq` | Minimum order quantity | Integer |
| `lead_time_days` | Lead time in days | Integer |
| `hs_code` | HS code | String |
| `brand` | Brand name | String |
| `model_number` | Model number | String |
| `certifications` | Certifications | String |
| `carton_length` | Carton length in cm | Decimal |
| `carton_width` | Carton width in cm | Decimal |
| `carton_height` | Carton height in cm | Decimal |
| `carton_weight` | Carton weight in kg | Decimal |
| `carton_cbm` | Carton volume in CBM | Decimal |
| `pcs_per_carton` | Pieces per carton | Integer |

## Configuration

### Environment Variables

```env
# DeepSeek API Key (required)
DEEP_SEEK=your-deepseek-api-key-here

# Alternative key name
DEEP_SEEK_2=your-alternative-key

# Or configure in config/services.php
DEEPSEEK_API_KEY=your-key
```

### Queue Configuration

The system uses Laravel queues for background processing:

```bash
# Start queue worker
php artisan queue:work

# Or use Supervisor in production
```

### Storage Configuration

Files are stored in the `private` disk:

```php
// config/filesystems.php
'private' => [
    'driver' => 'local',
    'root' => storage_path('app/private'),
    'visibility' => 'private',
],
```

## Usage

### 1. Create Import

1. Navigate to **Documents → Document Imports**
2. Click **New Document Import**
3. Select **Import Type** (currently only "Products" supported)
4. Upload Excel or PDF file (max 20MB)
5. Click **Create**

### 2. Wait for Analysis

- System automatically analyzes the file
- Status changes: `pending` → `analyzing` → `ready`
- Refresh page to see updated status
- Analysis typically takes 10-60 seconds

### 3. Review AI Analysis

1. Click **View AI Analysis** button
2. Review detected information:
   - Document type and confidence
   - Supplier information
   - Column mapping with confidence scores
   - Suggested tags
   - AI notes and recommendations

### 4. Start Import

1. Click **Start Import** button
2. Confirm the action
3. Wait for import to complete
4. Status changes: `ready` → `importing` → `completed`
5. Review import results

### 5. Handle Errors

If import fails:
- Check error messages in the view page
- Review warnings for data quality issues
- Click **Re-analyze** to retry AI analysis
- Fix source file and upload again if needed

## Error Handling

### Analysis Errors

**Common issues:**
- Invalid file format
- Corrupted file
- DeepSeek API error
- File too large

**Solutions:**
- Check file format (.xlsx, .xls, .pdf)
- Verify DeepSeek API key
- Increase PHP upload limits
- Check Laravel logs

### Import Errors

**Common issues:**
- Missing required fields (product name)
- Invalid data types
- Database constraints
- Duplicate SKUs

**Solutions:**
- Review AI mapping accuracy
- Check source data quality
- Manually adjust mapping if needed
- Review error details in import results

## Performance

### Optimization Tips

1. **Queue Workers**: Run multiple queue workers for parallel processing
2. **File Size**: Keep files under 10MB for optimal performance
3. **Batch Size**: Large files (1000+ products) may take 5-10 minutes
4. **Memory**: Ensure PHP memory_limit >= 256M
5. **Timeout**: Set max_execution_time >= 300 seconds

### Expected Processing Times

| Products | Analysis Time | Import Time |
|----------|---------------|-------------|
| 1-50 | 10-20 sec | 30-60 sec |
| 51-200 | 20-40 sec | 1-3 min |
| 201-500 | 40-60 sec | 3-7 min |
| 501+ | 60-120 sec | 7-15 min |

## Security

1. **File Upload**
   - Validated file types (.xlsx, .xls, .pdf)
   - Maximum file size: 20MB
   - Stored in private disk (not publicly accessible)
   - Automatic cleanup after processing

2. **Data Validation**
   - All inputs sanitized
   - SQL injection protection via Eloquent ORM
   - User authentication required
   - Role-based access control via Shield

3. **API Security**
   - DeepSeek API key stored in environment variables
   - HTTPS communication
   - Request/response logging

## Troubleshooting

### Issue: Analysis stuck in "analyzing" status

**Cause**: Queue worker not running or job failed

**Solution**:
```bash
# Check queue status
php artisan queue:work --once

# Check failed jobs
php artisan queue:failed

# Retry failed job
php artisan queue:retry <job-id>

# Check logs
tail -f storage/logs/laravel.log
```

### Issue: Import creates duplicate products

**Cause**: SKU matching not working

**Solution**:
- Ensure SKU column is correctly mapped
- Check for empty SKU values
- Review product name matching logic
- Consider adding unique constraints

### Issue: Images not importing

**Cause**: Image extraction not implemented for PDF or image column not detected

**Solution**:
- Use Excel files with embedded images
- Ensure images are in correct cells
- Check `has_images` flag in AI analysis
- Review image extraction logs

### Issue: DeepSeek API errors

**Cause**: Invalid API key, rate limits, or API downtime

**Solution**:
```bash
# Verify API key
php artisan tinker
>>> config('services.deepseek.api_key')

# Test API connection
>>> $service = new \App\Services\AI\DeepSeekService();
>>> $service->chat([['role' => 'user', 'content' => 'test']]);
```

## Future Enhancements

1. **Manual Mapping Editor**: Allow users to adjust AI-suggested mapping
2. **Import Preview**: Show preview of products before importing
3. **Scheduled Imports**: Automatically import from FTP/email
4. **Multi-type Import**: Support suppliers, clients, quotes
5. **Template Library**: Pre-configured mappings for common formats
6. **Duplicate Detection**: Advanced duplicate handling options
7. **Bulk Operations**: Import multiple files at once
8. **Export Templates**: Generate Excel templates for users
9. **Validation Rules**: Custom validation rules per import type
10. **Webhook Notifications**: Notify external systems on completion

## API Reference

### DeepSeekService

```php
$service = new DeepSeekService();

// Analyze file structure
$analysis = $service->analyzeFileStructure($parsedData);

// Extract products from text (PDF)
$products = $service->extractProductsFromText($text);

// Custom chat completion
$response = $service->chat($messages, $options);
```

### AIFileAnalyzerService

```php
$analyzer = new AIFileAnalyzerService();

// Analyze file
$analysis = $analyzer->analyzeFile($filePath);

// Get suggested mapping
$mapping = $analyzer->getSuggestedMapping($analysis);

// Get supplier info
$supplier = $analyzer->getSupplierInfo($analysis);
```

### DynamicProductImporter

```php
$importer = new DynamicProductImporter();

// Import products
$result = $importer->import($analysis, $mapping, $options);

// Result structure
[
    'success' => true,
    'message' => '50 products created, 10 updated',
    'stats' => [
        'success' => 50,
        'updated' => 10,
        'skipped' => 2,
        'errors' => 0,
        'warnings' => 3,
    ],
    'errors' => [],
    'warnings' => ['Created new supplier: ABC Co.'],
]
```

## Testing

### Manual Testing Checklist

- [ ] Upload valid Excel file
- [ ] Upload valid PDF file
- [ ] Upload invalid file format
- [ ] Upload file > 20MB
- [ ] Wait for AI analysis completion
- [ ] View AI analysis results
- [ ] Start product import
- [ ] Verify products created correctly
- [ ] Check supplier auto-creation
- [ ] Verify tag attachment
- [ ] Test with existing products (update)
- [ ] Test with duplicate SKUs
- [ ] Test error handling
- [ ] Test re-analyze function
- [ ] Verify queue processing
- [ ] Check logs for errors

### Test Files

Create test files with:
- Various document types (catalog, proforma, price list)
- Different column layouts
- Embedded images
- Multiple sheets
- Various price formats
- Special characters
- Empty rows
- Invalid data

## Support

For issues or questions:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Review queue failed jobs: `php artisan queue:failed`
3. Check DeepSeek API status
4. Review this documentation
5. Contact development team

## Changelog

### Version 1.0.0 (2025-12-10)
- Initial implementation
- DeepSeek AI integration
- Excel and PDF parsing
- Automatic analysis and import
- Queue-based processing
- Action buttons and status tracking
- AI analysis modal view
- Comprehensive error handling
