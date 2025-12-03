# ProjectExpensesWidget Debugging Guide

## Issue: Widget Not Appearing on RFQ Edit Page

### Step 1: Clear All Caches

Run these commands in your Laravel project:

```bash
php artisan optimize:clear
php artisan view:clear
php artisan config:clear
php artisan route:clear
php artisan filament:cache-components
```

### Step 2: Verify Migration

Check if the `project_id` column exists in `financial_transactions` table:

```bash
php artisan migrate:status
```

Look for: `2025_12_03_add_project_id_to_financial_transactions`

If not run, execute:

```bash
php artisan migrate
```

### Step 3: Check Laravel Logs

Open `storage/logs/laravel.log` and look for errors related to:
- `ProjectExpensesWidget`
- `total_project_expenses_dollars`
- `real_margin`
- `FinancialTransaction`

### Step 4: Add Debug Code to Widget

Edit `app/Filament/Widgets/ProjectExpensesWidget.php` and add debug at line 25:

```php
public function table(Table $table): Table
{
    // DEBUG: Check if record is being passed
    if (!$this->record) {
        \Log::error('ProjectExpensesWidget: No record passed to widget');
        return $table->query(FinancialTransaction::query()->whereRaw('1 = 0'));
    }
    
    // DEBUG: Check if record is Order instance
    if (!$this->record instanceof Order) {
        \Log::error('ProjectExpensesWidget: Record is not an Order instance', [
            'type' => get_class($this->record)
        ]);
        return $table->query(FinancialTransaction::query()->whereRaw('1 = 0'));
    }
    
    // DEBUG: Check accessors
    try {
        $totalExpenses = $this->record->total_project_expenses_dollars;
        \Log::info('ProjectExpensesWidget: Total expenses', ['total' => $totalExpenses]);
    } catch (\Exception $e) {
        \Log::error('ProjectExpensesWidget: Error accessing total_project_expenses_dollars', [
            'error' => $e->getMessage()
        ]);
    }
    
    // ... rest of the code
```

### Step 5: Check Browser Console

1. Open RFQ edit page
2. Press F12 to open Developer Tools
3. Go to Console tab
4. Look for JavaScript errors
5. Go to Network tab and check for failed requests

### Step 6: Verify Widget Registration

Check `app/Filament/Resources/Orders/Pages/EditOrder.php`:

```php
protected function getFooterWidgets(): array
{
    return [
        \App\Filament\Widgets\ProjectExpensesWidget::class,
        \App\Filament\Widgets\RelatedDocumentsWidget::class,
    ];
}
```

### Step 7: Test with Simple Widget

Temporarily replace the widget content to test if the problem is with the widget logic:

```php
public function table(Table $table): Table
{
    return $table
        ->heading('TEST WIDGET')
        ->description('If you see this, the widget is loading')
        ->query(FinancialTransaction::query()->whereRaw('1 = 0'))
        ->columns([
            TextColumn::make('id')->label('ID'),
        ]);
}
```

If this appears, the problem is with the widget logic. If not, the problem is with widget registration.

### Step 8: Check Database

Verify the Order model has the relationships and accessors:

```bash
php artisan tinker
```

Then in tinker:

```php
$order = \App\Models\Order::first();
$order->projectExpenses; // Should return a collection
$order->total_project_expenses_dollars; // Should return a float
$order->real_margin; // Should return a float
$order->real_margin_percent; // Should return a float
```

### Step 9: Check Filament Version

Some widget features depend on Filament version. Check your version:

```bash
composer show filament/filament
```

### Step 10: Force Widget Visibility

Try adding this to the widget class:

```php
protected static bool $isDiscovered = true;

public static function canView(): bool
{
    return true;
}

protected function getTableRecordsPerPageSelectOptions(): array
{
    return [10, 25, 50];
}
```

## Common Issues and Solutions

### Issue 1: "Call to undefined method"

**Cause:** Accessor method name mismatch  
**Solution:** Check Order model has these methods:
- `getTotalProjectExpensesDollarsAttribute()`
- `getRealMarginAttribute()`
- `getRealMarginPercentAttribute()`

### Issue 2: "Column not found: project_id"

**Cause:** Migration not run  
**Solution:** Run `php artisan migrate`

### Issue 3: Widget shows empty even with data

**Cause:** Query filtering incorrectly  
**Solution:** Check the query in widget:
```php
FinancialTransaction::query()
    ->where('project_id', $this->record->id)
    ->where('type', 'payable')
```

### Issue 4: "Trying to get property of non-object"

**Cause:** Record not passed to widget  
**Solution:** Ensure EditOrder page extends `EditRecord` and has `getFooterWidgets()` method

## Alternative: Use Relation Manager Instead

If the widget continues not working, consider using a Relation Manager instead:

```bash
php artisan make:filament-relation-manager OrderResource projectExpenses financial_category_id
```

This creates a tab on the edit page instead of a footer widget.

## Contact for Support

If none of these steps work, please provide:
1. Laravel version: `php artisan --version`
2. Filament version: `composer show filament/filament`
3. Error logs from `storage/logs/laravel.log`
4. Browser console errors (screenshot)
5. Output of `php artisan tinker` tests from Step 8
