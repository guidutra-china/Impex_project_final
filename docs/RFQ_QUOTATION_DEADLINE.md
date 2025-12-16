# RFQ Quotation Deadline Feature

## Overview

Added a **Quotation Deadline** field to the RFQ system, allowing users to specify a deadline date for suppliers to submit their quotations. The deadline is displayed prominently in the RFQ PDF and helps manage supplier response timelines.

## Implementation Details

### Database Changes

**Migration:** `2025_12_16_115630_add_quotation_deadline_to_orders.php`

Added `quotation_deadline` column to `orders` table:
- Type: `DATE`
- Nullable: `YES`
- Position: After `quotation_instructions`

### Model Updates

**File:** `app/Models/Order.php`

**Changes:**
- Added `quotation_deadline` to `$fillable` array
- Added `'quotation_deadline' => 'date'` to `$casts` array

### Form Updates

**File:** `app/Filament/Resources/Orders/Schemas/OrderForm.php`

**New Field:**
```php
DatePicker::make('quotation_deadline')
    ->label('Quotation Deadline')
    ->helperText('Deadline for suppliers to submit their quotations')
    ->native(false)
    ->displayFormat('d/m/Y')
    ->columnSpan(1)
```

**Features:**
- Non-native date picker (better UX)
- Display format: DD/MM/YYYY
- Optional field (nullable)
- Clear helper text

### PDF Template Updates

**File:** `resources/views/pdf/rfq/template.blade.php`

**Changes:**

1. **Header Section** - Shows deadline prominently:
```blade
@if($model->quotation_deadline)
    <p><strong>Deadline:</strong> {{ $model->quotation_deadline->format('M d, Y') }}</p>
@endif
```

2. **Instructions Section** - Shows deadline at bottom:
```blade
@if($model->quotation_deadline)
    <p style="margin-top: 15px;">
        <strong>Please submit your quotation by: {{ $model->quotation_deadline->format('M d, Y') }}</strong>
    </p>
@endif
```

### Default Instructions Update

**Files Updated:**
- `database/seeders/CompanySettingSeeder.php`
- `app/Console/Commands/PopulateCompanySettingsDefaults.php`

**Change:**
Removed the generic text "Please submit your quotation by the specified deadline" from default instructions, since the deadline is now shown separately with the actual date.

**Old Default Instructions:**
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

**New Default Instructions:**
```
Please provide your best quotation including:

• Unit price and total price for each item
• Lead time / delivery time
• Minimum Order Quantity (MOQ) if applicable
• Payment terms and conditions
• Validity period of your quotation
• Any additional costs (tooling, setup, shipping, etc.)
```

## Usage

### Setting a Deadline

1. Navigate to **Orders → RFQs**
2. Create or edit an RFQ
3. In the form, find the **Quotation Deadline** field
4. Select a date using the date picker
5. Save the RFQ

### PDF Display

When a deadline is set:

**In the Header:**
```
Date: Dec 16, 2025
Deadline: Dec 31, 2025  ← NEW
Valid Until: Jan 15, 2026
```

**At Bottom of Instructions:**
```
Please submit your quotation by: Dec 31, 2025
```

When **no deadline** is set:
- The deadline line does not appear in the header
- The "Please submit by" line does not appear in instructions
- No visual clutter from empty fields

## Benefits

### ✅ Clear Communication
- Suppliers know exactly when quotations are due
- No ambiguity about submission deadlines
- Professional appearance in RFQ documents

### ✅ Better Planning
- Track which RFQs have urgent deadlines
- Plan follow-ups based on deadline dates
- Manage supplier response timelines

### ✅ Flexible
- Optional field - only use when needed
- Can be set differently for each RFQ
- Easy to update if deadline changes

### ✅ Professional PDF
- Deadline prominently displayed in header
- Reinforced at bottom of instructions
- Clean formatting with conditional display

## Migration Instructions

To apply this feature to your database:

```bash
php artisan migrate
```

**Note:** This project uses `migrate:fresh` due to TODO columns in original migrations. If running fresh migration, all changes will be applied automatically.

## Future Enhancements

### Potential Additions:
- **Deadline Notifications** - Email reminders as deadline approaches
- **Overdue Indicators** - Visual flags for RFQs past deadline
- **Deadline Analytics** - Track supplier response times vs. deadlines
- **Auto-close RFQs** - Automatically close RFQs after deadline passes
- **Deadline Extensions** - Track deadline changes and extensions

## Related Features

- **Quotation Instructions** - Custom or default instructions per RFQ
- **Hybrid Instructions System** - Global defaults with per-RFQ overrides
- **RFQ PDF Generation** - Professional confidential RFQ documents

## Related Documentation

- [Hybrid Quotation Instructions](./HYBRID_QUOTATION_INSTRUCTIONS.md)
- [Phase 2 Completion Summary](./PHASE_2_COMPLETION_SUMMARY.md)
- [Document Settings Tabs](./DOCUMENT_SETTINGS_TABS.md)

## Commit Information

**Commit Hash:** `6bee9a8`  
**Branch:** `main`  
**Date:** December 16, 2025

---

**Status:** ✅ Implemented and Committed  
**Version:** 1.0  
**Author:** Manus AI Agent
