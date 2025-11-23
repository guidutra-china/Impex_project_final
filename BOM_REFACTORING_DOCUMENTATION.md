# BOM Refactoring - Products as Components

## Overview
Successfully refactored the Bill of Materials (BOM) system to use **Products** as components instead of a separate **Components** entity. This simplifies the data model and allows any product to be used as a component in another product's BOM.

---

## Changes Made

### 1. **Database Changes**
- ✅ Removed `components` table
- ✅ Renamed `bom_items.component_id` → `bom_items.component_product_id`
- ✅ Updated foreign key to reference `products` table
- ✅ Migration: `2025_11_23_010000_rename_component_id_to_component_product_id_in_bom_items.php`

### 2. **Model Changes**

#### **BomItem Model** (`app/Models/BomItem.php`)
- ✅ Changed `component_id` → `component_product_id` in fillable
- ✅ Changed `component()` → `componentProduct()` relationship
- ✅ Updated cost calculation to use `componentProduct->calculated_selling_price` or `componentProduct->price`

#### **Product Model** (`app/Models/Product.php`)
- ✅ Changed `components()` → `componentProducts()` (BOM items)
- ✅ Added `usedInProducts()` (reverse BOM - where this product is used as component)
- ✅ Self-referencing relationship via `bom_items` pivot table

### 3. **Filament Resources**

#### **BomItemsRelationManager**
- ✅ Changed all `component` references to `componentProduct`
- ✅ Updated table columns: `component.code` → `componentProduct.sku`
- ✅ Updated table columns: `component.name` → `componentProduct.name`
- ✅ Updated table columns: `component.type` → `componentProduct.category.name`
- ✅ Updated form field: `component_id` → `component_product_id`
- ✅ Added circular reference prevention (cannot add product to itself)
- ✅ Updated relationship query to filter active products only

#### **WhatIfScenariosRelationManager**
- ✅ Changed `component_id` → `component_product_id` in form
- ✅ Updated relationship loading: `with('component')` → `with('componentProduct')`
- ✅ Updated cost adjustments array key: `$adjustment['component_id']` → `$adjustment['component_product_id']`

#### **BomVersionsRelationManager**
- ✅ Updated relationship loading: `with('bomVersionItems.component')` → `with('bomVersionItems.componentProduct')`

#### **CompareBomVersions Page**
- ✅ Updated all relationship loading to use `componentProduct`
- ✅ Changed `component_id` → `component_product_id` in comparison logic
- ✅ Changed `component_code` → `component_sku` in comparison data
- ✅ Changed `allComponentIds` → `allComponentProductIds`

### 4. **Blade Views**

#### **bom-pdf.blade.php** (PDF Export)
- ✅ Changed `$item->component->code` → `$item->componentProduct->sku`
- ✅ Changed `$item->component->name` → `$item->componentProduct->name`

#### **bom-version-pdf.blade.php** (Version PDF Export)
- ✅ Changed `$item->component->code` → `$item->componentProduct->sku`
- ✅ Changed `$item->component->name` → `$item->componentProduct->name`

#### **bom-version-details.blade.php** (Version Details Modal)
- ✅ Changed `$item->component->code` → `$item->componentProduct->sku`
- ✅ Changed `$item->component->name` → `$item->componentProduct->name`

#### **what-if-scenario-details.blade.php** (What-If Scenario Modal)
- ✅ Changed `$componentId` → `$componentProductId`
- ✅ Changed `firstWhere('component_id')` → `firstWhere('component_product_id')`
- ✅ Changed `$bomItem->component->name` → `$bomItem->componentProduct->name`

### 5. **Removed Files**
- ✅ `app/Filament/Resources/Components/ComponentResource.php`
- ✅ `app/Filament/Resources/Components/Pages/CreateComponent.php`
- ✅ `app/Filament/Resources/Components/Pages/EditComponent.php`
- ✅ `app/Filament/Resources/Components/Pages/ListComponents.php`
- ✅ `app/Filament/Resources/Components/Schemas/ComponentForm.php`
- ✅ `app/Filament/Resources/Components/Tables/ComponentsTable.php`
- ✅ `app/Models/Component.php`
- ✅ `database/migrations/2025_11_13_224837_create_components_table.php`
- ✅ `database/migrations/2025_11_15_030046_add_currency_to_components_table.php`

---

## Benefits

### **1. Simplified Data Model**
- Only one entity (`Product`) instead of two (`Product` + `Component`)
- Less duplication of data (price, supplier, specs, etc.)
- Easier to maintain and understand

### **2. Flexibility**
- Any product can be used as a component
- Products can have multiple levels (product → sub-assembly → raw material)
- Easy to track where a product is used (`usedInProducts()` relationship)

### **3. Consistency**
- Prices always synchronized (uses product's price directly)
- No need to manually sync component prices with product prices
- Single source of truth for product information

### **4. Better Traceability**
- Can see complete product hierarchy
- Reverse BOM shows where a product is used
- Better for supply chain management

---

## How to Use

### **Adding Components to a Product:**
1. Go to **Products** → Select a product
2. Click on **Bill of Materials (BOM)** tab
3. Click **Add Component**
4. Select a **Product** to use as component (cannot select itself)
5. Enter quantity, unit of measure, waste factor, etc.
6. Save

### **Viewing Where a Product is Used:**
```php
$product = Product::find(1);
$usedIn = $product->usedInProducts; // Products that use this product as component
```

### **Viewing Product Components:**
```php
$product = Product::find(1);
$components = $product->componentProducts; // Products used as components in this product
```

### **Preventing Circular References:**
The system automatically prevents adding a product to itself. Future enhancement could add deeper circular reference detection (A → B → C → A).

---

## Migration Instructions

### **For Fresh Install:**
```bash
php artisan migrate
```

### **For Existing Data:**
If you have existing `components` data, you need to:
1. **Migrate components to products:**
   ```sql
   INSERT INTO products (name, sku, price, currency_id, status, created_at, updated_at)
   SELECT name, code, price, currency_id, 'active', created_at, updated_at
   FROM components;
   ```

2. **Update bom_items references:**
   ```sql
   UPDATE bom_items
   SET component_product_id = (
       SELECT p.id FROM products p
       INNER JOIN components c ON c.code = p.sku
       WHERE c.id = bom_items.component_id
   );
   ```

3. **Run the migration:**
   ```bash
   php artisan migrate
   ```

---

## Testing Checklist

- [ ] Create a product
- [ ] Add another product as component in BOM
- [ ] Verify costs are calculated correctly
- [ ] Export BOM to PDF
- [ ] Create BOM version
- [ ] Compare BOM versions
- [ ] Create What-If scenario with component adjustments
- [ ] Verify circular reference prevention (cannot add product to itself)
- [ ] Check reverse BOM (`usedInProducts()`)

---

## Future Enhancements

1. **Deep Circular Reference Detection:**
   - Prevent A → B → C → A scenarios
   - Add validation at form level

2. **BOM Hierarchy Visualization:**
   - Tree view of product structure
   - Visual representation of multi-level BOMs

3. **Component Substitution:**
   - Define alternative components
   - Automatic cost comparison

4. **BOM Templates:**
   - Save common BOM structures
   - Quick apply to new products

5. **Mass BOM Updates:**
   - Update component prices across all BOMs
   - Bulk quantity adjustments

---

## Notes

- ✅ All syntax checks passed
- ✅ No references to old `Component` model found
- ✅ All views updated to use `componentProduct`
- ✅ Relationships properly configured
- ✅ Circular reference prevention in place

**Status:** ✅ **COMPLETE AND READY FOR TESTING**
