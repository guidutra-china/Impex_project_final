# Payment Terms - Shipment Date Support Implementation

## Overview
Modified the Payment Terms system to support calculation of due dates based on either **Invoice Date** or **Shipment Date**. This allows for more flexible payment terms that align with real-world business scenarios where payment is due X days after shipment rather than after invoice.

## Business Logic

### Previous Behavior
- Payment terms calculated due date as: `due_date = invoice_date + days`
- All payment terms were based on invoice date only

### New Behavior
- Payment term stages now have a `calculation_base` field with two options:
  - **`invoice_date`** - Calculate from invoice date (default, maintains backward compatibility)
  - **`shipment_date`** - Calculate from shipment date (new option)
- Due date is calculated based on the **last stage** (final payment) of the payment term
- Formula: `due_date = base_date + days` where `base_date` is either `invoice_date` or `shipment_date` depending on the stage configuration

## Database Changes

### 1. Payment Term Stages Table
**Migration:** `database/migrations/2025_11_22_130000_add_calculation_base_to_payment_term_stages.php`

**Changes:**
- Renamed column `days_from_invoice` → `days` (more generic name)
- Added `calculation_base` enum field with values: `['invoice_date', 'shipment_date']`
- Default value: `'invoice_date'` (backward compatible)

**Schema:**
```php
Schema::table('payment_term_stages', function (Blueprint $table) {
    $table->unsignedSmallInteger('days');
    $table->enum('calculation_base', ['invoice_date', 'shipment_date'])
        ->default('invoice_date');
});
```

### 2. Invoices Tables
**Migration:** `database/migrations/2025_11_22_130001_add_shipment_date_to_invoices.php`

**Changes:**
- Added `shipment_date` date field to `purchase_invoices` table
- Added `shipment_date` date field to `sales_invoices` table
- Both fields are nullable (not all invoices have shipment dates immediately)

## Model Updates

### PaymentTermStage Model
**File:** `app/Models/PaymentTermStage.php`

**Changes:**
- Updated `$fillable` array: replaced `'days_from_invoice'` with `'days'`
- Added `'calculation_base'` to `$fillable` array
- Added `$casts` for `'calculation_base'` as string

### PurchaseInvoice Model
**File:** `app/Models/PurchaseInvoice.php`

**Changes:**
- Added `'shipment_date'` to `$fillable` array
- Added `'shipment_date' => 'date'` to `$casts` array

### SalesInvoice Model
**File:** `app/Models/SalesInvoice.php`

**Changes:**
- Added `'shipment_date'` to `$fillable` array
- Added `'shipment_date' => 'date'` to `$casts` array

## Form Updates

### PurchaseInvoiceForm
**File:** `app/Filament/Resources/PurchaseInvoices/Schemas/PurchaseInvoiceForm.php`

**Changes:**
1. Added `shipment_date` DatePicker field after `invoice_date`
   - Reactive field that triggers due date recalculation
   - Helper text: "Required if payment term is based on shipment date"
   - Not required by default (nullable)

2. Added `recalculateDueDate()` static method:
   - Loads payment term with stages
   - Gets the last stage (final payment) using `sortByDesc('sort_order')`
   - Checks `calculation_base` of the last stage
   - If `'shipment_date'`: uses shipment_date as base
   - If `'invoice_date'`: uses invoice_date as base (default)
   - Calculates: `due_date = base_date + stage->days`

3. Updated reactive logic:
   - `payment_term_id` change → triggers `recalculateDueDate()`
   - `invoice_date` change → triggers `recalculateDueDate()`
   - `shipment_date` change → triggers `recalculateDueDate()`

### SalesInvoiceForm
**File:** `app/Filament/Resources/SalesInvoices/Schemas/SalesInvoiceForm.php`

**Changes:**
- Same changes as PurchaseInvoiceForm (identical implementation)
- Added `shipment_date` field
- Added `recalculateDueDate()` method
- Updated reactive logic for all date fields

## Calculation Logic Flow

### Step-by-Step Process

1. **User selects Payment Term**
   - Form loads payment term with all stages
   - System identifies the last stage (highest `sort_order`)
   - Checks `calculation_base` of the last stage

2. **System determines base date**
   - If `calculation_base === 'shipment_date'`:
     - Looks for `shipment_date` value
     - If found, uses it as base date
     - If not found, due date is not calculated (waits for shipment date)
   - If `calculation_base === 'invoice_date'`:
     - Uses `invoice_date` as base date
     - Always available (required field)

3. **System calculates due date**
   - Formula: `due_date = base_date + days`
   - Uses Carbon to handle date arithmetic
   - Automatically handles month/year boundaries

4. **User changes dates**
   - Any change to `invoice_date`, `shipment_date`, or `payment_term_id` triggers recalculation
   - Due date updates automatically in real-time

## Example Scenarios

### Scenario 1: Traditional Invoice-Based Payment Term
**Payment Term:** "Net 30"
- Stage 1: 100%, 30 days, `calculation_base = 'invoice_date'`

**Invoice Data:**
- Invoice Date: 2025-01-15
- Shipment Date: (not required)

**Result:**
- Due Date: 2025-02-14 (invoice_date + 30 days)

### Scenario 2: Shipment-Based Payment Term
**Payment Term:** "30 Days After Shipment"
- Stage 1: 100%, 30 days, `calculation_base = 'shipment_date'`

**Invoice Data:**
- Invoice Date: 2025-01-15
- Shipment Date: 2025-01-20

**Result:**
- Due Date: 2025-02-19 (shipment_date + 30 days)

### Scenario 3: Multi-Stage Payment Term
**Payment Term:** "50% on Invoice, 50% 60 Days After Shipment"
- Stage 1: 50%, 0 days, `calculation_base = 'invoice_date'`
- Stage 2: 50%, 60 days, `calculation_base = 'shipment_date'`

**Invoice Data:**
- Invoice Date: 2025-01-15
- Shipment Date: 2025-01-25

**Result:**
- Due Date: 2025-03-26 (shipment_date + 60 days)
- *Note: The system uses the LAST stage to determine the final due date*

## User Experience

### Form Behavior

1. **When creating a new invoice:**
   - User selects Payment Term
   - If payment term uses `shipment_date`, the Shipment Date field becomes important
   - Helper text guides user: "Required if payment term is based on shipment date"
   - Due date calculates automatically when all required dates are filled

2. **When shipment date is entered:**
   - If payment term uses `shipment_date`, due date recalculates immediately
   - If payment term uses `invoice_date`, shipment date is just informational

3. **Visual feedback:**
   - All date fields are reactive
   - Due date updates in real-time as user enters data
   - No need to save to see the calculated due date

## Backward Compatibility

### Existing Data
- All existing payment term stages default to `calculation_base = 'invoice_date'`
- Existing invoices without `shipment_date` continue to work normally
- No data migration required for existing records

### Existing Payment Terms
- All existing payment terms will continue to work as before
- Due dates will be calculated from invoice date (default behavior)
- No changes needed to existing payment term configurations

## Testing Checklist

### Database Migration
- [ ] Run migrations successfully
- [ ] Verify `calculation_base` column exists in `payment_term_stages`
- [ ] Verify `days` column renamed from `days_from_invoice`
- [ ] Verify `shipment_date` column exists in both invoice tables
- [ ] Verify default value for `calculation_base` is `'invoice_date'`

### Payment Term Configuration
- [ ] Create new payment term with `calculation_base = 'invoice_date'`
- [ ] Create new payment term with `calculation_base = 'shipment_date'`
- [ ] Edit existing payment term and verify it still works

### Purchase Invoice Testing
- [ ] Create invoice with invoice-based payment term
- [ ] Verify due date calculates from invoice date
- [ ] Create invoice with shipment-based payment term
- [ ] Enter shipment date and verify due date calculates from shipment date
- [ ] Change invoice date and verify due date recalculates (if invoice-based)
- [ ] Change shipment date and verify due date recalculates (if shipment-based)
- [ ] Change payment term and verify due date recalculates

### Sales Invoice Testing
- [ ] Same tests as Purchase Invoice
- [ ] Verify multi-PO invoices work correctly with shipment dates

### Edge Cases
- [ ] Invoice with shipment-based term but no shipment date entered yet
- [ ] Change from invoice-based to shipment-based term
- [ ] Change from shipment-based to invoice-based term
- [ ] Multi-stage payment term with different calculation bases

## Files Modified

1. ✅ `database/migrations/2025_11_22_130000_add_calculation_base_to_payment_term_stages.php` (NEW)
2. ✅ `database/migrations/2025_11_22_130001_add_shipment_date_to_invoices.php` (NEW)
3. ✅ `app/Models/PaymentTermStage.php` (MODIFIED)
4. ✅ `app/Models/PurchaseInvoice.php` (MODIFIED)
5. ✅ `app/Models/SalesInvoice.php` (MODIFIED)
6. ✅ `app/Filament/Resources/PurchaseInvoices/Schemas/PurchaseInvoiceForm.php` (MODIFIED)
7. ✅ `app/Filament/Resources/SalesInvoices/Schemas/SalesInvoiceForm.php` (MODIFIED)

## Next Steps

1. **Run migrations:**
   ```bash
   php artisan migrate
   ```

2. **Update existing payment terms:**
   - Review existing payment terms in the admin panel
   - For terms that should be based on shipment date, update the `calculation_base` field
   - Most terms can remain as `'invoice_date'` (default)

3. **Test thoroughly:**
   - Follow the testing checklist above
   - Test with real business scenarios
   - Verify calculations are correct

4. **User training:**
   - Inform users about the new Shipment Date field
   - Explain when to use invoice-based vs shipment-based payment terms
   - Document the calculation logic for reference

## Technical Notes

### Why use the last stage?
- Payment terms can have multiple stages (e.g., 30% upfront, 70% on delivery)
- The "due date" of an invoice typically refers to when the FINAL payment is due
- Therefore, we use the last stage (highest `sort_order`) to determine the invoice due date
- Individual stage due dates can be calculated separately if needed for payment tracking

### Performance considerations
- The `recalculateDueDate()` method loads the payment term with stages using `with('stages')`
- This is an N+1 query optimization
- The method is only called on form interactions (reactive), not on every page load
- Consider caching payment term data if performance becomes an issue

### Future enhancements
- Add validation to require `shipment_date` when payment term uses `'shipment_date'`
- Add visual indicator in payment term list showing which calculation base is used
- Add bulk update tool to change calculation base for multiple payment terms
- Add report showing invoices waiting for shipment dates
