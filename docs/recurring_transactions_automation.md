# Recurring Transactions - Automatic Processing

## Overview

The system automatically processes recurring transactions every day at **3:00 AM**, generating financial transactions without any manual intervention.

---

## How It Works

### 1. **Recurring Transaction Model**

Each `RecurringTransaction` has:
- `name`: Description (e.g., "Office Rent")
- `type`: `payable` or `receivable`
- `amount`: Amount in cents
- `currency_id`: Currency reference
- `frequency`: `daily`, `weekly`, `monthly`, `quarterly`, `yearly`
- `next_due_date`: Next date to generate a transaction
- `is_active`: Enable/disable automatic generation

### 2. **Automatic Processing**

Every day at 3:00 AM, the system:
1. Finds all **active** recurring transactions where `next_due_date <= today`
2. Generates a `FinancialTransaction` for each one
3. Updates `next_due_date` based on the frequency
4. Logs success/failure for monitoring

### 3. **Laravel Scheduler**

The automation uses Laravel's built-in scheduler (cron jobs).

**Configuration:** `routes/console.php`

```php
Schedule::command('recurring:process')
    ->daily()
    ->at('03:00')
    ->withoutOverlapping()
    ->onSuccess(function () {
        Log::info('Recurring transactions processed successfully');
    })
    ->onFailure(function () {
        Log::error('Failed to process recurring transactions');
    });
```

---

## Setup for Production

### 1. **Enable Laravel Scheduler**

Add this **single cron entry** to your server:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

This runs every minute and Laravel decides which commands to execute based on their schedule.

### 2. **Verify Scheduler is Working**

```bash
# List all scheduled tasks
php artisan schedule:list

# Test the scheduler (runs all due tasks)
php artisan schedule:test
```

### 3. **Manual Execution (for testing)**

```bash
# Dry run (shows what would be processed without creating transactions)
php artisan recurring:process --dry-run

# Actual run (creates transactions)
php artisan recurring:process
```

---

## Command Details

### `recurring:process`

**Purpose:** Process all active recurring transactions that are due today or overdue.

**Options:**
- `--dry-run`: Preview what would be processed without creating transactions

**Output:**
```
Found 3 recurring transaction(s) to process:

📋 Processing: Office Rent
   Type: payable
   Amount: $2,500.00
   Due Date: 2025-11-24
   ✅ Generated: FT-2025-001234
   📅 Next due date: 2025-12-24

📋 Processing: Monthly Subscription
   Type: payable
   Amount: $99.00
   Due Date: 2025-11-24
   ✅ Generated: FT-2025-001235
   📅 Next due date: 2025-12-24

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📊 Summary:
   ✅ Successful: 3
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

---

## Monitoring

### Logs

All processing is logged to `storage/logs/laravel.log`:

**Success:**
```
[2025-11-24 03:00:15] local.INFO: Recurring transaction processed
{
  "recurring_id": 1,
  "recurring_name": "Office Rent",
  "transaction_id": 456,
  "transaction_number": "FT-2025-001234",
  "next_due_date": "2025-12-24"
}
```

**Failure:**
```
[2025-11-24 03:00:15] local.ERROR: Failed to process recurring transaction
{
  "recurring_id": 2,
  "recurring_name": "Subscription",
  "error": "Currency not found"
}
```

### Checking Logs

```bash
# View recent logs
tail -f storage/logs/laravel.log

# Search for recurring transaction logs
grep "Recurring transaction" storage/logs/laravel.log
```

---

## Frequency Examples

| Frequency | Description | Example |
|-----------|-------------|---------|
| `daily` | Every day | Daily backup cost |
| `weekly` | Every 7 days | Weekly service fee |
| `monthly` | Same day each month | Office rent (24th of each month) |
| `quarterly` | Every 3 months | Quarterly tax payment |
| `yearly` | Every 12 months | Annual license renewal |

---

## Manual Generation

Users can also manually generate the next transaction from the View page:

1. Go to **Recurring Transactions** list
2. Click **View** on any recurring transaction
3. Click **Generate Next Transaction** button
4. Confirm the action

This is useful for:
- Testing a new recurring transaction
- Generating a transaction early
- Recovering from errors

---

## Troubleshooting

### Scheduler Not Running

**Problem:** Transactions are not being generated automatically.

**Solution:**
1. Check if cron is configured: `crontab -l`
2. Verify scheduler is working: `php artisan schedule:list`
3. Check logs: `tail -f storage/logs/laravel.log`

### Transactions Generated Multiple Times

**Problem:** Same transaction generated twice.

**Solution:**
- The `->withoutOverlapping()` option prevents this
- Check if multiple cron entries exist
- Verify `next_due_date` is being updated correctly

### Inactive Recurring Transactions

**Problem:** A recurring transaction is not generating.

**Solution:**
1. Check if `is_active = true`
2. Verify `next_due_date` is in the past or today
3. Check logs for errors

---

## Best Practices

1. **Test with Dry Run First**
   ```bash
   php artisan recurring:process --dry-run
   ```

2. **Monitor Logs Regularly**
   - Set up log monitoring/alerts
   - Check for errors daily

3. **Use Descriptive Names**
   - Good: "Office Rent - Building A"
   - Bad: "Recurring 1"

4. **Set Realistic Schedules**
   - Don't use `daily` for monthly bills
   - Match frequency to actual billing cycle

5. **Deactivate Instead of Delete**
   - Keep history by setting `is_active = false`
   - Don't delete recurring transactions with generated transactions

---

## Future Enhancements

Possible improvements:
- Email notifications when transactions are generated
- Dashboard widget showing upcoming recurring transactions
- Bulk activation/deactivation
- Recurring transaction templates
- Support for custom frequencies (e.g., every 2 weeks)
- End date for recurring transactions (auto-deactivate)

---

## Related Documentation

- [Financial Architecture](./financial_architecture.md)
- [Multi-Currency Examples](./financial_multi_currency_examples.md)
