# Phase 2 Testing Checklist

## Pre-Testing Setup

- [ ] Pull latest code: `git pull origin main`
- [ ] Run migrations: `php artisan migrate`
- [ ] Clear cache: `php artisan optimize:clear`
- [ ] Restart Herd/server if needed

## Test 1: Generate Customer Quote

### Setup
- [ ] Create or select an existing Order (RFQ)
- [ ] Ensure order has at least 2 Supplier Quotes
- [ ] Ensure supplier quotes have status != 'draft' (e.g., 'submitted', 'approved')
- [ ] Ensure supplier quotes have items with prices

### Test Steps
- [ ] Navigate to Orders → Edit Order
- [ ] Verify "Generate Customer Quote" button is visible (green, top-right)
- [ ] Click "Generate Customer Quote"
- [ ] Form modal opens with:
  - [ ] Checkboxlist showing supplier quotes
  - [ ] Expiry days field (default: 7)
  - [ ] Internal notes field
- [ ] Select 2+ supplier quotes
- [ ] Set expiry days to 10
- [ ] Add internal note: "Test quote generation"
- [ ] Click "Generate"
- [ ] Success notification appears with quote number
- [ ] Page redirects to order edit page

## Test 2: View Customer Quote

### Navigation
- [ ] Go to Sales & Quotations → Customer Quotes
- [ ] New quote appears in the list
- [ ] Quote number format: CQ-202512-XXXX
- [ ] Status badge shows "Draft" (secondary color)

### View Page
- [ ] Click "View" (eye icon)
- [ ] Quote Information section shows:
  - [ ] Quote number (bold, copyable)
  - [ ] RFQ link (clickable, blue badge)
  - [ ] Customer name
  - [ ] Status badge (Draft)
  - [ ] Expiry date (10 days from now, green)
  - [ ] Number of options badge
- [ ] Customer Access section shows:
  - [ ] Public token (copyable)
  - [ ] Public URL (copyable, clickable)
- [ ] Commission Settings section shows:
  - [ ] Commission type badge
  - [ ] Commission percentage
- [ ] Activity Tracking section shows:
  - [ ] Sent At: "Not sent yet"
  - [ ] Viewed At: "Not viewed yet"
  - [ ] Responded At: "No response yet"
- [ ] Notes section shows:
  - [ ] Internal notes: "Test quote generation"
  - [ ] Customer notes: empty
- [ ] Metadata section shows:
  - [ ] Created At (with relative time)
  - [ ] Created By (your username)

## Test 3: Quote Items (Relation Manager)

### Items Tab
- [ ] Scroll to "Quote Options" section
- [ ] Items table displays with columns:
  - [ ] Option (Option A, Option B, etc.)
  - [ ] Supplier (Internal) - toggleable
  - [ ] Product name
  - [ ] SKU - toggleable
  - [ ] Quantity
  - [ ] Unit Price (with currency)
  - [ ] Total Price (bold, with currency)
  - [ ] Lead Time (days)
  - [ ] Selected (Yes/No badge)

### Verify Data
- [ ] Each supplier quote has its own option label
- [ ] Products match supplier quote items
- [ ] Quantities are correct
- [ ] Prices include commission (if set)
- [ ] Prices are in order currency
- [ ] No items are selected yet

## Test 4: Send to Customer

### Action
- [ ] Click "Send to Customer" button (green, top)
- [ ] Confirmation modal appears
- [ ] Confirm action
- [ ] Success notification: "Quote Sent"
- [ ] Status badge changes to "Sent" (info color)
- [ ] Sent At timestamp is populated
- [ ] "Send to Customer" button disappears

## Test 5: Copy Public Link

### Action
- [ ] Click "Copy Public Link" button
- [ ] Notification appears with the full URL
- [ ] URL format: `http://your-domain/customer-quote/public/{token}`
- [ ] Copy the URL for Phase 3 testing

## Test 6: Edit Customer Quote

### Edit Page
- [ ] Click "Edit" button
- [ ] Edit page opens
- [ ] Form shows all fields populated
- [ ] Order field is disabled (can't change RFQ)
- [ ] Change status to "Viewed"
- [ ] Change expiry date to 15 days from now
- [ ] Add customer notes: "Please review these options"
- [ ] Click "Save"
- [ ] Redirects to view page
- [ ] Changes are reflected

## Test 7: List and Filters

### List Page
- [ ] Go back to Customer Quotes list
- [ ] Test Status filter:
  - [ ] Select "Sent"
  - [ ] Quote appears
  - [ ] Select "Draft"
  - [ ] Quote disappears
  - [ ] Clear filter
- [ ] Test RFQ filter:
  - [ ] Select the RFQ
  - [ ] Quote appears
  - [ ] Clear filter
- [ ] Test Expired toggle:
  - [ ] Enable "Show Expired Only"
  - [ ] Quote disappears (not expired yet)
  - [ ] Disable toggle

### Search
- [ ] Use global search (top-right)
- [ ] Search by quote number
- [ ] Quote appears in results
- [ ] Result shows: RFQ, Customer, Status

## Test 8: Multiple Quotes

### Create Another Quote
- [ ] Go to the same Order
- [ ] Generate another customer quote
- [ ] Select different supplier quotes
- [ ] Verify new quote number increments (e.g., CQ-202512-0002)
- [ ] Verify both quotes appear in list
- [ ] Verify both have unique public tokens

## Test 9: Commission Calculation

### Setup
- [ ] Create a new order with commission_percent = 10%
- [ ] Create a supplier quote with item: $100.00
- [ ] Generate customer quote

### Verify
- [ ] If commission_type = 'embedded':
  - [ ] Customer sees unit price: $110.00
- [ ] If commission_type = 'separate':
  - [ ] Customer sees unit price: $100.00
  - [ ] (Separate commission line will be in Phase 3)

## Test 10: Error Handling

### Test Invalid Generation
- [ ] Try to generate quote from order with no supplier quotes
  - [ ] Button should not be visible
- [ ] Try to generate quote without selecting any supplier quotes
  - [ ] Form validation should prevent submission

## Issues Found

**Record any issues here:**

| Issue | Severity | Description | Status |
|-------|----------|-------------|--------|
|       |          |             |        |

## Sign-Off

- [ ] All tests passed
- [ ] No critical issues found
- [ ] Ready for Phase 3

**Tested By:** _______________  
**Date:** _______________  
**Environment:** Herd / Local  
**PHP Version:** 8.3.28  
**Laravel Version:** 12.39.0  
**Filament Version:** V4  

---

**Next:** Proceed to Phase 3 - Public Customer Interface
