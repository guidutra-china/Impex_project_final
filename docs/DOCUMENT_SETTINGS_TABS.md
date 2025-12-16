# Document Settings Tabs - Implementation Documentation

## Overview

The Company Settings **Document Settings** section has been reorganized using **Tabs** to provide better organization and scalability for managing different document types. Each document type now has its own dedicated tab with specific configuration options.

## Implementation Details

### Files Modified

1. **app/Filament/Resources/CompanySettings/Schemas/CompanySettingsForm.php**
   - Refactored Document Settings section to use `Filament\Forms\Components\Tabs`
   - Created separate tabs for each document type
   - Added visual icons for better UX

2. **app/Models/CompanySetting.php**
   - Added new fields to `$fillable` array: `po_terms`, `packing_list_prefix`, `commercial_invoice_prefix`

3. **database/seeders/CompanySettingSeeder.php**
   - Added default values for new document settings fields

4. **database/migrations/2025_12_16_110725_add_document_settings_fields_to_company_settings.php** (NEW)
   - Migration to add new columns to `company_settings` table

## Tab Structure

### 1. RFQ Tab 📄
**Icon:** `heroicon-o-document-text`

**Fields:**
- `quote_prefix` - RFQ Number Prefix (default: "RFQ")
- `rfq_default_instructions` - Default Quotation Instructions (textarea, 10 rows)

**Purpose:** Configure RFQ document generation settings and default instructions for suppliers.

---

### 2. Proforma Invoice Tab 💰
**Icon:** `heroicon-o-document-currency-dollar`

**Fields:**
- `invoice_prefix` - Proforma Invoice Prefix (default: "PI")
- `footer_text` - Footer Text (textarea, 3 rows)

**Purpose:** Configure proforma invoice numbering and footer text.

---

### 3. Purchase Order Tab 🛒
**Icon:** `heroicon-o-shopping-cart`

**Fields:**
- `po_prefix` - Purchase Order Prefix (default: "PO")
- `po_terms` - Default Terms & Conditions (textarea, 8 rows)

**Purpose:** Configure purchase order numbering and default terms.

**Default Terms:**
```
Standard Purchase Order Terms:

1. Payment terms as agreed
2. Delivery as per schedule
3. Quality inspection upon receipt
4. Warranty as specified
5. Compliance with all applicable regulations
```

---

### 4. Other Documents Tab 📑
**Icon:** `heroicon-o-document-duplicate`

**Fields:**
- `packing_list_prefix` - Packing List Prefix (default: "PL")
- `commercial_invoice_prefix` - Commercial Invoice Prefix (default: "CI")

**Purpose:** Configure numbering for additional document types.

## Database Schema Changes

### New Columns in `company_settings` Table

| Column Name | Type | Default | Nullable | Description |
|------------|------|---------|----------|-------------|
| `po_terms` | TEXT | NULL | YES | Default purchase order terms and conditions |
| `packing_list_prefix` | VARCHAR(10) | 'PL' | NO | Prefix for packing list numbers |
| `commercial_invoice_prefix` | VARCHAR(10) | 'CI' | NO | Prefix for commercial invoice numbers |

### Migration Command

```bash
php artisan migrate
```

**Note:** This project uses `migrate:fresh` due to TODO columns in original migrations.

## Benefits

### ✅ Better Organization
- Each document type has its own dedicated space
- Related settings are grouped logically
- Reduces visual clutter in the form

### ✅ Scalability
- Easy to add new document types by adding new tabs
- No need to restructure the entire form
- Each tab is independent

### ✅ User Experience
- Visual icons help identify document types quickly
- Collapsible section reduces initial form length
- Clear separation of concerns

### ✅ Backward Compatibility
- All existing fields maintained
- No breaking changes to existing functionality
- Migration is additive only

## Usage

### Accessing Document Settings

1. Navigate to **Settings → Company Settings** in the admin panel
2. Scroll to **Document Settings** section
3. Click on the desired document type tab
4. Configure settings for that document type
5. Save changes

### Adding New Document Types

To add a new document type tab:

1. **Add new tab** in `CompanySettingsForm.php`:
```php
Tabs\Tab::make('New Document Type')
    ->icon('heroicon-o-document')
    ->schema([
        TextInput::make('new_doc_prefix')
            ->label('Prefix')
            ->default('ND')
            ->maxLength(10)
            ->required(),
        // Add more fields as needed
    ])
    ->columns(2),
```

2. **Create migration** for new fields:
```bash
php artisan make:migration add_new_document_fields_to_company_settings
```

3. **Update model** `$fillable` array in `CompanySetting.php`

4. **Update seeder** with default values in `CompanySettingSeeder.php`

## Testing Checklist

- [ ] All tabs display correctly in Company Settings
- [ ] Existing data loads properly in each tab
- [ ] New fields can be saved successfully
- [ ] Default values are seeded correctly for new installations
- [ ] Migration runs without errors
- [ ] No breaking changes to existing document generation

## Future Enhancements

### Potential Additions:
- **Commercial Invoice Tab** - Separate from Proforma Invoice with specific fields
- **Packing List Tab** - Dedicated settings for packing list generation
- **Certificate of Origin Tab** - Settings for COO documents
- **Bill of Lading Tab** - Shipping document settings

### Advanced Features:
- Document template customization per tab
- Preview functionality for each document type
- Multi-language support for document templates
- Custom field definitions per document type

## Related Documentation

- [Phase 2 Completion Summary](./PHASE_2_COMPLETION_SUMMARY.md)
- [Hybrid Quotation Instructions](./HYBRID_QUOTATION_INSTRUCTIONS.md)
- [Migration Fixes Summary](./MIGRATION_FIXES_SUMMARY.md)

## Commit Information

**Commit Hash:** `f8f19a1`  
**Branch:** `main`  
**Date:** December 16, 2025

---

**Status:** ✅ Implemented and Committed  
**Version:** 1.0  
**Author:** Manus AI Agent
