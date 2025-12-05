# Quick Start Guide - Shipment Management System

## 🚀 Getting Started

This guide will help you quickly understand and use the Shipment Management System.

---

## 📋 Common Tasks

### 1. Create a Shipment from Proforma Invoice

**Via UI:**
1. Navigate to **Shipments** → **Create**
2. Select **Customer**
3. Link **Proforma Invoice(s)**
4. Click **Auto-Load Containers** (recommended)
5. Review and **Confirm**

**Via Code:**
```php
use App\Services\Shipment\ContainerLoadingService;

$shipment = Shipment::create([
    'customer_id' => $customerId,
    'shipping_method' => 'sea',
    'status' => 'draft',
]);

$shipment->shipmentInvoices()->create([
    'proforma_invoice_id' => $piId,
]);

// Auto-load containers
$service = app(ContainerLoadingService::class);
$result = $service->autoLoadBestFit($shipment, '40HC');

if ($result['success']) {
    $shipment->update(['status' => 'confirmed']);
}
```

---

### 2. Add Items to Container Manually

**Via UI:**
1. Open **Shipment** → **Containers** tab
2. Click on a **Container**
3. Click **Add Item**
4. Select **Proforma Invoice Item**
5. Enter **Quantity**
6. Click **Save**

**Via Code:**
```php
use App\Services\ShipmentContainerService;

$service = app(ShipmentContainerService::class);

$service->addItemToContainer($container, [
    'proforma_invoice_item_id' => $piItemId,
    'quantity_to_ship' => 100,
]);
```

---

### 3. Calculate Optimal Packing

**Via Code:**
```php
use App\Services\Shipment\PackagingCalculatorService;

$calculator = app(PackagingCalculatorService::class);

// Get all available box types
$boxTypes = PackingBoxType::where('is_active', true)->get();

// Calculate optimal packing
$optimal = $calculator->calculateOptimalPacking(
    $product,
    $quantity,
    $boxTypes
);

// Get recommendation
$recommended = $optimal['recommended'];

echo "Use {$recommended['box_type']->name}";
echo "Need {$recommended['boxes_needed']} boxes";
echo "Utilization: {$recommended['utilization']}%";
```

---

### 4. Validate Container Before Sealing

**Via Code:**
```php
use App\Services\ShipmentValidationService;

$service = app(ShipmentValidationService::class);

try {
    // Validate sealing requirements
    $service->validateContainerSealing($container);
    
    // Check for warnings
    $warnings = $service->checkCapacityWarnings($container);
    
    if (!empty($warnings)) {
        foreach ($warnings as $warning) {
            echo "{$warning['level']}: {$warning['message']}\n";
        }
    }
    
    // Seal container
    $container->update(['status' => 'sealed']);
    
} catch (ValidationException $e) {
    // Handle errors
    foreach ($e->errors() as $error) {
        echo "Error: {$error}\n";
    }
}
```

---

### 5. Get Container Optimization Suggestions

**Via Code:**
```php
use App\Services\Shipment\ContainerCapacityValidator;

$validator = app(ContainerCapacityValidator::class);

$suggestions = $validator->getOptimizationSuggestions($container);

foreach ($suggestions as $suggestion) {
    echo "[{$suggestion['priority']}] {$suggestion['message']}\n";
    
    if ($suggestion['type'] === 'add_more_items') {
        echo "Remaining capacity:\n";
        echo "- Weight: {$suggestion['remaining_capacity']['weight']}kg\n";
        echo "- Volume: {$suggestion['remaining_capacity']['volume']}m³\n";
    }
}
```

---

## 🎯 Best Practices

### ✅ DO

1. **Use Auto-Load** for initial container assignment
2. **Check warnings** before sealing containers
3. **Validate** before confirming shipments
4. **Use optimal packing** calculations for cost efficiency
5. **Review suggestions** for optimization opportunities

### ❌ DON'T

1. **Don't seal** containers without validation
2. **Don't ignore** capacity warnings
3. **Don't manually calculate** CBM (use service)
4. **Don't overload** containers (respect safety limits)
5. **Don't skip** quantity availability checks

---

## 📊 Understanding Utilization

### Weight Utilization

```
Weight Utilization = (Current Weight / Max Weight) × 100%
```

**Targets:**
- ✅ **70-95%**: Optimal range
- ⚠️ **90-95%**: Warning (approaching limit)
- 🔴 **95-100%**: Critical (very close to limit)
- ℹ️ **< 50%**: Underutilized (consider consolidation)

### Volume Utilization

```
Volume Utilization = (Current Volume / Max Volume) × 100%
```

**Same targets as weight utilization**

### Overall Utilization

```
Overall = (Weight Utilization + Volume Utilization) / 2
```

**Goal:** Achieve 70%+ overall utilization for cost efficiency

---

## 🔍 Common Scenarios

### Scenario 1: Container is Overloaded

**Problem:** "Weight exceeded. Maximum: 25000kg, Total: 26500kg"

**Solutions:**
1. Remove some items: `$service->removeItemFromContainer($containerItem)`
2. Use larger container: Change container type to 40HC
3. Split items: Create new container and distribute items

---

### Scenario 2: Low Utilization

**Problem:** Container is only 45% utilized

**Solutions:**
1. Add more items from other PIs
2. Use smaller container type
3. Consolidate with another container

**Code:**
```php
$suggestions = $validator->getOptimizationSuggestions($container);

if ($suggestions[0]['type'] === 'downsize_container') {
    // Change to smaller container type
    $container->update(['container_type_id' => $smallerTypeId]);
}
```

---

### Scenario 3: Unbalanced Weight Distribution

**Problem:** Top 3 items account for 80% of weight

**Solution:** Redistribute heavy items across multiple containers

**Code:**
```php
$balance = $validator->validateBalance($container);

if (!$balance['is_balanced']) {
    // Get heavy items
    $heavyItems = $container->items()
        ->orderByDesc('total_weight')
        ->take(3)
        ->get();
    
    // Move to different containers
    foreach ($heavyItems as $item) {
        $service->moveItemToContainer($item, $targetContainer);
    }
}
```

---

## 📈 Monitoring & Metrics

### Get Loading Efficiency

```php
use App\Services\Shipment\ContainerLoadingService;

$service = app(ContainerLoadingService::class);
$metrics = $service->calculateLoadingEfficiency($shipment);

echo "Total Containers: {$metrics['total_containers']}\n";
echo "Average Utilization: {$metrics['average_utilization']}%\n";
echo "Total Weight: {$metrics['total_weight']}kg\n";
echo "Total Volume: {$metrics['total_volume']}m³\n";
```

### Get Validation Report

```php
use App\Services\ShipmentValidationService;

$service = app(ShipmentValidationService::class);
$report = $service->getValidationReport($shipment);

if ($report['is_valid']) {
    echo "✅ Shipment is valid\n";
} else {
    echo "❌ Errors found:\n";
    foreach ($report['errors'] as $error) {
        echo "- {$error}\n";
    }
}

if (!empty($report['warnings'])) {
    echo "⚠️ Warnings:\n";
    foreach ($report['warnings'] as $warning) {
        echo "- {$warning}\n";
    }
}
```

---

## 🆘 Troubleshooting

### Error: "Container must have at least one item to be sealed"

**Cause:** Trying to seal empty container

**Solution:** Add items before sealing

---

### Error: "Insufficient quantity. Remaining: 50, Requested: 100"

**Cause:** Not enough items available in Proforma Invoice

**Solution:** 
1. Check `quantity_remaining` on PI item
2. Reduce requested quantity
3. Verify items haven't been shipped already

---

### Warning: "Weight capacity at 92% - approaching limit"

**Cause:** Container is almost full

**Solution:**
1. This is just a warning, can proceed
2. Consider not adding more items
3. Review if current load is optimal

---

## 📚 Additional Resources

- [Complete Documentation](./SHIPMENT_SYSTEM.md)
- [API Reference](./SHIPMENT_SYSTEM.md#api-reference)
- [Workflows](./SHIPMENT_SYSTEM.md#workflows)

---

## 💡 Tips & Tricks

### Tip 1: Use Auto-Load for Speed

Instead of manually assigning items, use auto-load:

```php
// ❌ Slow: Manual assignment
foreach ($items as $item) {
    $service->addItemToContainer($container, $item);
}

// ✅ Fast: Auto-load
$service->autoLoadBestFit($shipment, '40HC');
```

### Tip 2: Check Suggestions Regularly

```php
// Get suggestions for all containers
foreach ($shipment->containers as $container) {
    $suggestions = $validator->getOptimizationSuggestions($container);
    
    if (!empty($suggestions)) {
        // Show to user or log
    }
}
```

### Tip 3: Calculate CBM Correctly

```php
// ❌ Wrong
$cbm = ($length * $width * $height) / 1000000;

// ✅ Correct
$cbm = $calculator->calculateCBM($length, $width, $height);
```

### Tip 4: Validate Early and Often

```php
// Validate before each major operation
$report = $service->getValidationReport($shipment);

if (!$report['is_valid']) {
    // Fix errors before proceeding
    return;
}

// Proceed with operation
```

---

**Happy Shipping! 🚢**
