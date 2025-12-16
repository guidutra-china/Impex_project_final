# Hotfix: Financial Transactions Migration

**Date:** December 16, 2025  
**Commit:** 4337cb5  
**Severity:** Critical  
**Status:** ✅ Fixed

## Problem

The `financial_transactions` table migration had two critical columns commented out as TODO:
- `type` enum('payable', 'receivable')
- `status` enum('pending', 'partially_paid', 'paid', 'overdue', 'cancelled')

This caused a database error when trying to query the table:

```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'type' in 'where clause'
```

The error occurred in queries like:
```sql
SELECT SUM(`amount_base_currency`) FROM `financial_transactions` 
WHERE `type` = 'payable' AND ...
```

## Root Cause

The migration file `2025_12_09_000028_create_financial_transactions_table.php` had these lines:

```php
// TODO: `type` enum('payable','receivable') COLLATE utf8mb4_unicode_ci NOT NULL
// TODO: `status` enum('pending','partially_paid','paid','overdue','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending'
```

The columns were never actually created in the database, but the model and application code expected them to exist.

## Solution

Updated the migration to properly create the columns:

```php
$table->enum('type', ['payable', 'receivable'])
    ->comment('payable = Conta a Pagar, receivable = Conta a Receber');
    
$table->enum('status', ['pending', 'partially_paid', 'paid', 'overdue', 'cancelled'])
    ->default('pending');
```

## Impact

**Affected Features:**
- Project Expenses (Add Project Expense action in Orders)
- Financial Transactions listing and filtering
- Any query that filters by `type` or `status`

**Not Affected:**
- Customer Quotations module (Phase 2)
- Document Import system
- Other modules

## Required Action

⚠️ **IMPORTANT:** You must refresh your database migrations to apply this fix.

### Option 1: Fresh Migration (Recommended for Development)

**WARNING:** This will delete all data in your database!

```bash
# 1. Pull the latest code
git pull origin main

# 2. Backup your database (if you have important data)
php artisan db:backup  # or use your preferred backup method

# 3. Fresh migrate
php artisan migrate:fresh

# 4. Seed if needed
php artisan db:seed
```

### Option 2: Manual Column Addition (For Production/Existing Data)

If you have important data and can't afford to lose it:

```sql
-- Run this SQL directly on your database
ALTER TABLE `financial_transactions` 
ADD COLUMN `type` ENUM('payable', 'receivable') NOT NULL 
COMMENT 'payable = Conta a Pagar, receivable = Conta a Receber' 
AFTER `description`;

ALTER TABLE `financial_transactions` 
ADD COLUMN `status` ENUM('pending', 'partially_paid', 'paid', 'overdue', 'cancelled') 
NOT NULL DEFAULT 'pending' 
AFTER `type`;
```

### Option 3: Create a New Migration (Alternative)

If you prefer not to modify existing migrations:

```bash
php artisan make:migration add_type_and_status_to_financial_transactions
```

Then add the columns in the new migration:

```php
public function up()
{
    Schema::table('financial_transactions', function (Blueprint $table) {
        $table->enum('type', ['payable', 'receivable'])
            ->after('description')
            ->comment('payable = Conta a Pagar, receivable = Conta a Receber');
            
        $table->enum('status', ['pending', 'partially_paid', 'paid', 'overdue', 'cancelled'])
            ->default('pending')
            ->after('type');
    });
}
```

## Verification

After applying the fix, verify the columns exist:

```bash
# Using Laravel Tinker
php artisan tinker
>>> Schema::hasColumn('financial_transactions', 'type')
=> true
>>> Schema::hasColumn('financial_transactions', 'status')
=> true
```

Or check directly in MySQL:

```sql
DESCRIBE financial_transactions;
```

You should see:
- `type` enum('payable','receivable')
- `status` enum('pending','partially_paid','paid','overdue','cancelled')

## Testing

After applying the fix, test the "Add Project Expense" feature:

1. Go to Orders → Edit an order
2. Click "Add Project Expense"
3. Fill in the form
4. Submit
5. Verify the expense is created without errors
6. Check the Project Expenses widget displays correctly

## Related Files

- `database/migrations/2025_12_09_000028_create_financial_transactions_table.php` (fixed)
- `app/Models/FinancialTransaction.php` (already correct)
- `app/Filament/Resources/Orders/Pages/EditOrder.php` (uses the columns)

## Prevention

To prevent similar issues in the future:

1. **Never leave TODO comments in migrations** - Either implement or remove
2. **Test migrations immediately** after creation
3. **Run `php artisan migrate:fresh`** regularly in development
4. **Check for schema/model mismatches** before committing

## Commit Details

```
commit 4337cb5
Author: [Your Name]
Date: December 16, 2025

fix: Add missing type and status columns to financial_transactions migration

- Removed TODO comments and implemented the columns
- type: enum('payable', 'receivable')
- status: enum('pending', 'partially_paid', 'paid', 'overdue', 'cancelled')
- Fixes SQLSTATE[42S22] Column not found error
- Requires migration refresh: php artisan migrate:fresh
```

## Status

✅ **Fixed and committed to GitHub**  
⚠️ **Requires database migration refresh on your local environment**

---

**Next Steps:**
1. Pull the latest code
2. Refresh migrations (choose option above)
3. Continue testing Phase 2 of Customer Quotations
