# Payment Terms Enhancement - Deployment Checklist

## Pre-Deployment Checklist

### 1. Backup
- [ ] Backup database using `mysqldump` or your backup tool
- [ ] Backup `.env` file
- [ ] Backup current codebase (create a Git tag or branch)
- [ ] Note current Git commit hash: `git rev-parse HEAD`

### 2. Environment Check
- [ ] Verify PHP version is 8.2 or higher: `php -v`
- [ ] Verify Composer is installed: `composer --version`
- [ ] Verify database connection works: `php artisan tinker --execute="DB::connection()->getPdo();"`
- [ ] Check disk space: `df -h`
- [ ] Verify Git repository is clean or committed

### 3. Review Changes
- [ ] Review `PAYMENT_TERMS_IMPLEMENTATION.md`
- [ ] Review `PAYMENT_TERMS_SHIPMENT_DATE_UPDATE.md`
- [ ] Review migration files in `database/migrations/`
- [ ] Review modified model files
- [ ] Review modified form files

## Deployment Steps

### Option A: Automated Deployment (Recommended)

Run the deployment script:
```bash
cd /path/to/Impex_project_final
./deploy_payment_terms.sh
```

The script will guide you through:
1. Database backup confirmation
2. Git pull
3. Composer update
4. Cache clearing
5. Migration execution
6. Application optimization
7. Service restart

### Option B: Manual Deployment

#### Step 1: Pull Changes from Git
```bash
cd /path/to/Impex_project_final
git pull origin main
```

#### Step 2: Update Dependencies
```bash
composer install --no-dev --optimize-autoloader
```

#### Step 3: Clear Caches
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

#### Step 4: Run Migrations
```bash
php artisan migrate --force
```

Expected output:
```
Migrating: 2025_11_22_120000_add_payment_term_id_to_invoices
Migrated:  2025_11_22_120000_add_payment_term_id_to_invoices (XX.XXms)
Migrating: 2025_11_22_130000_add_calculation_base_to_payment_term_stages
Migrated:  2025_11_22_130000_add_calculation_base_to_payment_term_stages (XX.XXms)
Migrating: 2025_11_22_130001_add_shipment_date_to_invoices
Migrated:  2025_11_22_130001_add_shipment_date_to_invoices (XX.XXms)
```

#### Step 5: Optimize Application
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### Step 6: Restart Services
```bash
# Restart PHP-FPM (adjust version if needed)
sudo systemctl restart php8.2-fpm

# Restart Nginx
sudo systemctl restart nginx

# Restart queue workers
php artisan queue:restart
```

## Post-Deployment Verification

### 1. Database Verification
```bash
php artisan tinker
```

Then run:
```php
// Check payment_term_stages table
Schema::hasColumn('payment_term_stages', 'calculation_base');
Schema::hasColumn('payment_term_stages', 'days');

// Check purchase_invoices table
Schema::hasColumn('purchase_invoices', 'payment_term_id');
Schema::hasColumn('purchase_invoices', 'shipment_date');

// Check sales_invoices table
Schema::hasColumn('sales_invoices', 'payment_term_id');
Schema::hasColumn('sales_invoices', 'shipment_date');
```

All should return `true`.

### 2. Application Access
- [ ] Access admin panel: `https://yourdomain.com/admin`
- [ ] Login successfully
- [ ] No PHP errors in logs: `tail -f storage/logs/laravel.log`

### 3. Payment Terms Configuration
- [ ] Navigate to Payment Terms in admin panel
- [ ] Edit an existing payment term
- [ ] Verify you can see the stages
- [ ] Check if `calculation_base` field is available in the database

### 4. Purchase Invoice Testing
- [ ] Navigate to Purchase Invoices
- [ ] Click "Create New"
- [ ] Verify "Payment Terms" field is visible
- [ ] Select a supplier
- [ ] Select a payment term
- [ ] Verify "Shipment Date" field is visible
- [ ] Enter invoice date
- [ ] Verify due_date is auto-calculated
- [ ] Change invoice date
- [ ] Verify due_date recalculates
- [ ] Enter shipment date
- [ ] If payment term uses shipment_date, verify due_date recalculates
- [ ] Save the invoice
- [ ] Verify it saves successfully
- [ ] View the invoice in the table
- [ ] Verify "Payment Terms" column shows the term name

### 5. Sales Invoice Testing
- [ ] Navigate to Sales Invoices
- [ ] Click "Create New"
- [ ] Verify "Payment Terms" field is visible
- [ ] Select a client
- [ ] Select a payment term
- [ ] Verify "Shipment Date" field is visible
- [ ] Enter invoice date
- [ ] Verify due_date is auto-calculated
- [ ] Change invoice date
- [ ] Verify due_date recalculates
- [ ] Enter shipment date
- [ ] If payment term uses shipment_date, verify due_date recalculates
- [ ] Save the invoice
- [ ] Verify it saves successfully
- [ ] View the invoice in the table
- [ ] Verify "Payment Terms" column shows the term name

### 6. Edge Case Testing
- [ ] Create invoice without selecting payment term (should show validation error)
- [ ] Create invoice with shipment-based term but no shipment date (due_date should not calculate until shipment date is entered)
- [ ] Edit existing invoice and add payment term
- [ ] Change payment term on existing invoice
- [ ] Delete a payment term that's not in use
- [ ] Try to delete a payment term that's in use (should be prevented or set to null)

### 7. Performance Check
- [ ] Check page load times for invoice forms
- [ ] Check page load times for invoice tables
- [ ] Monitor server resources: `htop` or `top`
- [ ] Check for N+1 queries in logs (if query logging is enabled)

## Rollback Plan (If Needed)

If something goes wrong, follow these steps:

### Step 1: Rollback Migrations
```bash
php artisan migrate:rollback --step=3
```

This will rollback the last 3 migrations:
- `2025_11_22_130001_add_shipment_date_to_invoices`
- `2025_11_22_130000_add_calculation_base_to_payment_term_stages`
- `2025_11_22_120000_add_payment_term_id_to_invoices`

### Step 2: Rollback Code
```bash
git reset --hard <previous_commit_hash>
```

### Step 3: Clear Caches
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

### Step 4: Restore Database Backup (If Necessary)
```bash
mysql -u username -p database_name < backup_file.sql
```

### Step 5: Restart Services
```bash
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
php artisan queue:restart
```

## Configuration After Deployment

### 1. Update Payment Term Stages

For each payment term that should be based on shipment date:

1. Access the database directly or create an admin interface
2. Update the `payment_term_stages` table:
   ```sql
   UPDATE payment_term_stages 
   SET calculation_base = 'shipment_date' 
   WHERE payment_term_id = X AND sort_order = Y;
   ```

Example payment terms to consider:
- **"Net 30 After Shipment"**: Set `calculation_base = 'shipment_date'`
- **"60 Days After Shipment"**: Set `calculation_base = 'shipment_date'`
- **"Net 30"** (traditional): Keep `calculation_base = 'invoice_date'` (default)
- **"Due on Receipt"**: Keep `calculation_base = 'invoice_date'`

### 2. Create New Payment Terms (Examples)

#### Example 1: 30 Days After Shipment
```sql
-- Insert payment term
INSERT INTO payment_terms (name, description, is_default, created_at, updated_at)
VALUES ('30 Days After Shipment', 'Payment due 30 days after shipment date', 0, NOW(), NOW());

-- Get the ID
SET @term_id = LAST_INSERT_ID();

-- Insert stage
INSERT INTO payment_term_stages (payment_term_id, percentage, days, calculation_base, sort_order, created_at, updated_at)
VALUES (@term_id, 100, 30, 'shipment_date', 1, NOW(), NOW());
```

#### Example 2: 50% on Invoice, 50% 60 Days After Shipment
```sql
-- Insert payment term
INSERT INTO payment_terms (name, description, is_default, created_at, updated_at)
VALUES ('50/50 Split - Invoice/Shipment', '50% on invoice, 50% 60 days after shipment', 0, NOW(), NOW());

-- Get the ID
SET @term_id = LAST_INSERT_ID();

-- Insert stages
INSERT INTO payment_term_stages (payment_term_id, percentage, days, calculation_base, sort_order, created_at, updated_at)
VALUES 
(@term_id, 50, 0, 'invoice_date', 1, NOW(), NOW()),
(@term_id, 50, 60, 'shipment_date', 2, NOW(), NOW());
```

### 3. User Training

Inform users about:
- New "Shipment Date" field in invoice forms
- How payment terms now work (invoice-based vs shipment-based)
- When to enter shipment dates
- How due dates are calculated automatically

## Monitoring After Deployment

### First 24 Hours
- [ ] Monitor error logs: `tail -f storage/logs/laravel.log`
- [ ] Monitor server logs: `tail -f /var/log/nginx/error.log`
- [ ] Check database for any anomalies
- [ ] Monitor user feedback and bug reports
- [ ] Check invoice creation rate (should be normal)

### First Week
- [ ] Review any reported issues
- [ ] Check if due dates are calculating correctly
- [ ] Verify payment tracking is working
- [ ] Gather user feedback
- [ ] Optimize if performance issues are detected

## Support Contacts

If issues arise:
1. Check logs first: `storage/logs/laravel.log`
2. Review error messages carefully
3. Check the documentation files
4. Rollback if critical issues occur
5. Contact development team with:
   - Error messages
   - Steps to reproduce
   - Screenshots if applicable
   - Server logs

## Success Criteria

Deployment is successful when:
- ✅ All migrations run without errors
- ✅ No PHP errors in logs
- ✅ Admin panel is accessible
- ✅ Invoice forms load correctly
- ✅ Payment Terms field is visible and functional
- ✅ Shipment Date field is visible
- ✅ Due dates calculate automatically
- ✅ Invoices can be created and saved
- ✅ Payment Terms column shows in tables
- ✅ Existing invoices still work
- ✅ No performance degradation

## Notes

- This deployment adds new features but maintains backward compatibility
- Existing invoices without payment terms will continue to work
- Existing payment terms will default to `invoice_date` calculation
- No data migration is required for existing records
- The system is designed to be flexible and extensible

## Deployment Date

- **Deployed by:** _________________
- **Date:** _________________
- **Time:** _________________
- **Git Commit:** _________________
- **Status:** [ ] Success [ ] Failed [ ] Rolled Back
- **Notes:** _________________
