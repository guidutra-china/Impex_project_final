# Testing Product Import Feature - Quick Guide

## Prerequisites

Before testing, make sure you have:

1. ✅ Pulled latest code from GitHub
2. ✅ Run migrations (if any new ones)
3. ✅ Cleared cache: `php artisan cache:clear && php artisan config:clear`
4. ✅ At least one **Currency** in the system (e.g., USD)
5. ✅ At least one **Tag** in the system (or it will be auto-created)
6. ✅ (Optional) Suppliers and Customers for testing relationships

## Step-by-Step Testing

### Test 1: Download Template

1. Go to **Products** page
2. Look for **"Import"** button (green, with upload icon)
3. Click it to see the upload dialog
4. ✅ **Expected:** Modal opens with file upload field

### Test 2: Prepare Test Excel File

**Option A: Use the Generated Template**

1. Download template from: `storage/app/public/templates/Product_Import_Template.xlsx`
2. Open in Excel/LibreOffice
3. Review the example data in Row 2
4. Modify Row 2 or add new rows with your test data
5. **Delete Row 2** if using your own data

**Option B: Create Simple Test File**

Create a minimal Excel file with these columns:

| A (Name) | AQ (Tags) |
|----------|-----------|
| Test Product 1 | Electronics |
| Test Product 2 | Lighting |

### Test 3: Test Photo Import - Option 1 (URLs)

Add a photo URL to Column G:

| A (Name) | G (Photo URL) | AQ (Tags) |
|----------|---------------|-----------|
| LED Light | https://picsum.photos/400/300 | Lighting |

**Note:** Using `https://picsum.photos/400/300` as a test image URL (random placeholder image).

### Test 4: Test Photo Import - Option 2 (Embedded)

1. Find any image on your computer
2. Copy it (Ctrl+C or Cmd+C)
3. Click on cell H2 in Excel
4. Paste (Ctrl+V or Cmd+V)
5. Image should appear in the cell

| A (Name) | H (Photo Embedded) | AQ (Tags) |
|----------|-------------------|-----------|
| Solar Panel | [Image pasted here] | Electronics |

### Test 5: Import the File

1. Save your Excel file
2. Go to **Products** page
3. Click **"Import"** button
4. Select your Excel file
5. Click **"Import"** or **"Submit"**
6. Wait for processing (should be quick for 1-2 products)

### Test 6: Verify Results

**Check Notification:**

✅ **Success:** "X product(s) imported successfully"
⚠️ **Warnings:** Check if any warnings appear (e.g., supplier not found)
❌ **Errors:** Check error messages for issues

**Check Products List:**

1. Refresh the Products page
2. Look for your imported products
3. Click on a product to view details
4. ✅ **Verify:**
   - Product name is correct
   - Photo is displayed (if you provided URL or embedded image)
   - Tags are assigned
   - Other fields are populated

**Check Photos:**

1. Navigate to product edit page
2. Check if avatar/photo is displayed
3. ✅ **Expected:** Photo shows correctly
4. Check storage: `storage/app/public/products/avatars/`
5. ✅ **Expected:** Image files are saved with UUID names

### Test 7: Test Update Existing Product

1. Export or note the SKU of an imported product
2. Create a new Excel file with the same SKU
3. Change some fields (e.g., price, description)
4. Import the file
5. ✅ **Expected:** Product is updated, not duplicated

### Test 8: Test Validation Errors

**Test Missing Required Field:**

| A (Name) | AQ (Tags) |
|----------|-----------|
| Product A | Electronics |
| (empty) | Lighting |

✅ **Expected:** Row with empty name should fail with error message

**Test Invalid Status:**

| A (Name) | D (Status) | AQ (Tags) |
|----------|------------|-----------|
| Product B | invalid_status | Electronics |

✅ **Expected:** Status defaults to 'active' (warning may appear)

### Test 9: Test Relationships

**Prerequisites:** Create a Supplier named "Test Supplier" and Customer named "Test Customer"

| A (Name) | I (Supplier Name) | K (Customer Name) | AQ (Tags) |
|----------|-------------------|-------------------|-----------|
| Product C | Test Supplier | Test Customer | Electronics |

✅ **Expected:** Product is linked to supplier and customer

**Test Non-existent Supplier:**

| A (Name) | I (Supplier Name) | AQ (Tags) |
|----------|-------------------|-----------|
| Product D | Non-existent Supplier | Electronics |

✅ **Expected:** Warning appears, product created without supplier link

### Test 10: Test Large Import

1. Create Excel with 10-20 products
2. Mix of products with and without photos
3. Import the file
4. ✅ **Expected:** All products imported successfully
5. Check processing time (should be reasonable)

## Common Issues & Solutions

### Issue: "Import Failed" Error

**Possible Causes:**
- PhpOffice/PhpSpreadsheet not installed
- File permissions issue
- Invalid Excel format

**Solutions:**
1. Check Laravel logs: `tail -100 /var/www/filament-crm/storage/logs/laravel.log`
2. Ensure PhpSpreadsheet is installed: `composer require phpoffice/phpspreadsheet`
3. Check file permissions: `chmod -R 775 storage/`

### Issue: Photos Not Importing

**For URL Photos:**
- Check URL is publicly accessible
- Check internet connection on server
- Check Laravel logs for download errors

**For Embedded Photos:**
- Ensure image is actually pasted in cell (not just a link)
- Check image format is supported (JPG, PNG, GIF)
- Check Excel file is .xlsx format (not .xls)

### Issue: "Import" Button Not Visible

**Solutions:**
1. Clear cache: `php artisan cache:clear && php artisan view:clear`
2. Check if you pulled latest code
3. Check user permissions (if applicable)

### Issue: Relationships Not Working

**Solutions:**
1. Ensure Supplier/Customer names match exactly (case-sensitive)
2. Check if Supplier/Customer exists in database
3. Review warnings in import results

## Checklist

After testing, verify:

- [ ] Import button appears on Products page
- [ ] Template file is accessible
- [ ] Can import products with basic data (name + tags)
- [ ] Can import photos via URLs
- [ ] Can import photos via embedded images
- [ ] Validation errors are shown clearly
- [ ] Success/warning/error notifications work
- [ ] Photos are saved correctly
- [ ] Products appear in list after import
- [ ] Can update existing products
- [ ] Relationships (supplier/customer) work
- [ ] Tags are assigned correctly
- [ ] Large imports work without timeout

## Next Steps

After successful testing:

1. ✅ Document any issues found
2. ✅ Test with real product data
3. ✅ Train users on how to use the feature
4. ✅ Set up regular backups before bulk imports
5. ✅ Monitor performance with large files

## Support

If you encounter issues:

1. Check Laravel logs: `storage/logs/laravel.log`
2. Review documentation: `PRODUCT_IMPORT_DOCUMENTATION.md`
3. Check this testing guide for common issues
4. Contact system administrator with error details
