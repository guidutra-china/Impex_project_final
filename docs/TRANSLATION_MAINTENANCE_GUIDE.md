# Translation System Maintenance Guide
## Impex Project Final - Multi-Language Support

---

## Quick Reference

### Translation File Locations

```
lang/
├── en/                      # English translations
│   ├── navigation.php       # Menu items, groups, resource labels
│   ├── fields.php           # Form fields, table columns
│   └── common.php           # Actions, status, common UI
└── zh_CN/                   # Simplified Chinese translations
    ├── navigation.php
    ├── fields.php
    └── common.php
```

---

## Common Tasks

### 1. Adding a New Field Translation

**Step 1:** Add to English file
```php
// lang/en/fields.php
return [
    // ... existing translations
    'new_field_name' => 'New Field Name',
];
```

**Step 2:** Add to Chinese file
```php
// lang/zh_CN/fields.php
return [
    // ... existing translations
    'new_field_name' => '新字段名称',
];
```

**Step 3:** Use in code
```php
// In a Form
TextInput::make('new_field_name')
    ->label(__('fields.new_field_name'))
    ->required();

// In a Table
TextColumn::make('new_field_name')
    ->label(__('fields.new_field_name'))
    ->sortable();
```

---

### 2. Adding a New Resource

**Step 1:** Add navigation labels
```php
// lang/en/navigation.php
'my_new_resource' => 'My New Resource',

// lang/zh_CN/navigation.php
'my_new_resource' => '我的新资源',
```

**Step 2:** Update Resource class
```php
// app/Filament/Resources/MyNewResource.php
class MyNewResource extends Resource
{
    public static function getNavigationGroup(): ?string
    {
        return __('navigation.group_name');
    }
    
    public static function getModelLabel(): string
    {
        return __('navigation.my_new_resource');
    }
    
    public static function getPluralModelLabel(): string
    {
        return __('navigation.my_new_resource');
    }
}
```

---

### 3. Translating Form Labels

**Before:**
```php
TextInput::make('customer_name')
    ->label('Customer Name')
    ->required();
```

**After:**
```php
TextInput::make('customer_name')
    ->label(__('fields.customer_name'))
    ->required();
```

---

### 4. Translating Table Columns

**Before:**
```php
TextColumn::make('order_number')
    ->label('Order Number')
    ->sortable();
```

**After:**
```php
TextColumn::make('order_number')
    ->label(__('fields.order_number'))
    ->sortable();
```

---

### 5. Translating Actions

**Before:**
```php
Action::make('approve')
    ->label('Approve')
    ->action(fn () => ...);
```

**After:**
```php
Action::make('approve')
    ->label(__('common.approve'))
    ->action(fn () => ...);
```

---

## Translation Key Naming Conventions

### Fields (fields.php)

Use **snake_case** for field names:

```php
'customer_name' => 'Customer Name',
'order_number' => 'Order Number',
'shipping_method' => 'Shipping Method',
'unit_price' => 'Unit Price',
```

### Navigation (navigation.php)

Use **snake_case** for resource names:

```php
'products' => 'Products',
'purchase_orders' => 'Purchase Orders',
'proforma_invoices' => 'Proforma Invoices',
```

### Common (common.php)

Use **snake_case** for actions and status:

```php
'create' => 'Create',
'edit' => 'Edit',
'active' => 'Active',
'pending' => 'Pending',
```

---

## Using Automation Scripts

### Translate Tables

```bash
cd /home/ubuntu/Impex_project_final
./scripts/safe_translate.sh
```

### Translate RelationManagers

```bash
cd /home/ubuntu/Impex_project_final
./scripts/translate_relation_managers.sh
```

### Translate Forms/Schemas

```bash
cd /home/ubuntu/Impex_project_final
./scripts/translate_schemas.sh
```

### Translate Pages

```bash
cd /home/ubuntu/Impex_project_final
./scripts/translate_pages.sh
```

**Note:** These scripts use `sed` to replace hardcoded labels with `__()` calls. Always review changes before committing.

---

## Testing Translations

### Manual Testing

1. Login to the application
2. Go to Profile → Edit Profile
3. Change language to 简体中文
4. Navigate through the application
5. Check all forms, tables, and actions
6. Change back to English
7. Verify everything still works

### Automated Testing

```bash
# Check for syntax errors in translation files
php -l lang/en/navigation.php
php -l lang/en/fields.php
php -l lang/en/common.php
php -l lang/zh_CN/navigation.php
php -l lang/zh_CN/fields.php
php -l lang/zh_CN/common.php

# Check for missing translations
php artisan lang:check
```

---

## Common Patterns

### Section Headings

```php
Section::make(__('common.basic_information'))
    ->schema([
        // fields...
    ]);
```

### Fieldsets

```php
Fieldset::make(__('common.contact_information'))
    ->schema([
        // fields...
    ]);
```

### Tabs

```php
Tabs\Tab::make(__('common.details'))
    ->schema([
        // fields...
    ]);
```

### Placeholders

```php
TextInput::make('email')
    ->label(__('fields.email'))
    ->placeholder(__('common.enter_email'));
```

### Helper Text

```php
TextInput::make('tax_id')
    ->label(__('fields.tax_id'))
    ->helperText(__('common.tax_id_help'));
```

---

## Troubleshooting

### Translation Not Showing

**Problem:** Changed translation but still seeing old text

**Solution:**
```bash
# Clear Laravel cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Restart development server
php artisan serve
```

---

### Missing Translation Key

**Problem:** Seeing translation key instead of translated text (e.g., "fields.customer_name")

**Solution:**
1. Check if key exists in translation file
2. Check for typos in key name
3. Verify file syntax (no trailing commas, proper array structure)
4. Clear cache and refresh

---

### Language Not Switching

**Problem:** Language selector not working

**Solution:**
1. Check if `SetLocale` middleware is registered in Filament panel
2. Verify `locale` column exists in `users` table
3. Check if user is authenticated
4. Clear session: `php artisan session:flush`

---

## Best Practices

### DO ✅

- Always add translations to both `en` and `zh_CN` files
- Use semantic key names (e.g., `customer_name` not `cust_nm`)
- Group related translations together
- Add comments to organize translation files
- Test translations in both languages before committing
- Use automation scripts for bulk translations
- Keep translation files organized and alphabetized

### DON'T ❌

- Don't hardcode labels in code
- Don't use abbreviations in translation keys
- Don't mix English and Chinese in the same file
- Don't forget to clear cache after changes
- Don't use special characters in keys
- Don't duplicate translation keys
- Don't leave untranslated strings in production

---

## Adding a New Language

### Step 1: Create Language Directory

```bash
mkdir -p lang/pt_BR
```

### Step 2: Copy Translation Files

```bash
cp lang/en/navigation.php lang/pt_BR/navigation.php
cp lang/en/fields.php lang/pt_BR/fields.php
cp lang/en/common.php lang/pt_BR/common.php
```

### Step 3: Translate Content

Edit each file and translate all strings to the new language.

### Step 4: Update Language Switcher

```php
// app/Filament/Pages/Auth/EditProfile.php
Select::make('locale')
    ->label(__('Language'))
    ->options([
        'en' => 'English',
        'zh_CN' => '简体中文',
        'pt_BR' => 'Português (Brasil)', // Add new language
    ])
    ->default('en')
    ->required();
```

---

## Performance Optimization

### Translation Caching

Laravel automatically caches translations. To manually cache:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Lazy Loading

For large translation files, consider lazy loading:

```php
// Instead of loading all translations at once
__('fields.customer_name')

// Use trans() for better performance
trans('fields.customer_name')
```

---

## Monitoring & Maintenance

### Monthly Tasks

- [ ] Review new features for untranslated strings
- [ ] Check for missing translations using `php artisan lang:check`
- [ ] Update translation files with new keys
- [ ] Test language switching in production

### Quarterly Tasks

- [ ] Review translation quality with native speakers
- [ ] Update outdated translations
- [ ] Add new languages if needed
- [ ] Optimize translation file structure

---

## Resources

### Laravel Localization Docs
https://laravel.com/docs/10.x/localization

### Filament Docs
https://filamentphp.com/docs/3.x/panels/resources

### Translation Tools
- Google Translate API
- DeepL API
- Crowdin (for collaborative translation)

---

## Support

For questions or issues with the translation system:

1. Check this guide first
2. Review the implementation report
3. Check Laravel/Filament documentation
4. Contact the development team

---

**Last Updated:** December 8, 2024  
**Version:** 1.0  
**Maintainer:** Development Team
