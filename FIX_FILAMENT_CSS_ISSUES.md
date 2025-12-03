# Fix Filament CSS/Intrinsics Issues

## Problem Identified

The browser console shows multiple CSS parsing errors and "unpermitted intrinsics" warnings. These prevent the ProjectExpensesWidget from rendering correctly.

**Console Errors:**
```
⚠️ SES: Removing unpermitted intrinsics
⚠️ Error in parsing value for '-webkit-text-size-adjust'
⚠️ Ruleset ignored due to bad selector
⚠️ Error in parsing value for 'filter'
```

These errors are typically caused by:
1. Filament assets not properly compiled
2. Conflicting Tailwind CSS versions
3. Browser cache with old assets
4. Missing or corrupted CSS files

---

## Solution Steps

### Step 1: Clear Browser Cache

**Chrome/Edge:**
1. Press `Ctrl + Shift + Delete` (Windows) or `Cmd + Shift + Delete` (Mac)
2. Select "Cached images and files"
3. Click "Clear data"
4. **Hard refresh the page:** `Ctrl + F5` or `Cmd + Shift + R`

**Firefox:**
1. Press `Ctrl + Shift + Delete`
2. Select "Cache"
3. Click "Clear Now"
4. Hard refresh: `Ctrl + F5`

### Step 2: Rebuild Filament Assets

Run these commands in your Laravel project:

```bash
# Clear all caches
php artisan optimize:clear
php artisan view:clear
php artisan filament:cache-components

# If using Vite (Laravel 9+)
npm run build

# OR if using Laravel Mix (Laravel 8)
npm run production

# Clear browser cache and hard refresh after this
```

### Step 3: Check Filament Version

Ensure you're using a compatible Filament version:

```bash
composer show filament/filament
```

**Recommended:** Filament 3.x (latest stable)

If outdated, update:

```bash
composer update filament/filament
php artisan filament:upgrade
```

### Step 4: Verify Node Modules

Sometimes node_modules can be corrupted:

```bash
rm -rf node_modules package-lock.json
npm install
npm run build  # or npm run production
```

### Step 5: Check for Tailwind CSS Conflicts

If you have custom Tailwind configuration, ensure it doesn't conflict with Filament:

**Check `tailwind.config.js`:**
```javascript
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './app/Filament/**/*.php',
        './vendor/filament/**/*.blade.php', // Important!
    ],
    // ... rest of config
}
```

### Step 6: Disable Browser Extensions

Some browser extensions (ad blockers, privacy tools) can interfere with CSS:

1. Open browser in **Incognito/Private mode**
2. Test if widget appears
3. If it works, disable extensions one by one to find the culprit

### Step 7: Check for JavaScript Errors

Open browser console (F12) and look for JavaScript errors that might prevent widget initialization:

```javascript
// Common errors to look for:
- "Cannot read property of undefined"
- "Livewire is not defined"
- "Alpine is not defined"
```

If found, run:
```bash
php artisan livewire:publish --assets
```

### Step 8: Force Widget Visibility (Already Applied)

The widget code has been updated with:

```php
protected static bool $isDiscovered = true;

public static function canView(): bool
{
    return true;
}
```

This forces Filament to always discover and show the widget.

### Step 9: Test with Simple Content

Temporarily simplify the widget to isolate the issue:

**Edit `app/Filament/Widgets/ProjectExpensesWidget.php`:**

```php
public function table(Table $table): Table
{
    return $table
        ->heading('TEST WIDGET - If you see this, rendering works!')
        ->description('Widget is loading correctly')
        ->query(FinancialTransaction::query()->limit(5))
        ->columns([
            TextColumn::make('id')->label('ID'),
            TextColumn::make('description')->label('Description'),
        ]);
}
```

If this appears, the issue is with the complex query or columns. If not, it's a deeper rendering issue.

### Step 10: Check Livewire Version

Filament 3 requires Livewire 3:

```bash
composer show livewire/livewire
```

Should be version `^3.0`

If not:
```bash
composer require livewire/livewire:^3.0
```

---

## Alternative Solution: Use Relation Manager

If the widget continues to have issues, use a Relation Manager instead:

```bash
php artisan make:filament-relation-manager OrderResource projectExpenses description
```

This creates a tab on the RFQ edit page instead of a footer widget, which is more stable.

**Edit the generated file:**
```php
public function table(Table $table): Table
{
    return $table
        ->recordTitleAttribute('description')
        ->columns([
            Tables\Columns\TextColumn::make('transaction_number'),
            Tables\Columns\TextColumn::make('category.name'),
            Tables\Columns\TextColumn::make('amount')
                ->money(fn ($record) => $record->currency->code, divideBy: 100),
            Tables\Columns\TextColumn::make('status')->badge(),
        ])
        ->filters([
            //
        ])
        ->headerActions([
            // Add expense action here
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
}
```

Then register in `OrderResource.php`:
```php
public static function getRelations(): array
{
    return [
        RelationManagers\ProjectExpensesRelationManager::class,
        // ... other relation managers
    ];
}
```

---

## Quick Fix Commands (Run All)

```bash
# Backend
php artisan optimize:clear
php artisan view:clear
php artisan config:clear
php artisan route:clear
php artisan filament:cache-components
php artisan livewire:publish --assets

# Frontend
npm install
npm run build

# Then in browser:
# 1. Clear cache (Ctrl+Shift+Delete)
# 2. Hard refresh (Ctrl+F5)
```

---

## Expected Result

After following these steps, you should see:

1. ✅ No CSS errors in browser console
2. ✅ "Project Expenses" widget appears at bottom of RFQ edit page
3. ✅ Widget shows heading and description with totals
4. ✅ Expense table is visible (or empty state if no expenses)

---

## If Still Not Working

Provide this information:

1. **Versions:**
   ```bash
   php artisan --version
   composer show filament/filament
   composer show livewire/livewire
   node --version
   npm --version
   ```

2. **Browser Console Output:**
   - Screenshot of all errors (F12 > Console)
   - Screenshot of Network tab showing failed requests

3. **Laravel Log:**
   ```bash
   tail -n 50 storage/logs/laravel.log
   ```

4. **Test Results:**
   - Does RelatedDocumentsWidget appear? (It's on the same page)
   - Does the simple test widget (Step 9) appear?
   - Does it work in Incognito mode?

---

**Last Updated:** December 3, 2025
