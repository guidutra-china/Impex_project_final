# Translation Guide: Forms and Tables

Complete guide for translating all forms and tables in the Filament admin panel.

## 📋 Table of Contents

1. [Overview](#overview)
2. [Translation File Structure](#translation-file-structure)
3. [How to Translate Forms](#how-to-translate-forms)
4. [How to Translate Tables](#how-to-translate-tables)
5. [Step-by-Step Examples](#step-by-step-examples)
6. [Batch Translation Script](#batch-translation-script)
7. [Testing](#testing)
8. [Checklist](#checklist)

---

## 📖 Overview

### What Needs Translation

✅ **Navigation** - DONE (100%)  
⏳ **Forms** - TODO  
⏳ **Tables** - TODO  
⏳ **Actions** - TODO  
⏳ **Notifications** - TODO  
⏳ **Documents (PDF/Excel)** - TODO  

### Current Status

- Translation files created: ✅
- Base translations added: ✅
- Navigation translated: ✅
- **Forms/Tables**: Need to apply `__()` helper

---

## 📁 Translation File Structure

```
lang/
├── en/
│   ├── common.php          # Actions, status, messages
│   ├── fields.php          # Form field labels ← WE ARE HERE
│   ├── navigation.php      # Menu items (DONE)
│   └── documents.php       # PDF/Excel labels
└── zh_CN/
    ├── common.php
    ├── fields.php
    ├── navigation.php
    └── documents.php
```

---

## 🔧 How to Translate Forms

### Before (Hard-coded):
```php
TextInput::make('name')
    ->label('Name')
    ->required(),
```

### After (Translated):
```php
TextInput::make('name')
    ->label(__('fields.name'))
    ->required(),
```

### Pattern:
```php
->label('Hard Coded String')  →  ->label(__('fields.key'))
```

---

## 📊 How to Translate Tables

### Before (Hard-coded):
```php
Tables\Columns\TextColumn::make('name')
    ->label('Name')
    ->searchable()
    ->sortable(),
```

### After (Translated):
```php
Tables\Columns\TextColumn::make('name')
    ->label(__('fields.name'))
    ->searchable()
    ->sortable(),
```

### Pattern:
```php
->label('Hard Coded String')  →  ->label(__('fields.key'))
```

---

## 📝 Step-by-Step Examples

### Example 1: Simple TextInput

**File:** `app/Filament/Resources/Customers/Schemas/CustomerForm.php`

**Before:**
```php
TextInput::make('name')
    ->label('Customer Name')
    ->required()
    ->maxLength(255),
```

**After:**
```php
TextInput::make('name')
    ->label(__('fields.customer_name'))
    ->required()
    ->maxLength(255),
```

**Translation files:**
```php
// lang/en/fields.php
'customer_name' => 'Customer Name',

// lang/zh_CN/fields.php
'customer_name' => '客户名称',
```

---

### Example 2: Select with Placeholder

**Before:**
```php
Select::make('status')
    ->label('Status')
    ->options([
        'active' => 'Active',
        'inactive' => 'Inactive',
    ])
    ->placeholder('Select status'),
```

**After:**
```php
Select::make('status')
    ->label(__('fields.status'))
    ->options([
        'active' => __('common.active'),
        'inactive' => __('common.inactive'),
    ])
    ->placeholder(__('common.select_status')),
```

**Translation files:**
```php
// lang/en/fields.php
'status' => 'Status',

// lang/en/common.php
'active' => 'Active',
'inactive' => 'Inactive',
'select_status' => 'Select status',

// lang/zh_CN/fields.php
'status' => '状态',

// lang/zh_CN/common.php
'active' => '活跃',
'inactive' => '非活跃',
'select_status' => '选择状态',
```

---

### Example 3: Section with Description

**Before:**
```php
Section::make('Basic Information')
    ->description('Enter the basic details')
    ->schema([
        // fields...
    ]),
```

**After:**
```php
Section::make(__('common.basic_information'))
    ->description(__('common.enter_basic_details'))
    ->schema([
        // fields...
    ]),
```

---

### Example 4: Table Column

**Before:**
```php
Tables\Columns\TextColumn::make('customer.name')
    ->label('Customer')
    ->searchable()
    ->sortable(),
```

**After:**
```php
Tables\Columns\TextColumn::make('customer.name')
    ->label(__('fields.customer_name'))
    ->searchable()
    ->sortable(),
```

---

### Example 5: Repeater

**Before:**
```php
Repeater::make('items')
    ->label('Order Items')
    ->schema([
        Select::make('product_id')
            ->label('Product')
            ->required(),
        TextInput::make('quantity')
            ->label('Quantity')
            ->numeric()
            ->required(),
    ])
    ->addActionLabel('Add Item')
    ->deleteActionLabel('Remove'),
```

**After:**
```php
Repeater::make('items')
    ->label(__('fields.order_items'))
    ->schema([
        Select::make('product_id')
            ->label(__('fields.product'))
            ->required(),
        TextInput::make('quantity')
            ->label(__('fields.quantity'))
            ->numeric()
            ->required(),
    ])
    ->addActionLabel(__('common.add_item'))
    ->deleteActionLabel(__('common.remove')),
```

---

## 🤖 Batch Translation Script

For faster implementation, use this script to translate multiple resources at once.

**File:** `translate_resources.sh`

```bash
#!/bin/bash

# List of resources to translate
RESOURCES=(
    "Shipments/ShipmentResource"
    "Products/ProductResource"
    "Customers/CustomerResource"
    "Suppliers/SupplierResource"
    "PurchaseOrders/PurchaseOrderResource"
)

for RESOURCE in "${RESOURCES[@]}"; do
    echo "Translating $RESOURCE..."
    
    # Find all schema files
    find "app/Filament/Resources/$RESOURCE" -name "*Form.php" -o -name "*Table.php" | while read file; do
        echo "  Processing $file..."
        
        # Backup original
        cp "$file" "$file.bak"
        
        # Apply translations (example patterns)
        # You'll need to customize these sed commands for your specific fields
        
        sed -i "s/->label('Name')/->label(__('fields.name'))/g" "$file"
        sed -i "s/->label('Email')/->label(__('fields.email'))/g" "$file"
        sed -i "s/->label('Phone')/->label(__('fields.phone'))/g" "$file"
        sed -i "s/->label('Status')/->label(__('fields.status'))/g" "$file"
        
        # Check syntax
        php -l "$file" > /dev/null 2>&1
        if [ $? -ne 0 ]; then
            echo "  ❌ Syntax error in $file, restoring backup"
            mv "$file.bak" "$file"
        else
            echo "  ✅ $file translated successfully"
            rm "$file.bak"
        fi
    done
done

echo "Done!"
```

**Usage:**
```bash
chmod +x translate_resources.sh
./translate_resources.sh
```

---

## 🧪 Testing

### 1. Test Translation Loading

```bash
php artisan tinker
```

```php
app()->setLocale('en');
echo __('fields.customer_name'); // Should show: Customer Name

app()->setLocale('zh_CN');
echo __('fields.customer_name'); // Should show: 客户名称

exit
```

### 2. Test in Browser

1. Login to admin panel
2. Change language to Chinese (Profile → Language Preference)
3. Navigate to a resource (e.g., Customers)
4. Check if form labels are in Chinese
5. Check if table columns are in Chinese

### 3. Check for Missing Translations

If you see the translation key instead of text:
```
fields.some_field_name  ← Missing translation!
```

**Fix:**
1. Add the key to `lang/en/fields.php`
2. Add the translation to `lang/zh_CN/fields.php`
3. Clear cache: `php artisan config:clear`

---

## ✅ Checklist

### Phase 1: Preparation
- [ ] Review all resources to translate
- [ ] List all unique field labels
- [ ] Add missing translations to `lang/en/fields.php`
- [ ] Add Chinese translations to `lang/zh_CN/fields.php`

### Phase 2: Forms
- [ ] Translate Shipment forms
- [ ] Translate Product forms
- [ ] Translate Customer forms
- [ ] Translate Supplier forms
- [ ] Translate Purchase Order forms
- [ ] Translate RFQ forms
- [ ] Translate Proforma Invoice forms
- [ ] Translate other resources

### Phase 3: Tables
- [ ] Translate Shipment tables
- [ ] Translate Product tables
- [ ] Translate Customer tables
- [ ] Translate Supplier tables
- [ ] Translate Purchase Order tables
- [ ] Translate RFQ tables
- [ ] Translate Proforma Invoice tables
- [ ] Translate other resources

### Phase 4: Actions & Notifications
- [ ] Translate action labels
- [ ] Translate button text
- [ ] Translate notification messages
- [ ] Translate validation messages

### Phase 5: Testing
- [ ] Test all forms in English
- [ ] Test all forms in Chinese
- [ ] Test all tables in English
- [ ] Test all tables in Chinese
- [ ] Fix missing translations
- [ ] Clear all caches

---

## 📚 Quick Reference

### Common Patterns

| Type | Before | After |
|------|--------|-------|
| **TextInput** | `->label('Name')` | `->label(__('fields.name'))` |
| **Select** | `->placeholder('Select...')` | `->placeholder(__('common.select'))` |
| **Section** | `Section::make('Title')` | `Section::make(__('common.title'))` |
| **Tabs** | `Tab::make('General')` | `Tab::make(__('common.general'))` |
| **Table Column** | `->label('Customer')` | `->label(__('fields.customer'))` |
| **Action** | `->label('Save')` | `->label(__('common.save'))` |

### Translation File Mapping

| Content Type | File | Example Key |
|--------------|------|-------------|
| Form labels | `fields.php` | `fields.customer_name` |
| Actions | `common.php` | `common.save` |
| Status values | `common.php` | `common.active` |
| Sections | `common.php` | `common.basic_information` |
| Messages | `common.php` | `common.success_message` |

---

## 🎯 Priority Order

Translate in this order for maximum impact:

1. **High Priority** (Most used):
   - Shipments
   - Products
   - Customers
   - Purchase Orders

2. **Medium Priority**:
   - Suppliers
   - RFQs
   - Proforma Invoices
   - Bank Accounts

3. **Low Priority**:
   - Settings
   - Tags
   - Categories
   - Other resources

---

## 💡 Tips

1. **Use Find & Replace**: Most editors support regex find/replace across files
2. **Test Incrementally**: Translate one resource, test, then move to next
3. **Keep Backups**: Always backup before batch operations
4. **Clear Cache Often**: `php artisan optimize:clear`
5. **Check Syntax**: `php -l file.php` before committing
6. **Use Version Control**: Commit after each resource is translated

---

## 🆘 Troubleshooting

### Problem: Translation not showing

**Solution:**
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### Problem: Syntax error after translation

**Solution:**
- Check for unescaped quotes
- Verify all parentheses are balanced
- Run `php -l filename.php`

### Problem: Missing translation key

**Solution:**
- Add key to both `lang/en/` and `lang/zh_CN/`
- Clear cache
- Refresh browser

---

## 📞 Need Help?

If you encounter issues:
1. Check this guide first
2. Test translations with `php artisan tinker`
3. Verify translation files exist
4. Clear all caches
5. Check browser console for errors

---

**Last Updated:** 2025-12-08  
**Status:** Ready to implement  
**Estimated Time:** 4-8 hours for all resources
