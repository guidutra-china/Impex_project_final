# Debug Guide - SupplierQuote Black Screen Issue

## Current Status

We've identified and fixed several potential issues, but need your help to see the actual error logs.

## What Was Fixed

### 1. Safety Checks in `lockExchangeRate()`
```php
// Before: Would fail if no items exist
foreach ($this->items as $item) {
    $item->convertPrice($lockedRate);
}

// After: Checks if items exist first
if ($this->items()->exists()) {
    foreach ($this->items as $item) {
        $item->convertPrice($lockedRate);
    }
}
```

### 2. Safety Checks in `calculateCommission()`
```php
// Added at the start of the method:
if (!$order || !isset($order->commission_percent)) {
    return;
}

if (!$this->items()->exists()) {
    return;
}
```

### 3. Error Logging in `created()` Hook
```php
static::created(function ($quote) {
    try {
        \Log::info('SupplierQuote created hook started', ['quote_id' => $quote->id]);
        
        $quote->lockExchangeRate();
        \Log::info('Exchange rate locked', ['quote_id' => $quote->id, 'rate' => $quote->locked_exchange_rate]);
        
        $quote->calculateCommission();
        \Log::info('Commission calculated', ['quote_id' => $quote->id]);
    } catch (\Exception $e) {
        \Log::error('Error in SupplierQuote created hook', [
            'quote_id' => $quote->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        throw $e;
    }
});
```

## How to Debug

### Step 1: Pull Latest Changes
```bash
cd /path/to/Impex_project_final
git pull origin main
```

### Step 2: Clear Cache (Important!)
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Step 3: Try Creating a SupplierQuote
1. Navigate to an Order in your application
2. Go to the SupplierQuotes relation manager
3. Click "Create"
4. Fill in the form with:
   - Supplier (any)
   - Currency (preferably different from order currency)
   - Validity days (e.g., 30)
   - Status (e.g., draft)
5. Submit the form

### Step 4: Check the Logs
```bash
tail -100 storage/logs/laravel.log
```

### Step 5: Look for These Log Messages

**Success case** - You should see:
```
[timestamp] local.INFO: SupplierQuote created hook started {"quote_id":123}
[timestamp] local.INFO: Exchange rate locked {"quote_id":123,"rate":"6.5"}
[timestamp] local.INFO: Commission calculated {"quote_id":123}
```

**Error case** - You'll see:
```
[timestamp] local.ERROR: Error in SupplierQuote created hook {"quote_id":123,"error":"Some error message","trace":"..."}
```

### Step 6: Send Me the Logs

Copy the relevant log lines and send them to me. I need to see:
- The error message
- The stack trace
- Any related context

## Common Issues to Check

### Issue 1: Missing Exchange Rate
**Symptom**: Error says "Missing exchange rate"
**Solution**: Create an exchange rate for the currency pair and date

### Issue 2: Null Order
**Symptom**: Error says "Trying to get property of non-object"
**Solution**: This shouldn't happen anymore with our fixes, but if it does, check that `order_id` is being set correctly

### Issue 3: Missing Commission Percent
**Symptom**: Error says "Division by zero" or "Undefined property: commission_percent"
**Solution**: Make sure the Order has a `commission_percent` value set

### Issue 4: Database Transaction Rollback
**Symptom**: No error in logs, but no record created
**Solution**: Check if there's a database constraint violation (foreign keys, unique constraints, etc.)

## Additional Debugging

### Enable Query Logging
Add this to `app/Providers/AppServiceProvider.php` in the `boot()` method:

```php
if (config('app.debug')) {
    \DB::listen(function($query) {
        \Log::info('Query', [
            'sql' => $query->sql,
            'bindings' => $query->bindings,
            'time' => $query->time
        ]);
    });
}
```

### Check Database Directly
```sql
SELECT * FROM supplier_quotes ORDER BY id DESC LIMIT 5;
```

This will show if any records are being created but then deleted.

### Browser Console
Open browser console (F12) and look for:
- Network tab: Check the response of the create request
- Console tab: Look for JavaScript errors
- Response preview: See if there's an HTML error page being returned

## What to Send Me

Please send me:
1. ✅ The last 50-100 lines of `storage/logs/laravel.log` after attempting to create a quote
2. ✅ Screenshot of the browser console (F12 → Console tab)
3. ✅ Screenshot of the Network tab showing the failed request and its response
4. ✅ Any error messages you see on screen

With this information, I can identify the exact problem and fix it!

---

**Last Updated**: 2025-11-19
**Commit**: 5ad6492
