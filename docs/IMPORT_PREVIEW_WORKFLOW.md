# Import Preview & Review Workflow

## Overview

The Import Preview & Review system provides a comprehensive workflow for importing products from Excel/PDF files with AI-powered analysis, customizable field mapping, duplicate detection, and manual approval before final import.

## Workflow Stages

### 1. Upload & Initial Analysis

**Status:** `pending` → `analyzing` → `ready`

1. User uploads Excel/PDF file via DocumentImportResource
2. System automatically dispatches `AnalyzeImportFileJob`
3. AI analyzes the file to detect:
   - Document type (quotation, product list, etc.)
   - Supplier information (name, email)
   - Column headers and data structure
   - Suggested field mapping
   - Total number of products
4. Status changes to `ready` when analysis completes

**User Actions:**
- View AI Analysis results
- Re-analyze if needed

---

### 2. Configure Field Mapping

**Status:** `ready`

**Page:** Configure Mapping (`/configure-mapping`)

User can customize how Excel columns map to product fields:

**Available Target Fields:**

- **Basic:** SKU, Supplier Code, Model Number, Name, Description, Brand
- **Pricing:** Price, Cost, MSRP
- **Physical:** Gross Weight, Net Weight, Product Dimensions (L×W×H)
- **Packaging:** Carton Dimensions, CBM, Pcs per Carton
- **Logistics:** MOQ, Lead Time, HS Code
- **Additional:** Certifications, Features, Photo URL

**Features:**
- AI-suggested mapping with confidence scores
- Dropdown selection for each column
- Option to skip columns
- Reset to AI suggestions
- Save mapping for later

**User Actions:**
- Adjust field mapping
- Click "Generate Preview" to proceed

---

### 3. Generate Preview

**Status:** `ready` → `generating_preview` → `preview_ready`

1. System dispatches `GenerateImportPreviewJob`
2. Applies field mapping to all rows
3. Creates `ImportPreviewItem` records for each product
4. Runs duplicate detection:
   - **Exact match:** SKU or Supplier Code
   - **Similar match:** Name similarity >90%
5. Validates each item (required fields, data types)
6. Extracts photos from Excel images
7. Status changes to `preview_ready`

**Duplicate Detection Results:**
- 🟢 **New** - No duplicate found
- 🔴 **Duplicate** - Exact SKU/Code match
- 🟡 **Similar** - Name similarity detected

---

### 4. Review & Edit Preview

**Status:** `preview_ready`

**Page:** Review Preview (`/review-preview`)

User reviews all products before importing:

**Table Columns:**
- ☑️ Import checkbox
- Row number
- Status badge (New/Duplicate/Similar)
- Photo thumbnail
- SKU
- Product Name
- Price
- Brand
- MOQ
- Photo Status
- Action (Import/Skip/Update/Merge)
- Validation status

**Filters:**
- Status (New/Duplicate/Similar)
- Action
- Photo Status
- Selected for Import
- Has Errors

**Individual Actions:**
- **View** - See full details and duplicate comparison
- **Edit** - Modify product data, upload photo manually
- **Delete** - Remove from preview

**Bulk Actions:**
- Select All
- Deselect All
- Skip Duplicates
- Set Action: Import/Skip
- Remove Selected

**Statistics Dashboard:**
- Total Items
- Selected Items
- Duplicates Found
- Items with Errors
- Photos Extracted/Missing

**Duplicate Comparison:**

For duplicate/similar items, side-by-side comparison shows:
- Import data vs Existing data
- Highlighted differences
- Similarity score
- Last update date

**Action Options:**
- **Import** - Create as new product (even if duplicate)
- **Skip** - Don't import this item
- **Update** - Replace existing product data
- **Merge** - Fill empty fields in existing product

---

### 5. Final Import

**Status:** `preview_ready` → `importing` → `completed`

1. User clicks "Import Selected Items"
2. System dispatches `ImportSelectedItemsJob`
3. Processes only items where `selected = true`
4. For each item:
   - Creates/updates product based on action
   - Links to supplier
   - Handles photo (extracted/uploaded/URL)
   - Creates feature tags
   - Logs errors/warnings
5. Updates statistics:
   - Success count (created)
   - Updated count
   - Skipped count
   - Error count
6. Status changes to `completed`

**Photo Handling Priority:**
1. Manual upload (highest priority)
2. Extracted from Excel
3. Downloaded from URL
4. None (warning logged)

---

## Database Schema

### `import_preview_items` Table

**Product Data:**
- Basic: sku, supplier_code, model_number, name, description, brand
- Pricing: price, cost, msrp (in cents)
- Physical: weights, dimensions
- Packaging: carton info, CBM, pcs per carton
- Logistics: moq, lead_time_days, hs_code
- Additional: certifications, features

**Photo Management:**
- `photo_path` - Final path after import
- `photo_temp_path` - Temporary extracted photo
- `photo_url` - External URL
- `photo_status` - none/extracted/missing/uploaded/error
- `photo_extracted` - Boolean flag
- `photo_error` - Error message

**Duplicate Detection:**
- `duplicate_status` - new/duplicate/similar
- `existing_product_id` - Link to existing product
- `similarity_score` - 0-100%
- `differences` - JSON of field differences

**Import Decision:**
- `action` - import/skip/update/merge
- `selected` - Boolean (user selection)
- `notes` - User notes

**Validation:**
- `validation_errors` - JSON array
- `validation_warnings` - JSON array
- `has_errors` - Boolean flag

**Metadata:**
- `row_number` - Original row in file
- `raw_data` - JSON of original data

---

## Services

### FieldMappingService

**Purpose:** Handle field mapping and value transformation

**Methods:**
- `getAvailableFields()` - All mappable fields grouped by category
- `getFieldOptions()` - Flat list for dropdowns
- `applyMapping()` - Apply mapping to raw data
- `transformValue()` - Convert values by type (money, decimal, etc.)
- `parseFeatures()` - Extract features from text

**Value Transformations:**
- **Money:** Removes symbols, converts to cents
- **Decimal:** Removes non-numeric, converts to float
- **Integer:** Extracts numbers only
- **URL:** Validates and adds protocol if needed

---

## Jobs

### 1. AnalyzeImportFileJob

**Trigger:** After file upload  
**Duration:** ~30-60 seconds  
**Status:** `pending` → `analyzing` → `ready`

**Tasks:**
- Parse Excel/PDF file
- Send to DeepSeek AI for analysis
- Extract supplier info
- Detect document type
- Suggest column mapping
- Count total products

### 2. GenerateImportPreviewJob

**Trigger:** User clicks "Generate Preview"  
**Duration:** ~10-30 seconds per 100 rows  
**Status:** `ready` → `generating_preview` → `preview_ready`

**Tasks:**
- Apply field mapping to all rows
- Create ImportPreviewItem records
- Run duplicate detection
- Validate data
- Extract photos
- Calculate statistics

### 3. ImportSelectedItemsJob

**Trigger:** User clicks "Import Selected Items"  
**Duration:** ~5-15 seconds per 100 items  
**Status:** `preview_ready` → `importing` → `completed`

**Tasks:**
- Process selected items only
- Create/update products
- Link suppliers
- Handle photos (copy/download)
- Create feature tags
- Log results

---

## User Interface

### Configure Mapping Page

```
┌─────────────────────────────────────────────────┐
│ Column Mapping Configuration                    │
├─────────────────────────────────────────────────┤
│ Excel Column A: "Product Code"                  │
│ Maps To: [Dropdown: SKU ▼]                      │
│ AI Confidence: 95%                               │
│                                                  │
│ Excel Column B: "Product Name"                  │
│ Maps To: [Dropdown: Name ▼]                     │
│ AI Confidence: 98%                               │
│                                                  │
│ [Save Mapping] [Reset to AI] [Generate Preview] │
└─────────────────────────────────────────────────┘
```

### Review Preview Page

```
┌──────────────────────────────────────────────────────────┐
│ Import Preview Statistics                                │
├──────────────────────────────────────────────────────────┤
│ Total: 150  Selected: 145  Duplicates: 5  Errors: 0     │
└──────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────┐
│ ☑ | Row | Status | Photo | SKU | Name | Price | Actions │
├──────────────────────────────────────────────────────────┤
│ ☑ | 1   | 🟢 New | [img] | A01 | Prod | $10   | [Edit]  │
│ ☐ | 2   | 🔴 Dup | [img] | A02 | Prod | $15   | [View]  │
│ ☑ | 3   | 🟡 Sim | [img] | A03 | Prod | $20   | [Edit]  │
└──────────────────────────────────────────────────────────┘

[Select All] [Deselect Duplicates] [Import Selected Items]
```

---

## Best Practices

### For Users

1. **Always review the mapping** - AI suggestions may not be perfect
2. **Check duplicates carefully** - Decide whether to skip, update, or merge
3. **Validate data before import** - Fix errors in preview stage
4. **Handle missing photos** - Upload manually if needed
5. **Use bulk actions** - Save time with "Skip Duplicates" etc.

### For Developers

1. **Keep jobs idempotent** - Safe to retry
2. **Log everything** - Helps debugging import issues
3. **Validate early** - Catch errors in preview stage
4. **Handle photos gracefully** - Don't fail import if photo missing
5. **Use transactions** - Rollback on errors

---

## Troubleshooting

### Issue: Analysis stuck in "analyzing"

**Solution:**
- Check queue worker is running: `php artisan queue:work`
- Check logs: `storage/logs/laravel.log`
- Re-analyze from View page

### Issue: Preview generation fails

**Causes:**
- Invalid column mapping
- Corrupted file
- Missing required fields

**Solution:**
- Check error in import history
- Adjust mapping
- Re-generate preview

### Issue: Photos not importing

**Causes:**
- Photos not embedded in Excel
- Invalid URL
- Storage permission issues

**Solution:**
- Upload photos manually in preview
- Check photo_status column
- Verify storage disk configuration

### Issue: Duplicates not detected

**Causes:**
- SKU format different
- Name variations
- Missing supplier code

**Solution:**
- Standardize SKU format
- Adjust similarity threshold in code
- Use supplier_code for matching

---

## Configuration

### Environment Variables

```env
# DeepSeek AI API Key
DEEP_SEEK=sk-your-api-key-here

# Storage disk for uploads
FILESYSTEM_DISK=private

# Queue connection
QUEUE_CONNECTION=database
```

### Customization

**Adjust similarity threshold:**

Edit `ImportPreviewItem::detectDuplicate()`:
```php
if ($similarity >= 90) { // Change threshold here
    $this->markAsSimilar($existing, $similarity);
}
```

**Add custom fields:**

1. Add column to migration
2. Add to `ImportPreviewItem` fillable
3. Add to `FieldMappingService::getAvailableFields()`
4. Add to `ImportSelectedItemsJob::createProduct()`

**Customize validation:**

Edit `ImportPreviewItem::validate()`:
```php
// Add custom validation rules
if (empty($this->sku) && empty($this->supplier_code)) {
    $errors[] = 'Either SKU or Supplier Code is required';
}
```

---

## API Reference

### ImportPreviewItem Model

**Methods:**
- `detectDuplicate()` - Run duplicate detection
- `validate()` - Validate item data
- `isDuplicate()` - Check if duplicate
- `isSimilar()` - Check if similar
- `isNew()` - Check if new

**Relationships:**
- `importHistory()` - Parent import
- `existingProduct()` - Linked duplicate product

**Attributes:**
- `duplicate_status_color` - Badge color
- `duplicate_status_label` - Badge label
- `formatted_price` - Price with currency
- `photo_status_color` - Photo status color

### FieldMappingService

**Static Methods:**
- `getAvailableFields(): array`
- `getFieldOptions(): array`
- `getFieldInfo(string $field): ?array`
- `applyMapping(array $rawData, array $mapping): array`
- `parseFeatures(?string $text): array`

---

## Future Enhancements

- [ ] Support for multiple sheets in Excel
- [ ] Batch photo upload via ZIP
- [ ] Custom validation rules per import type
- [ ] Import templates (save mapping for reuse)
- [ ] Scheduled imports from FTP/API
- [ ] Export preview to Excel for offline editing
- [ ] Undo last import
- [ ] Import history comparison
- [ ] AI-powered price suggestions
- [ ] Automatic category assignment

---

## Version History

**v2.0.0** (2025-12-10)
- Complete rewrite with preview workflow
- Added field mapping configuration
- Added duplicate detection
- Added manual review and editing
- Added photo management
- Added validation system

**v1.0.0** (2025-12-09)
- Initial direct import implementation
- Basic AI analysis
- No preview or review

---

## Support

For issues or questions:
- Check logs: `storage/logs/laravel.log`
- Review documentation: `docs/`
- Contact: dev@impex.com
