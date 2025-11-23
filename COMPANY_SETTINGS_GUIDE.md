# Company Settings - Implementation Guide

## 📋 Overview

The Company Settings system provides a centralized configuration for all company information used across the application, particularly in PDF documents, emails, and other official communications.

## ✅ What's Included

### **1. Database Table: `company_settings`**

Stores all company information in a single record (singleton pattern).

**Fields:**
- **Basic Information**
  - `company_name` - Your company name
  - `logo_path` - Path to uploaded logo
  - `address` - Street address
  - `city`, `state`, `zip_code`, `country` - Location details

- **Contact Information**
  - `phone` - Phone number
  - `email` - Email address
  - `website` - Website URL

- **Legal Information**
  - `tax_id` - Tax ID / VAT Number
  - `registration_number` - Company registration number

- **Banking Information**
  - `bank_name` - Bank name
  - `bank_account_number` - Account number
  - `bank_routing_number` - Routing/sort code
  - `bank_swift_code` - SWIFT/BIC code

- **Document Settings**
  - `footer_text` - Custom footer for invoices
  - `invoice_prefix` - Prefix for invoice numbers (e.g., INV, PI, SI)
  - `quote_prefix` - Prefix for quote numbers
  - `po_prefix` - Prefix for PO numbers

### **2. Filament Resource**

Navigate to: **Settings → Company Settings**

Features:
- ✅ Single page form (no list view needed)
- ✅ Logo upload with image editor
- ✅ Organized sections
- ✅ Collapsible sections for banking and document settings
- ✅ Helper texts for guidance
- ✅ Validation

### **3. Helper Functions**

Available globally throughout the application:

```php
// Get all settings
$settings = companySettings();

// Get company name
$name = companyName();

// Get logo path (for PDFs)
$logo = companyLogo();

// Get formatted address
$address = companyAddress();
```

### **4. Model Features**

```php
use App\Models\CompanySetting;

// Get current settings (cached)
$settings = CompanySetting::current();

// Get logo URL (for web display)
$logoUrl = $settings->logo_url;

// Get logo full path (for PDF generation)
$logoPath = $settings->logo_full_path;

// Get formatted address
$fullAddress = $settings->full_address;
```

### **5. PDF Integration**

Both Purchase and Sales Invoice PDFs now automatically use Company Settings:
- ✅ Company logo (if uploaded)
- ✅ Company name
- ✅ Full address
- ✅ Contact information
- ✅ Custom footer text

---

## 🚀 Installation Steps

### 1. Pull Latest Code

```bash
git pull origin main
```

### 2. Update Composer Autoload

```bash
composer dump-autoload
```

### 3. Run Migration

```bash
php artisan migrate
```

### 4. Seed Default Data (Optional)

```bash
php artisan db:seed --class=CompanySettingSeeder
```

This creates a default company settings record with placeholder data.

### 5. Clear Caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### 6. Create Storage Link (if not exists)

```bash
php artisan storage:link
```

This is required for logo uploads to work properly.

---

## 📝 How to Use

### Configure Company Settings

1. **Access the Admin Panel**
   - Navigate to **Settings → Company Settings**

2. **Fill in Company Information**
   - **Company Name**: Enter your company name
   - **Logo**: Upload your company logo (recommended size: 300x100px, max 2MB)
   - **Address**: Fill in complete address details
   - **Contact**: Add phone, email, website
   - **Legal**: Add tax ID and registration number
   - **Banking**: Add bank details (will appear on invoices)
   - **Footer**: Customize invoice footer text

3. **Save Settings**
   - Click "Save Settings" button
   - Settings are automatically cached for performance

### Logo Upload

**Recommended specifications:**
- Format: PNG or JPG
- Size: 300x100px (or similar aspect ratio)
- Max file size: 2MB
- Transparent background recommended for PNG

**Upload process:**
1. Click "Choose file" or drag & drop
2. Use the image editor to crop/adjust if needed
3. Save the form

### Using in Code

**In Blade Templates:**
```blade
{{ companyName() }}
{{ companySettings()->email }}
{{ companySettings()->phone }}
```

**In Controllers:**
```php
$settings = companySettings();
$companyName = $settings->company_name;
$logo = $settings->logo_url;
```

**In PDFs:**
```blade
@if(companyLogo())
<img src="{{ companyLogo() }}" alt="Logo">
@endif
<div>{{ companyName() }}</div>
<div>{{ companySettings()->address }}</div>
```

---

## 🎨 Customization

### Change Logo Size in PDFs

Edit the PDF templates:
- `resources/views/pdf/invoices/purchase-invoice.blade.php`
- `resources/views/pdf/invoices/sales-invoice.blade.php`

Find:
```html
<img src="{{ companyLogo() }}" alt="Logo" style="max-width: 150px; max-height: 60px;">
```

Adjust `max-width` and `max-height` as needed.

### Add New Fields

1. **Add column to migration:**
```php
$table->string('new_field')->nullable();
```

2. **Add to model fillable:**
```php
protected $fillable = [
    // ... existing fields
    'new_field',
];
```

3. **Add to form schema:**
```php
TextInput::make('new_field')
    ->label('New Field')
    ->maxLength(255),
```

4. **Run migration:**
```bash
php artisan migrate
```

### Customize Form Layout

Edit: `app/Filament/Resources/CompanySettings/Schemas/CompanySettingsForm.php`

You can:
- Reorder sections
- Change column spans
- Add/remove fields
- Modify validation rules
- Update helper texts

---

## 🔧 Technical Details

### Caching

Settings are cached for 1 hour (3600 seconds) for performance.

**Cache is automatically cleared when:**
- Settings are saved
- Settings are deleted

**Manually clear cache:**
```bash
php artisan cache:forget company_settings
```

Or in code:
```php
Cache::forget('company_settings');
```

### Singleton Pattern

Only one company settings record should exist. The system:
- Creates one record on first access if none exists
- Always returns the first record via `CompanySetting::current()`
- Form updates the existing record instead of creating new ones

### File Storage

Logos are stored in:
- **Disk**: `public`
- **Directory**: `storage/app/public/company/`
- **Public URL**: `/storage/company/logo.png`

Make sure the storage link is created:
```bash
php artisan storage:link
```

---

## 🐛 Troubleshooting

### Logo not showing in PDFs

**Check:**
1. Storage link exists: `php artisan storage:link`
2. File exists in `storage/app/public/company/`
3. File permissions are correct (readable)
4. Path is correct in database

**Test:**
```php
$settings = CompanySetting::first();
dd($settings->logo_full_path);
// Should return: /path/to/storage/app/public/company/filename.png
```

### Settings not updating

**Solution:**
1. Clear cache: `php artisan cache:clear`
2. Check database: `SELECT * FROM company_settings;`
3. Verify form is submitting correctly

### "Company Settings" not in menu

**Check:**
1. Resource is registered in `app/Providers/Filament/AdminPanelProvider.php`
2. Clear config cache: `php artisan config:clear`
3. User has proper permissions

### Helper functions not found

**Solution:**
1. Run: `composer dump-autoload`
2. Verify `composer.json` includes:
```json
"autoload": {
    "files": [
        "app/Helpers/helpers.php"
    ]
}
```

---

## 📊 Database Schema

```sql
CREATE TABLE `company_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) NOT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `address` text,
  `city` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `zip_code` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `tax_id` varchar(255) DEFAULT NULL,
  `registration_number` varchar(255) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `bank_account_number` varchar(255) DEFAULT NULL,
  `bank_routing_number` varchar(255) DEFAULT NULL,
  `bank_swift_code` varchar(255) DEFAULT NULL,
  `footer_text` text,
  `invoice_prefix` varchar(255) DEFAULT 'INV',
  `quote_prefix` varchar(255) DEFAULT 'QT',
  `po_prefix` varchar(255) DEFAULT 'PO',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
);
```

---

## ✅ Checklist

- [ ] Pull latest code
- [ ] Run `composer dump-autoload`
- [ ] Run migration
- [ ] Run seeder (optional)
- [ ] Create storage link
- [ ] Clear all caches
- [ ] Access Company Settings in admin panel
- [ ] Upload company logo
- [ ] Fill in all company information
- [ ] Save settings
- [ ] Generate a test PDF invoice
- [ ] Verify logo and information appear correctly

---

## 🎯 Next Steps

### Recommended Enhancements

1. **Multi-language Support**
   - Add language field
   - Translate company info per language
   - Use in PDFs based on client language

2. **Multiple Companies**
   - Add company selector
   - Link invoices to specific company
   - Support multi-tenant scenarios

3. **Email Templates**
   - Use company settings in email headers
   - Include logo in email signatures
   - Consistent branding across communications

4. **Letterhead Template**
   - Create official letterhead PDF
   - Use for formal communications
   - Include all company branding

5. **Digital Signature**
   - Upload signature image
   - Add to invoices and contracts
   - Authorized signatory information

---

**Ready to use!** 🎉

Your Company Settings system is now fully implemented and integrated with all PDF documents.
