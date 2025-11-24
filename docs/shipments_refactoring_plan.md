# Shipments System Refactoring Plan

**Date:** November 24, 2025  
**Purpose:** Professional refactoring of Shipments system with multi-invoice support, quantity tracking, commercial invoice, and packing list with master boxes

---

## 📊 CURRENT SYSTEM ANALYSIS

### What Exists Now

#### Current Structure:
```
Shipment (1) → (N) ShipmentItems
     ↓
SalesOrder (1:1 relationship)
```

#### Current Limitations:
❌ **Single Sales Order per Shipment** - Can't combine multiple invoices  
❌ **No Quantity Tracking** - Can't track shipped vs ordered quantities  
❌ **No Commercial Invoice** - Missing critical export document  
❌ **No Packing List** - No detailed packing information  
❌ **No Master Box System** - Can't group items into boxes  
❌ **No Partial Shipments** - Can't ship orders in multiple shipments  
❌ **No Shipment-Invoice Link** - Shipment linked to Order, not Invoice  

---

## 🎯 BUSINESS REQUIREMENTS

### Core Requirements:

1. **Multi-Invoice Shipments**
   - One shipment can contain products from multiple Sales Invoices
   - Track which items come from which invoice
   - Support partial fulfillment of invoices

2. **Quantity Tracking**
   - Track ordered quantity vs shipped quantity
   - Support multiple shipments for same invoice
   - Calculate remaining quantity to ship
   - Prevent over-shipment

3. **Commercial Invoice**
   - Generate commercial invoice for customs
   - Include all required customs information
   - Support multiple currencies
   - Include HS codes, country of origin
   - Calculate total customs value

4. **Packing List**
   - Detailed list of all items in shipment
   - Support master boxes (cartons)
   - Track box dimensions and weight
   - Track items per box
   - Generate professional packing list PDF

5. **Master Box System**
   - Group items into physical boxes/cartons
   - Track box number, dimensions, weight
   - Track which items are in which box
   - Support mixed items per box
   - Calculate total boxes, weight, volume

---

## 🏗️ PROPOSED ARCHITECTURE

### New Database Structure

```
SalesInvoice (N) ←→ (M) Shipment
         ↓                  ↓
  SalesInvoiceItem    ShipmentInvoice (pivot)
         ↓                  ↓
         └─────→ ShipmentItem ←─────┘
                      ↓
                 PackingBox
                      ↓
               PackingBoxItem
                      ↓
            CommercialInvoice
```

### Entity Relationship:

```
┌─────────────────┐
│  SalesInvoice   │
│  - invoice_number│
│  - client_id    │
│  - total        │
└────────┬────────┘
         │ 1
         │
         │ N
┌────────▼────────────────┐
│ SalesInvoiceItem        │
│ - product_id            │
│ - quantity              │◄──────┐
│ - quantity_shipped      │       │
│ - quantity_remaining    │       │
└─────────────────────────┘       │
                                  │
┌─────────────────────────────────┼────────┐
│         Shipment                │        │
│  - shipment_number              │        │
│  - shipment_date                │        │
│  - carrier                      │        │
│  - tracking_number              │        │
│  - status                       │        │
│  - total_boxes                  │        │
│  - total_weight                 │        │
│  - total_volume                 │        │
└────────┬────────────────────────┘        │
         │ 1                               │
         │                                 │
         │ N                               │
┌────────▼────────────────────┐            │
│   ShipmentInvoice (pivot)   │            │
│ - shipment_id               │            │
│ - sales_invoice_id          │            │
│ - notes                     │            │
└─────────────────────────────┘            │
                                           │
┌──────────────────────────────────────────┼─┐
│        ShipmentItem                      │ │
│  - shipment_id                           │ │
│  - sales_invoice_item_id  ───────────────┘ │
│  - product_id                              │
│  - quantity_to_ship                        │
│  - quantity_shipped                        │
│  - unit_price (for commercial invoice)     │
│  - hs_code                                 │
│  - country_of_origin                       │
│  - customs_value                           │
└────────┬───────────────────────────────────┘
         │ 1
         │
         │ N
┌────────▼────────────────┐
│     PackingBox          │
│  - shipment_id          │
│  - box_number           │
│  - box_type             │
│  - length               │
│  - width                │
│  - height               │
│  - weight               │
│  - volume               │
└────────┬────────────────┘
         │ 1
         │
         │ N
┌────────▼────────────────┐
│   PackingBoxItem        │
│  - packing_box_id       │
│  - shipment_item_id     │
│  - quantity             │
│  - notes                │
└─────────────────────────┘

┌─────────────────────────┐
│  CommercialInvoice      │
│  - shipment_id          │
│  - invoice_number       │
│  - invoice_date         │
│  - exporter_info        │
│  - importer_info        │
│  - terms_of_sale        │
│  - total_value          │
│  - currency_id          │
└─────────────────────────┘
```

---

## 📋 DETAILED SPECIFICATIONS

### 1. Shipment Model (Enhanced)

```php
class Shipment extends Model
{
    protected $fillable = [
        // Identification
        'shipment_number',          // SHP-2025-0001
        
        // Type & Method
        'shipment_type',            // outgoing, incoming
        'shipping_method',          // air, sea, land, courier
        
        // Carrier Information
        'carrier',                  // DHL, FedEx, Maersk
        'tracking_number',
        'container_number',         // For sea freight
        'vessel_name',              // For sea freight
        'voyage_number',            // For sea freight
        'flight_number',            // For air freight
        
        // Status
        'status',                   // draft, confirmed, picked_up, in_transit, 
                                    // customs_clearance, delivered, cancelled
        
        // Dates
        'shipment_date',
        'estimated_departure_date',
        'actual_departure_date',
        'estimated_arrival_date',
        'actual_arrival_date',
        
        // Addresses
        'origin_port',              // Port of loading
        'destination_port',         // Port of discharge
        'origin_address',
        'destination_address',
        'notify_party_address',     // For commercial invoice
        
        // Totals (calculated)
        'total_boxes',              // Count of packing boxes
        'total_weight',             // In kg
        'total_volume',             // In m³ (CBM)
        'total_items',              // Count of items
        'total_quantity',           // Sum of all quantities
        
        // Costs
        'freight_cost',
        'insurance_cost',
        'other_costs',
        'total_shipping_cost',
        'currency_id',
        
        // Incoterms
        'incoterm',                 // FOB, CIF, EXW, etc.
        
        // Documents
        'commercial_invoice_generated',
        'packing_list_generated',
        'bill_of_lading_number',
        'awb_number',               // Air Waybill
        
        // Notes
        'notes',
        'special_instructions',
        'customs_notes',
        
        // Audit
        'created_by',
        'confirmed_by',
        'confirmed_at',
    ];
    
    // Relationships
    public function salesInvoices(): BelongsToMany;
    public function shipmentInvoices(): HasMany;
    public function items(): HasMany;
    public function packingBoxes(): HasMany;
    public function commercialInvoice(): HasOne;
    public function documents(): MorphMany;
    public function trackingEvents(): HasMany;
    
    // Methods
    public function calculateTotals(): void;
    public function canBeConfirmed(): bool;
    public function confirm(): void;
    public function generateCommercialInvoice(): CommercialInvoice;
    public function generatePackingList(): string; // PDF path
    public function getShippingProgress(): array;
}
```

### 2. ShipmentInvoice (Pivot Table)

**Purpose:** Link shipments to multiple sales invoices

```php
class ShipmentInvoice extends Model
{
    protected $fillable = [
        'shipment_id',
        'sales_invoice_id',
        'notes',                    // Notes specific to this invoice in this shipment
        'total_items',              // Count of items from this invoice
        'total_quantity',           // Total quantity from this invoice
        'total_value',              // Total value from this invoice
    ];
    
    public function shipment(): BelongsTo;
    public function salesInvoice(): BelongsTo;
}
```

### 3. ShipmentItem (Enhanced)

**Purpose:** Track individual items in shipment with quantity control

```php
class ShipmentItem extends Model
{
    protected $fillable = [
        'shipment_id',
        'sales_invoice_item_id',    // Link to specific invoice item
        'product_id',
        
        // Quantity Tracking
        'quantity_ordered',         // From invoice
        'quantity_to_ship',         // In this shipment
        'quantity_shipped',         // Actually shipped (confirmed)
        
        // Product Info (denormalized for speed)
        'product_name',
        'product_sku',
        'product_description',
        
        // Customs Information
        'hs_code',                  // Harmonized System code
        'country_of_origin',
        'unit_price',               // For customs value
        'customs_value',            // quantity * unit_price
        
        // Physical Properties
        'unit_weight',              // Per unit in kg
        'total_weight',             // quantity * unit_weight
        'unit_volume',              // Per unit in m³
        'total_volume',             // quantity * unit_volume
        
        // Packing Status
        'packing_status',           // unpacked, partially_packed, fully_packed
        'quantity_packed',          // How many are in boxes
        'quantity_remaining',       // Not yet packed
        
        // Notes
        'notes',
    ];
    
    // Relationships
    public function shipment(): BelongsTo;
    public function salesInvoiceItem(): BelongsTo;
    public function product(): BelongsTo;
    public function packingBoxItems(): HasMany;
    
    // Methods
    public function calculateTotals(): void;
    public function canBePacked(): bool;
    public function getRemainingToPack(): int;
    public function isFullyPacked(): bool;
}
```

### 4. PackingBox (NEW)

**Purpose:** Represent physical boxes/cartons in shipment

```php
class PackingBox extends Model
{
    protected $fillable = [
        'shipment_id',
        
        // Box Identification
        'box_number',               // 1, 2, 3... or BOX-001
        'box_type',                 // carton, pallet, crate, bag
        'box_label',                // Custom label
        
        // Dimensions (in cm)
        'length',
        'width',
        'height',
        
        // Weight & Volume
        'gross_weight',             // Including box weight (kg)
        'net_weight',               // Product weight only (kg)
        'volume',                   // L x W x H in m³
        
        // Totals
        'total_items',              // Count of different items
        'total_quantity',           // Sum of all quantities
        
        // Status
        'packing_status',           // empty, packing, sealed, shipped
        'sealed_at',
        'sealed_by',
        
        // Notes
        'notes',
        'contents_description',     // Brief description
    ];
    
    // Relationships
    public function shipment(): BelongsTo;
    public function items(): HasMany; // PackingBoxItem
    public function sealedBy(): BelongsTo; // User
    
    // Methods
    public function calculateTotals(): void;
    public function calculateVolume(): float;
    public function seal(): void;
    public function canBeSealed(): bool;
    public function getItemsSummary(): array;
}
```

### 5. PackingBoxItem (NEW)

**Purpose:** Track which items are in which box

```php
class PackingBoxItem extends Model
{
    protected $fillable = [
        'packing_box_id',
        'shipment_item_id',
        'quantity',                 // How many of this item in this box
        'notes',
    ];
    
    // Relationships
    public function packingBox(): BelongsTo;
    public function shipmentItem(): BelongsTo;
}
```

### 6. CommercialInvoice (NEW)

**Purpose:** Generate commercial invoice for customs

```php
class CommercialInvoice extends Model
{
    protected $fillable = [
        'shipment_id',
        
        // Invoice Information
        'invoice_number',           // CI-2025-0001
        'invoice_date',
        
        // Parties
        'exporter_name',
        'exporter_address',
        'exporter_tax_id',
        'exporter_country',
        
        'importer_name',
        'importer_address',
        'importer_tax_id',
        'importer_country',
        
        'notify_party_name',
        'notify_party_address',
        
        // Shipping Details
        'port_of_loading',
        'port_of_discharge',
        'country_of_origin',
        'country_of_destination',
        
        // Terms
        'incoterm',                 // FOB, CIF, EXW
        'payment_terms',
        'terms_of_sale',
        
        // Totals
        'currency_id',
        'subtotal',
        'freight_cost',
        'insurance_cost',
        'total_value',
        
        // Additional Info
        'reason_for_export',        // Sale, Sample, Return, Repair
        'declaration',              // Customs declaration text
        
        // Status
        'status',                   // draft, issued, submitted
        'issued_at',
        'issued_by',
        
        // Notes
        'notes',
    ];
    
    // Relationships
    public function shipment(): BelongsTo;
    public function currency(): BelongsTo;
    public function issuedBy(): BelongsTo; // User
    
    // Methods
    public function generatePDF(): string;
    public function issue(): void;
    public function canBeIssued(): bool;
}
```

### 7. SalesInvoiceItem (Enhanced)

**Purpose:** Add quantity tracking to existing model

```php
// Add these fields to existing SalesInvoiceItem
protected $fillable = [
    // ... existing fields ...
    
    // Shipment Tracking (NEW)
    'quantity_shipped',         // Total shipped across all shipments
    'quantity_remaining',       // quantity - quantity_shipped
    'shipment_status',          // not_shipped, partially_shipped, fully_shipped
];

// Add methods
public function updateShipmentStatus(): void;
public function getRemainingToShip(): int;
public function canBeShipped(): bool;
public function isFullyShipped(): bool;
```

---

## 🔄 WORKFLOW & BUSINESS LOGIC

### Shipment Creation Workflow

```
1. CREATE SHIPMENT (Draft)
   ↓
2. SELECT SALES INVOICES
   - Can select multiple invoices
   - Shows items from each invoice
   ↓
3. SELECT ITEMS TO SHIP
   - Choose which items from which invoices
   - Specify quantity (can be partial)
   - System validates: quantity <= remaining_to_ship
   ↓
4. PACK ITEMS INTO BOXES
   - Create packing boxes
   - Assign items to boxes
   - Specify quantity per box
   - System validates: sum(box quantities) = quantity_to_ship
   ↓
5. ENTER SHIPPING DETAILS
   - Carrier, tracking number
   - Dates, costs
   - Incoterm
   ↓
6. GENERATE DOCUMENTS
   - Commercial Invoice
   - Packing List
   ↓
7. CONFIRM SHIPMENT
   - Locks the shipment
   - Updates invoice item quantities
   - Changes status to "confirmed"
   ↓
8. TRACK SHIPMENT
   - Update status as it moves
   - Add tracking events
   ↓
9. MARK AS DELIVERED
   - Final status
   - Trigger notifications
```

### Quantity Tracking Logic

```php
// When creating shipment item
$invoiceItem = SalesInvoiceItem::find($id);
$quantityRemaining = $invoiceItem->quantity - $invoiceItem->quantity_shipped;

if ($quantityToShip > $quantityRemaining) {
    throw new Exception("Cannot ship more than remaining quantity");
}

// When confirming shipment
foreach ($shipment->items as $item) {
    $invoiceItem = $item->salesInvoiceItem;
    $invoiceItem->quantity_shipped += $item->quantity_to_ship;
    $invoiceItem->quantity_remaining = $invoiceItem->quantity - $invoiceItem->quantity_shipped;
    
    // Update status
    if ($invoiceItem->quantity_remaining == 0) {
        $invoiceItem->shipment_status = 'fully_shipped';
    } else if ($invoiceItem->quantity_shipped > 0) {
        $invoiceItem->shipment_status = 'partially_shipped';
    }
    
    $invoiceItem->save();
}
```

### Packing Validation Logic

```php
// Validate packing before confirming shipment
foreach ($shipment->items as $shipmentItem) {
    $quantityPacked = $shipmentItem->packingBoxItems()->sum('quantity');
    
    if ($quantityPacked != $shipmentItem->quantity_to_ship) {
        throw new Exception(
            "Item {$shipmentItem->product_name}: " .
            "Packed quantity ($quantityPacked) must equal " .
            "quantity to ship ({$shipmentItem->quantity_to_ship})"
        );
    }
}
```

---

## 📄 DOCUMENT GENERATION

### Commercial Invoice PDF

**Required Information:**
- Invoice number and date
- Exporter details (your company)
- Importer details (customer)
- Notify party (if different)
- Port of loading and discharge
- Incoterm and payment terms
- **Item list with:**
  - Description
  - HS Code
  - Country of Origin
  - Quantity
  - Unit Price
  - Total Value
- Subtotal, freight, insurance, total
- Declaration and signature

**Template Structure:**
```
┌────────────────────────────────────────┐
│        COMMERCIAL INVOICE              │
│                                        │
│ Invoice No: CI-2025-0001               │
│ Date: 2025-11-24                       │
│                                        │
│ EXPORTER:                              │
│ [Your Company Details]                 │
│                                        │
│ IMPORTER:                              │
│ [Customer Details]                     │
│                                        │
│ SHIPPING DETAILS:                      │
│ Port of Loading: Shanghai              │
│ Port of Discharge: Los Angeles         │
│ Incoterm: FOB Shanghai                 │
│                                        │
│ ITEMS:                                 │
│ ┌──┬───────┬──────┬────┬─────┬──────┐ │
│ │#│Product│HS    │Qty │Price│Total │ │
│ ├──┼───────┼──────┼────┼─────┼──────┤ │
│ │1│Widget │123456│100 │10.00│1000  │ │
│ └──┴───────┴──────┴────┴─────┴──────┘ │
│                                        │
│ Subtotal:    $1,000.00                 │
│ Freight:     $  200.00                 │
│ Insurance:   $   50.00                 │
│ TOTAL:       $1,250.00                 │
│                                        │
│ Declaration: [Standard text]           │
│ Signature: _______________             │
└────────────────────────────────────────┘
```

### Packing List PDF

**Required Information:**
- Shipment number and date
- Shipper and consignee
- Carrier and tracking number
- Total boxes, weight, volume
- **Box-by-box breakdown:**
  - Box number
  - Dimensions (L x W x H)
  - Weight (Gross/Net)
  - Volume
  - Contents (items and quantities)

**Template Structure:**
```
┌────────────────────────────────────────┐
│          PACKING LIST                  │
│                                        │
│ Shipment No: SHP-2025-0001             │
│ Date: 2025-11-24                       │
│                                        │
│ FROM: [Shipper]                        │
│ TO: [Consignee]                        │
│                                        │
│ Carrier: DHL                           │
│ Tracking: 1234567890                   │
│                                        │
│ SUMMARY:                               │
│ Total Boxes: 5                         │
│ Total Weight: 250.5 kg                 │
│ Total Volume: 1.25 m³                  │
│                                        │
│ BOX 1 of 5:                            │
│ Dimensions: 50 x 40 x 30 cm            │
│ Gross Weight: 52.5 kg                  │
│ Net Weight: 50.0 kg                    │
│ Volume: 0.06 m³                        │
│ Contents:                              │
│   - Widget A x 50 pcs                  │
│   - Widget B x 30 pcs                  │
│                                        │
│ BOX 2 of 5:                            │
│ [Similar structure]                    │
│ ...                                    │
└────────────────────────────────────────┘
```

---

## 🎨 USER INTERFACE DESIGN

### Shipment Creation Form

**Step 1: Basic Information**
- Shipment number (auto-generated)
- Shipment type (outgoing/incoming)
- Shipping method (air/sea/land/courier)
- Shipment date

**Step 2: Select Sales Invoices**
- Multi-select table of approved invoices
- Show invoice number, client, total, status
- Filter by client, date range, status
- Show items preview on selection

**Step 3: Select Items to Ship**
- Table grouped by invoice
- For each item show:
  - Product name/SKU
  - Ordered quantity
  - Already shipped
  - Remaining to ship
  - **Input: Quantity to ship in this shipment**
- Validation: Can't exceed remaining
- Show totals per invoice

**Step 4: Packing (Optional but Recommended)**
- Create packing boxes
- For each box:
  - Box number (auto-increment)
  - Box type (dropdown)
  - Dimensions (L x W x H)
  - Assign items from shipment
  - For each item: quantity in this box
- Visual indicator: Item packing progress
- Validation: All items must be fully packed

**Step 5: Shipping Details**
- Carrier (dropdown + custom)
- Tracking number
- Container/Vessel/Flight number
- Incoterm (dropdown)
- Origin/Destination ports
- Estimated dates
- Freight cost, insurance, other costs

**Step 6: Review & Confirm**
- Summary of all information
- List of invoices and items
- Packing summary (boxes)
- Total weight, volume, cost
- **Actions:**
  - Save as Draft
  - Confirm Shipment (locks it)
  - Generate Commercial Invoice
  - Generate Packing List

### Shipment View/Edit Page

**Tabs:**

1. **Overview**
   - Shipment details
   - Status timeline
   - Quick stats (boxes, weight, volume)
   - Actions (Confirm, Generate Docs, Track)

2. **Invoices**
   - List of linked sales invoices
   - Click to view invoice details
   - Show items from each invoice

3. **Items**
   - Table of all items in shipment
   - Grouped by invoice
   - Show quantities (ordered/shipped/remaining)
   - Show customs info (HS code, origin)

4. **Packing**
   - List of packing boxes
   - For each box: number, dimensions, weight, contents
   - Visual packing diagram (optional)
   - Add/Edit/Delete boxes (if not confirmed)

5. **Documents**
   - Commercial Invoice (Generate/View/Download)
   - Packing List (Generate/View/Download)
   - Bill of Lading
   - Other documents (upload)

6. **Tracking**
   - Tracking events timeline
   - Status updates
   - Add manual tracking event

7. **Costs**
   - Freight cost breakdown
   - Insurance
   - Other costs
   - Total shipping cost

### Sales Invoice - Shipments Tab

**Add to existing Sales Invoice view:**

**Tab: "Shipments"**
- List of all shipments for this invoice
- For each shipment:
  - Shipment number (link)
  - Shipment date
  - Status
  - Items shipped (count)
  - Quantity shipped
- Summary:
  - Total quantity ordered
  - Total quantity shipped
  - Remaining to ship
  - Shipment status (Not Shipped / Partially Shipped / Fully Shipped)

---

## 📊 REPORTS & ANALYTICS

### Shipment Reports

1. **Shipment Summary Report**
   - List of shipments
   - Filter by date range, status, carrier
   - Show totals: boxes, weight, volume, cost

2. **Invoice Shipment Status Report**
   - List of invoices
   - Show shipment progress for each
   - Highlight overdue shipments

3. **Packing Efficiency Report**
   - Average items per box
   - Average box utilization
   - Weight/volume analysis

4. **Carrier Performance Report**
   - On-time delivery rate by carrier
   - Average transit time
   - Cost analysis

### Dashboard Widgets

1. **Pending Shipments Widget**
   - Count of shipments in draft/preparing
   - Action: Create new shipment

2. **In-Transit Shipments Widget**
   - Count of shipments in transit
   - Map view (optional)

3. **Overdue Shipments Widget**
   - Count of delayed shipments
   - Alert icon

4. **Ready to Ship Widget**
   - Invoices fully packed and ready
   - Quick action to confirm

---

## 🔧 IMPLEMENTATION PLAN

### Phase 1: Database & Models (Week 1)

**Tasks:**
1. Create migrations:
   - `shipment_invoices` (pivot table)
   - `packing_boxes`
   - `packing_box_items`
   - `commercial_invoices`
   - Add columns to `sales_invoice_items`:
     - `quantity_shipped`
     - `quantity_remaining`
     - `shipment_status`
   - Modify `shipments` table (add new columns)
   - Modify `shipment_items` table (add new columns)

2. Create/Update models:
   - `ShipmentInvoice`
   - `PackingBox`
   - `PackingBoxItem`
   - `CommercialInvoice`
   - Update `Shipment` model
   - Update `ShipmentItem` model
   - Update `SalesInvoiceItem` model

3. Add relationships to all models

4. Create seeders for testing

**Deliverables:**
- All migrations ready
- All models created
- Relationships working
- Test data seeded

---

### Phase 2: Business Logic (Week 2)

**Tasks:**
1. Implement quantity tracking logic
2. Implement packing validation logic
3. Implement shipment confirmation logic
4. Create service classes:
   - `ShipmentService`
   - `PackingService`
   - `CommercialInvoiceService`

5. Add methods to models:
   - `Shipment::calculateTotals()`
   - `Shipment::confirm()`
   - `PackingBox::calculateVolume()`
   - `SalesInvoiceItem::updateShipmentStatus()`

6. Create observers:
   - `ShipmentObserver` (auto-calculate totals)
   - `PackingBoxObserver` (auto-calculate totals)

**Deliverables:**
- All business logic implemented
- Service classes created
- Unit tests written
- Validation working

---

### Phase 3: Filament Resources (Week 3)

**Tasks:**
1. Refactor `ShipmentResource`:
   - Update form with wizard steps
   - Add invoice selection step
   - Add item selection step
   - Add packing step
   - Update table columns

2. Create Relation Managers:
   - `InvoicesRelationManager` (for Shipment)
   - `ItemsRelationManager` (for Shipment)
   - `PackingBoxesRelationManager` (for Shipment)
   - `ShipmentsRelationManager` (for SalesInvoice)

3. Create custom Filament pages:
   - `PackingPage` (visual packing interface)
   - `CommercialInvoicePage` (generate/view)

4. Add actions:
   - `ConfirmShipmentAction`
   - `GenerateCommercialInvoiceAction`
   - `GeneratePackingListAction`
   - `AddTrackingEventAction`

**Deliverables:**
- Shipment Resource fully refactored
- All relation managers working
- Custom pages created
- Actions implemented

---

### Phase 4: Document Generation (Week 4)

**Tasks:**
1. Install PDF library (DomPDF or TCPDF)

2. Create PDF templates:
   - Commercial Invoice template
   - Packing List template

3. Create generator classes:
   - `CommercialInvoiceGenerator`
   - `PackingListGenerator`

4. Add preview functionality

5. Add download/email functionality

**Deliverables:**
- PDF generation working
- Templates professional
- Preview working
- Download/email working

---

### Phase 5: Testing & Polish (Week 5)

**Tasks:**
1. End-to-end testing
2. Create test scenarios:
   - Single invoice shipment
   - Multi-invoice shipment
   - Partial shipment
   - Multiple shipments for same invoice
3. Fix bugs
4. Polish UI/UX
5. Add loading states
6. Add validation messages
7. Create user documentation

**Deliverables:**
- All tests passing
- Bugs fixed
- UI polished
- Documentation complete

---

## 📝 MIGRATION STRATEGY

### Handling Existing Data

**Option 1: Keep Old System (Recommended)**
- Don't migrate old shipments
- Old shipments remain read-only
- New shipments use new system
- Add flag: `legacy_shipment` boolean

**Option 2: Migrate (Complex)**
- Create migration script
- Convert old shipments to new structure
- Create dummy packing boxes
- Link to invoices (if possible)

**Recommendation:** Option 1 - Clean break, less risk

---

## 🎯 SUCCESS METRICS

After implementation, the system should support:

- ✅ Multiple invoices per shipment
- ✅ Quantity tracking (ordered vs shipped vs remaining)
- ✅ Partial shipments
- ✅ Multiple shipments per invoice
- ✅ Master box packing system
- ✅ Commercial invoice generation
- ✅ Professional packing list
- ✅ Customs information (HS codes, origin)
- ✅ Full audit trail
- ✅ Document management
- ✅ Tracking events

---

## 💡 ADDITIONAL FEATURES (Future)

### Advanced Features to Consider:

1. **Barcode Scanning**
   - Generate barcodes for boxes
   - Scan items during packing
   - Verify packing accuracy

2. **Weight/Volume Optimization**
   - Suggest optimal box packing
   - Calculate best box sizes
   - Minimize shipping cost

3. **Integration with Carriers**
   - Auto-create shipping labels
   - Real-time tracking updates
   - Rate shopping

4. **Customs Integration**
   - Auto-submit customs declarations
   - Track customs clearance
   - Calculate duties/taxes

5. **Mobile App**
   - Warehouse staff can pack using mobile
   - Scan barcodes
   - Update shipment status

6. **Advanced Analytics**
   - Shipping cost analysis
   - Carrier performance
   - Packing efficiency
   - Delivery time analysis

---

## 🚀 READY TO START?

This plan provides a complete roadmap for refactoring the Shipments system into a professional, enterprise-grade logistics solution.

**Estimated Timeline:** 5 weeks  
**Complexity:** High  
**Impact:** Very High  

**Next Steps:**
1. Review and approve this plan
2. Start with Phase 1 (Database & Models)
3. Iterate based on feedback

Let me know if you want to proceed or if you have any questions/modifications!
