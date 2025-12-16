# Hybrid Quotation Instructions System

## Overview

The RFQ module now supports a **hybrid quotation instructions system** that combines global defaults with per-RFQ customization.

## How It Works

### 1. Company-Wide Default

Configure default RFQ quotation instructions in **Company Settings**:

- Navigate to: **Settings → Company Settings**
- Scroll to: **Document Settings** section
- Field: **RFQ Default Quotation Instructions**
- These instructions will be used for **all RFQs** by default

### 2. Per-RFQ Override (Optional)

Customize instructions for specific RFQs:

- When creating/editing an RFQ (Order)
- Go to: **Notes** section
- Field: **RFQ Quotation Instructions**
- If filled → Uses custom instructions for this RFQ
- If empty → Uses company default instructions

### 3. PDF Generation Logic

When generating RFQ PDF:

```
IF Order has quotation_instructions
    → Use Order-specific instructions
ELSE
    → Use Company default instructions (rfq_default_instructions)
```

## Default Instructions Template

The seeder includes this default text:

```
Please provide your best quotation including:

• Unit price and total price for each item
• Lead time / delivery time
• Minimum Order Quantity (MOQ) if applicable
• Payment terms and conditions
• Validity period of your quotation
• Any additional costs (tooling, setup, shipping, etc.)

Please submit your quotation by the specified deadline.
```

## Database Structure

### company_settings table
- `rfq_default_instructions` (text, nullable) - Global default

### orders table
- `quotation_instructions` (text, nullable) - Per-RFQ override

## Benefits

✅ **Set once, use everywhere** - Configure default instructions globally  
✅ **Flexibility when needed** - Override for specific RFQs  
✅ **No repetition** - Don't type the same instructions every time  
✅ **Professional consistency** - All RFQs follow the same format by default  
✅ **Easy customization** - Simple to modify per RFQ when requirements differ

## Usage Examples

### Example 1: Standard RFQ
1. Create new RFQ
2. Leave "RFQ Quotation Instructions" empty
3. Generate PDF
4. **Result:** Uses company default instructions

### Example 2: Special Requirements RFQ
1. Create new RFQ
2. Fill "RFQ Quotation Instructions" with custom text:
   ```
   This is a rush order. Please provide:
   - Express delivery options
   - Premium quality materials only
   - 24-hour response time required
   ```
3. Generate PDF
4. **Result:** Uses custom instructions for this RFQ only

### Example 3: Update Global Default
1. Go to Company Settings
2. Update "RFQ Default Quotation Instructions"
3. Save
4. **Result:** All future RFQs (without custom instructions) use new default

## Migration Required

After pulling this update:

```bash
git pull origin main
php artisan migrate:fresh
php artisan db:seed
# Recreate super admin
```

The seeder will automatically populate the default instructions.

## Files Modified

- `database/migrations/2025_12_09_000015_create_company_settings_table.php`
- `database/migrations/2025_12_09_000036_create_orders_table.php`
- `database/seeders/CompanySettingSeeder.php`
- `resources/views/pdf/rfq/template.blade.php`
- `app/Filament/Resources/Orders/Schemas/OrderForm.php`
- `app/Filament/Resources/CompanySettings/Schemas/CompanySettingsForm.php`
- `app/Models/Order.php`
- `app/Models/CompanySetting.php`

## Commit

**Commit:** f62f7ac  
**Date:** 2025-12-16  
**Feature:** Hybrid Quotation Instructions System
