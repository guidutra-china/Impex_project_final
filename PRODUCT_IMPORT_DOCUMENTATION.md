# Product Import from Excel - Documentation

## Overview

The Product Import feature allows you to bulk import products from Excel files with support for **two methods of importing product photos**:

1. **Option 1: Photo URLs** - Provide image URLs in the Excel file
2. **Option 2: Embedded Images** - Paste images directly into Excel cells

## Features

✅ **Bulk Import** - Import hundreds of products at once
✅ **Photo Support** - Two flexible options for product images
✅ **Auto-generation** - SKU auto-generated if not provided
✅ **Validation** - Comprehensive validation with detailed error messages
✅ **Relationships** - Automatic linking to suppliers, customers, currencies
✅ **Tags** - Support for product categorization
✅ **Update Existing** - Updates products if SKU or name matches
✅ **Detailed Reporting** - Success, errors, and warnings clearly reported

## How to Use

### Step 1: Download Template

1. Navigate to **Products** page in the system
2. Click the **"Import"** button (green upload icon)
3. Download the template Excel file from `storage/app/public/templates/Product_Import_Template.xlsx`

### Step 2: Fill in Product Data

Open the template and fill in your product data starting from **Row 2**.

#### Required Fields (Red Background)

- **Column A: Product Name*** - The name of the product
- **Column AQ: Tags*** - Comma-separated tags (only first tag will be used)

#### Optional Fields (Blue Background)

All other fields are optional. See template for complete list.

### Step 3: Add Product Photos

Choose **ONE** of the two methods below:

#### Method 1: Photo URLs (Column G) - Recommended

1. Host your product images on a web server or cloud storage
2. Get the direct URL to each image (e.g., `https://example.com/product1.jpg`)
3. Paste the URL into **Column G** for each product

**Supported formats:** JPG, JPEG, PNG, GIF, WEBP
**Max size:** 5MB per image

**Example:**
```
https://example.com/images/led-light-100w.jpg
https://cdn.mystore.com/products/solar-panel.png
```

#### Method 2: Embedded Images (Column H) - Advanced

1. Copy an image from your computer or browser
2. Click on the cell in **Column H** for the product
3. Paste the image directly into the cell (Ctrl+V or Cmd+V)
4. The image will appear in the cell

**Supported formats:** JPG, PNG, GIF
**Note:** Excel file size will increase with embedded images

**Priority:** If both URL (Column G) and embedded image (Column H) are provided, the **embedded image takes priority**.

### Step 4: Import the File

1. Save your Excel file (keep .xlsx format)
2. Go to **Products** page in the system
3. Click **"Import"** button
4. Select your Excel file
5. Click **"Import"**
6. Wait for processing (may take a few minutes for large files)

### Step 5: Review Results

After import, you'll see a notification with:

- ✅ **Success count** - Number of products imported successfully
- ⚠️ **Warnings** - Non-critical issues (e.g., supplier not found)
- ❌ **Errors** - Critical issues that prevented import (e.g., missing required fields)

## Field Specifications

### Basic Information

| Column | Field | Type | Description | Example |
|--------|-------|------|-------------|---------|
| A | Product Name* | Text | Product name | "LED Street Light 100W" |
| B | SKU | Text | Product code (auto-generated if empty) | "LED-100W-001" |
| C | Description | Text | Product description | "High-efficiency LED..." |
| D | Status | Enum | 'active' or 'inactive' (default: active) | "active" |
| E | Price | Decimal | Price in currency units | 125.50 |
| F | Currency Code | Text | USD, CNY, EUR, etc. (default: USD) | "USD" |

### Photos

| Column | Field | Type | Description |
|--------|-------|------|-------------|
| G | Photo URL | URL | Direct URL to product image |
| H | Photo Embedded | Image | Paste image directly in cell |

### Supplier & Customer

| Column | Field | Type | Description | Example |
|--------|-------|------|-------------|---------|
| I | Supplier Name | Text | Must match existing supplier | "Shenzhen LED Co" |
| J | Supplier Code | Text | Supplier's product code | "SZ-LED-100" |
| K | Customer Name | Text | Must match existing customer | "ABC Corp" |
| L | Customer Code | Text | Customer's product code | "ABC-LIGHT-01" |

### International Trade

| Column | Field | Type | Description | Example |
|--------|-------|------|-------------|---------|
| M | HS Code | Text | Harmonized System Code | "9405.40" |
| N | Country of Origin | Text | Country code | "CN" |
| O | Brand | Text | Product brand | "BrightLight" |
| P | Model Number | Text | Model/version | "BL-100W-V2" |

### Order Information

| Column | Field | Type | Description | Example |
|--------|-------|------|-------------|---------|
| Q | MOQ | Integer | Minimum Order Quantity | 100 |
| R | MOQ Unit | Text | Unit for MOQ | "pcs" |
| S | Lead Time (Days) | Integer | Production lead time | 30 |
| T | Certifications | Text | Certifications | "CE, RoHS, IP65" |

### Product Dimensions

| Column | Field | Type | Unit | Example |
|--------|-------|------|------|---------|
| U | Net Weight | Decimal | kg | 2.5 |
| V | Gross Weight | Decimal | kg | 3.2 |
| W | Length | Decimal | cm | 45 |
| X | Width | Decimal | cm | 30 |
| Y | Height | Decimal | cm | 8 |

### Packing Information

See template for complete packing fields (Inner Box, Master Carton, Container Loading).

### Notes & Tags

| Column | Field | Type | Description | Example |
|--------|-------|------|-------------|---------|
| AO | Packing Notes | Text | Special packing instructions | "Handle with care" |
| AP | Internal Notes | Text | Internal notes | "High margin product" |
| AQ | Tags* | Text | Comma-separated tags | "Electronics, Lighting" |

## Important Notes

### Data Validation

- **Required fields** must not be empty
- **Numeric fields** must contain valid numbers
- **Status** must be 'active' or 'inactive'
- **Currency** must exist in the system (creates default if not found)
- **Supplier/Customer** must match existing records (case-sensitive)

### Photo Import Behavior

1. **Embedded images** (Column H) take priority over URLs (Column G)
2. Images are downloaded/extracted and saved to `storage/app/public/products/avatars/`
3. Invalid URLs or failed downloads generate **warnings** but don't stop import
4. Supported formats: JPG, JPEG, PNG, GIF, WEBP
5. Maximum image size: 5MB

### Update vs Create

The system checks if a product already exists by:
1. First checking **SKU** (if provided)
2. Then checking **Product Name**

If a match is found, the product is **updated**. Otherwise, a new product is **created**.

### Tags

- Tags are comma-separated in Column AQ
- Example: "Electronics, Lighting, Outdoor"
- **Only the first tag** will be assigned to the product (system requirement)
- If tag doesn't exist, it will be created automatically

### Performance

- **Processing time:** ~1-5 seconds per product (depends on photo downloads)
- **Recommended batch size:** 100-500 products per file
- **Maximum file size:** 10MB
- **Timeout:** 5 minutes

## Troubleshooting

### Common Errors

| Error | Cause | Solution |
|-------|-------|----------|
| "Required field 'name' is missing" | Product name is empty | Fill in Column A |
| "Required field 'tags' is missing" | Tags column is empty | Fill in Column AQ |
| "Currency 'XYZ' not found" | Currency doesn't exist | Use valid currency code or create it first |
| "Supplier 'ABC' not found" | Supplier name doesn't match | Check spelling and case |
| "Failed to download image" | Invalid URL or network issue | Check URL is accessible |

### Common Warnings

| Warning | Cause | Impact |
|---------|-------|--------|
| "Currency 'XYZ' not found, using default" | Currency not in system | Product uses default currency (USD) |
| "Supplier 'ABC' not found" | Supplier doesn't exist | Product created without supplier link |
| "Failed to download image from URL" | Image download failed | Product created without photo |

## Technical Details

### File Structure

```
app/
├── Services/
│   ├── ProductImportService.php      # Main import logic
│   └── ProductImportConfig.php       # Configuration and mappings
├── Filament/Resources/Products/Pages/
│   └── ListProducts.php              # Import action UI
storage/app/public/
├── templates/
│   └── Product_Import_Template.xlsx  # Template file
└── products/avatars/                 # Imported photos
```

### Dependencies

- **PhpOffice/PhpSpreadsheet** - Excel file reading
- **Laravel HTTP Client** - Image downloading
- **Laravel Storage** - File management

### Column Mapping

The system uses a column-to-field mapping defined in `ProductImportConfig::COLUMN_MAPPINGS`.

To customize columns, edit this configuration file.

## Examples

### Example 1: Basic Product with URL Photo

| A (Name) | E (Price) | F (Currency) | G (Photo URL) | AQ (Tags) |
|----------|-----------|--------------|---------------|-----------|
| LED Light | 50.00 | USD | https://example.com/led.jpg | Lighting |

### Example 2: Product with Embedded Photo

| A (Name) | E (Price) | H (Photo Embedded) | AQ (Tags) |
|----------|-----------|-------------------|-----------|
| Solar Panel | 150.00 | [Paste image here] | Electronics |

### Example 3: Complete Product Data

See Row 2 in the template file for a complete example with all fields filled.

## Support

For issues or questions:
1. Check the error messages in the import results
2. Review this documentation
3. Check Laravel logs: `storage/logs/laravel.log`
4. Contact system administrator

## Version History

- **v1.0** (2025-12-10) - Initial release
  - Basic import functionality
  - Photo import via URLs
  - Photo import via embedded images
  - Validation and error reporting
  - Template generation
