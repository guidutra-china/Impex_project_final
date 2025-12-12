# Quick Start - Testing the Import Preview System

## Prerequisites

1. **Pull latest changes:**
```bash
cd ~/Estudos/Impex_project_final
git pull origin main
```

2. **Run migrations:**
```bash
php artisan migrate
```

3. **Ensure DeepSeek API key is set in `.env`:**
```env
DEEP_SEEK=sk-0822152792a745c9a286608ba22334f7
```

4. **Start queue worker (IMPORTANT!):**
```bash
php artisan queue:work
```
Leave this running in a separate terminal.

---

## Testing Workflow

### Step 1: Upload File

1. Go to `http://impex_project_final.test/admin/document-imports`
2. Click "New Document Import"
3. Fill in:
   - **Import Type:** products
   - **File:** Upload your Excel file (e.g., the 11MB file you tested before)
4. Click "Create"

**Expected Result:**
- Success notification: "Import Created Successfully"
- Another notification: "AI analysis has been queued"
- Redirected to view page
- Status should show "Analyzing..."

---

### Step 2: Wait for Analysis

**In queue worker terminal, you should see:**
```
Processing: App\Jobs\AnalyzeImportFileJob
Processed:  App\Jobs\AnalyzeImportFileJob
```

**Refresh the view page:**
- Status should change to "Configure Mapping"
- New button appears: "Configure Mapping"
- New button appears: "View AI Analysis"

**Click "View AI Analysis"** to see what the AI detected:
- Document type
- Supplier information
- Column headers
- Suggested mapping

---

### Step 3: Configure Field Mapping

1. Click "Configure Mapping" button
2. You'll see a form with all Excel columns
3. For each column, select the target field from dropdown:
   - Example: "Product Code" → SKU
   - Example: "Product Name" → Name
   - Example: "Unit Price" → Price
   - Example: "MOQ" → MOQ
4. Click "Save Mapping" (optional, to save progress)
5. Click "Generate Preview" button

**Expected Result:**
- Confirmation modal appears
- Click "Generate Preview"
- Success notification: "Preview Generation Started"
- Status changes to "Generating Preview..."

---

### Step 4: Wait for Preview Generation

**In queue worker terminal:**
```
Processing: App\Jobs\GenerateImportPreviewJob
Processed:  App\Jobs\GenerateImportPreviewJob
```

**Refresh the view page:**
- Status changes to "Review & Approve"
- New button appears: "Review Preview"

---

### Step 5: Review Preview

1. Click "Review Preview" button
2. You'll see a table with all products

**Statistics at the top:**
- Total Items
- Selected (for import)
- Duplicates
- Errors

**Table shows:**
- ☑️ Checkbox (to select/deselect)
- Row number
- Status badge:
  - 🟢 **New** - No duplicate
  - 🔴 **Duplicate** - Exact match found
  - 🟡 **Similar** - Similar product found
- Photo thumbnail
- SKU
- Product Name
- Price
- Brand
- MOQ
- Photo Status
- Action (Import/Skip/Update/Merge)
- Validation icon

**Try these actions:**

**Individual Actions:**
- Click eye icon to **View** details
  - See full product info
  - See duplicate comparison (if duplicate)
  - See validation errors/warnings
- Click pencil icon to **Edit**
  - Modify product data
  - Upload photo manually
  - Change action (Import/Skip/Update/Merge)
- Click trash icon to **Remove** from preview

**Bulk Actions:**
- Select multiple items
- Click "Select All" to select everything
- Click "Skip Duplicates" to auto-skip all duplicates
- Click "Set Action: Import" to set action for selected items

**Filters:**
- Filter by Status (New/Duplicate/Similar)
- Filter by Action
- Filter by Photo Status
- Filter by Selected/Not Selected
- Filter by Has Errors

---

### Step 6: Import Selected Items

1. Review the products
2. Deselect any you don't want to import
3. Click "Import Selected Items" button at the top
4. Confirmation modal shows:
   - "You are about to import X out of Y products"
5. Click "Import Selected Items"

**Expected Result:**
- Success notification: "Import Started"
- Status changes to "Importing..."

**In queue worker terminal:**
```
Processing: App\Jobs\ImportSelectedItemsJob
Processed:  App\Jobs\ImportSelectedItemsJob
```

**Refresh the view page:**
- Status changes to "Completed"
- Statistics show:
  - Success count (created)
  - Updated count
  - Skipped count
  - Error count
  - Success rate %
- Result message shows summary

---

## Common Issues & Solutions

### Issue 1: Queue worker not running

**Symptom:** Status stuck in "Analyzing..." or "Generating Preview..."

**Solution:**
```bash
# In a separate terminal
cd ~/Estudos/Impex_project_final
php artisan queue:work
```

### Issue 2: Migration error

**Symptom:** Table 'import_preview_items' doesn't exist

**Solution:**
```bash
php artisan migrate
```

### Issue 3: DeepSeek API error

**Symptom:** Analysis fails with API error

**Solution:**
Check `.env` has correct API key:
```env
DEEP_SEEK=sk-0822152792a745c9a286608ba22334f7
```

### Issue 4: Photos not showing

**Symptom:** Photo column shows placeholder

**Causes:**
- Photos not embedded in Excel (just links)
- Photo extraction failed

**Solution:**
- Upload photos manually in Edit mode
- Check photo_status column for error details

### Issue 5: All items marked as duplicate

**Symptom:** Everything shows 🔴 Duplicate

**Causes:**
- Products already exist from previous import
- SKU matching existing products

**Solution:**
- Use "Skip Duplicates" bulk action
- Or change action to "Update" to replace existing
- Or deselect duplicates and import only new ones

---

## Testing Checklist

- [ ] Upload file successfully
- [ ] AI analysis completes
- [ ] View AI analysis modal
- [ ] Configure field mapping
- [ ] Generate preview successfully
- [ ] See statistics dashboard
- [ ] View individual product details
- [ ] See duplicate comparison (if any)
- [ ] Edit a product
- [ ] Upload photo manually
- [ ] Use bulk actions
- [ ] Use filters
- [ ] Import selected items
- [ ] Check final statistics
- [ ] Verify products created in Products resource

---

## What to Test

### Duplicate Detection

1. Import a file
2. Import the SAME file again
3. In second import, all items should show as 🔴 Duplicate
4. Try different actions:
   - Skip (don't import)
   - Update (replace existing)
   - Merge (fill empty fields)

### Photo Handling

1. Test Excel with embedded images
2. Test Excel with image URLs
3. Test manual upload in Edit mode
4. Check photo_status column

### Validation

1. Try importing row with missing product name
2. Should show error icon and not be selected by default
3. Edit to fix, then select for import

### Field Mapping

1. Upload file with different column names
2. Adjust mapping to match your fields
3. Generate preview
4. Verify data mapped correctly

---

## Expected Results

After successful import:

1. **Products Resource:**
   - New products appear
   - SKU, Name, Price populated
   - Photos attached (if available)
   - Supplier linked
   - Features created as tags

2. **Import History:**
   - Status: Completed
   - Statistics showing success/errors
   - Result message with summary

3. **Preview Items:**
   - Still exist in database
   - Can be reviewed later
   - Can be used for audit trail

---

## Performance Benchmarks

- **Analysis:** ~30-60 seconds (depends on DeepSeek API)
- **Preview Generation:** ~10-30 seconds per 100 rows
- **Final Import:** ~5-15 seconds per 100 items

For 150 products:
- Total time: ~2-4 minutes
- Most time spent in AI analysis

---

## Next Steps After Testing

1. Test with your real product files
2. Adjust field mapping as needed
3. Test duplicate detection with real data
4. Verify photo extraction works
5. Check feature parsing
6. Review validation rules
7. Customize as needed

---

## Need Help?

Check the full documentation:
- `docs/IMPORT_PREVIEW_WORKFLOW.md` - Complete workflow guide
- `docs/AI_DOCUMENT_IMPORT_SYSTEM.md` - AI system details

Check logs:
```bash
tail -f storage/logs/laravel.log
```

Check queue jobs:
```bash
# See failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```
