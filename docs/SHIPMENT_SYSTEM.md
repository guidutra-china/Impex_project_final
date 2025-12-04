# Shipment Management System - Complete Documentation

## Table of Contents

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Services](#services)
4. [Workflows](#workflows)
5. [Best Practices](#best-practices)
6. [API Reference](#api-reference)

---

## Overview

The Shipment Management System is a comprehensive solution for managing international shipments, including:

- **Proforma Invoice Management**: Track customer orders and items
- **Container Loading**: Optimize container utilization
- **Packing Management**: Manage boxes and packaging
- **Validation & Calculations**: Automatic CBM calculations, capacity validation
- **Shipment Tracking**: Full lifecycle tracking from draft to delivered

### Key Features

✅ **Automated Calculations**
- CBM (Cubic Meter) calculations
- Weight and volume tracking
- Freight class determination
- Optimal packing strategies

✅ **Smart Validations**
- Container capacity validation (weight & volume)
- Quantity availability checks
- Safety limit enforcement
- Balance distribution validation

✅ **Optimization**
- Auto-load algorithms (Best-Fit, Weight-Balanced)
- Container type suggestions
- Packing optimization
- Utilization metrics

✅ **User Experience**
- Real-time validation feedback
- Warning system (Info/Warning/Critical)
- Comprehensive error messages
- Optimization suggestions

---

## Architecture

### Database Schema

```
proforma_invoices
├── id
├── invoice_number
├── customer_id
├── currency_id
├── status
└── items (relationship)

proforma_invoice_items
├── id
├── proforma_invoice_id
├── product_id
├── quantity
├── quantity_shipped
├── quantity_remaining (calculated)
├── unit_price
├── commission_percent
└── commission_type

shipments
├── id
├── shipment_number
├── customer_id
├── status
├── shipping_method
├── etd / eta
└── containers (relationship)

shipment_containers
├── id
├── shipment_id
├── container_type_id
├── container_number
├── current_weight
├── current_volume
├── max_weight
├── max_volume
└── status

container_types
├── id
├── name (20GP, 40GP, 40HC)
├── length / width / height
└── max_weight

packing_box_types
├── id
├── name
├── length / width / height
├── max_weight
└── cost
```

### Service Layer

```
app/Services/
├── Shipment/
│   ├── PackingService.php
│   ├── ContainerLoadingService.php
│   ├── PackingBoxTypeService.php
│   ├── PackagingCalculatorService.php
│   └── ContainerCapacityValidator.php
├── ShipmentContainerService.php
└── ShipmentValidationService.php
```

---

## Services

### 1. PackingService

Manages packing boxes and their items.

**Key Methods:**

```php
// Create a packing box
$box = $packingService->createPackingBox($shipmentId, [
    'box_type_id' => 1,
    'box_number' => 'BOX-001',
]);

// Add items to box
$packingService->addItemToBox($box, $piItem, $quantity);

// Auto-pack items
$result = $packingService->autoPackItems($shipment, $boxTypeId);

// Seal box
$packingService->sealBox($box);
```

**Features:**
- ✅ Create/Update/Delete boxes
- ✅ Add/Remove items
- ✅ Auto-pack distribution
- ✅ Seal/Unseal boxes
- ✅ Volume calculations
- ✅ Validation checks

---

### 2. ContainerLoadingService

Optimizes container loading with multiple algorithms.

**Key Methods:**

```php
// Suggest container types
$suggestions = $service->suggestContainerTypes($shipment);

// Auto-load with Best-Fit algorithm
$result = $service->autoLoadBestFit($shipment, '40HC');

// Auto-load with Weight-Balanced algorithm
$result = $service->autoLoadWeightBalanced($shipment, 3);

// Calculate loading efficiency
$metrics = $service->calculateLoadingEfficiency($shipment);
```

**Algorithms:**

**Best-Fit Algorithm:**
- Minimizes wasted space
- Fills each container to maximum capacity
- Optimal for cost reduction

**Weight-Balanced Algorithm:**
- Distributes weight evenly across containers
- Prevents overloading
- Optimal for safety and handling

**Features:**
- ✅ Multiple loading strategies
- ✅ Container type recommendations
- ✅ Efficiency metrics
- ✅ Automatic distribution

---

### 3. PackagingCalculatorService

Performs all packaging-related calculations.

**Key Methods:**

```php
// Calculate CBM from dimensions (cm)
$cbm = $service->calculateCBM($length, $width, $height);

// Calculate box capacity
$capacity = $service->calculateBoxCapacity($product, $boxType);
// Returns: max_quantity, volume_utilization, weight_utilization

// Calculate boxes needed
$boxes = $service->calculateBoxesNeeded($product, $boxType, $quantity);
// Returns: boxes_needed, items_per_box, last_box_quantity

// Calculate container capacity
$capacity = $service->calculateContainerCapacity($boxType, $containerType);
// Returns: max_boxes, utilization, limiting_factor

// Calculate optimal packing
$optimal = $service->calculateOptimalPacking($product, $quantity, $boxTypes);
// Returns: recommended, alternatives, all_options

// Calculate freight class
$freight = $service->calculateFreightClass($weight, $volume);
// Returns: density, class, description
```

**Use Cases:**

1. **Product Packaging**: Determine optimal box type for a product
2. **Container Planning**: Calculate how many boxes fit in a container
3. **Cost Estimation**: Compare different packing strategies
4. **Freight Pricing**: Determine freight class based on density

---

### 4. ContainerCapacityValidator

Validates container capacity and provides optimization suggestions.

**Key Methods:**

```php
// Validate if items fit
$validation = $validator->validateItemsFit($container, $items);
// Returns: can_fit, issues, warnings, metrics

// Validate weight balance
$balance = $validator->validateBalance($container);
// Returns: is_balanced, warnings

// Validate safety limits
$safety = $validator->validateSafetyLimits($container);
// Returns: is_safe, issues

// Get optimization suggestions
$suggestions = $validator->getOptimizationSuggestions($container);
// Returns: array of suggestions with priorities
```

**Validation Levels:**

- **Error**: Cannot proceed (e.g., weight exceeded)
- **Warning**: Can proceed but not optimal (e.g., 90% capacity)
- **Info**: Informational (e.g., low utilization)

**Suggestions:**

- `add_more_items`: Container has available capacity
- `downsize_container`: Container is underutilized
- `split_container`: Container is at capacity
- `rebalance`: Weight distribution is uneven

---

### 5. ShipmentValidationService

Validates shipment integrity and provides comprehensive reports.

**Key Methods:**

```php
// Validate container sealing
$service->validateContainerSealing($container);

// Validate shipment confirmation
$service->validateShipmentConfirmation($shipment);

// Validate quantity addition
$service->validateQuantityAddition($container, $piItem, $quantity);

// Check capacity warnings
$warnings = $service->checkCapacityWarnings($container);

// Get validation report
$report = $service->getValidationReport($shipment);
// Returns: is_valid, errors, warnings, info
```

**Warning System:**

```php
[
    'type' => 'weight',
    'level' => 'warning',  // info, warning, critical
    'message' => 'Weight capacity at 92% - approaching limit',
    'utilization' => 92.5,
]
```

---

## Workflows

### Workflow 1: Create Shipment from Proforma Invoice

```php
// 1. Create shipment
$shipment = Shipment::create([
    'customer_id' => $customerId,
    'shipping_method' => 'sea',
    'status' => 'draft',
]);

// 2. Link Proforma Invoice
$shipment->shipmentInvoices()->create([
    'proforma_invoice_id' => $piId,
]);

// 3. Suggest container types
$suggestions = app(ContainerLoadingService::class)
    ->suggestContainerTypes($shipment);

// 4. Auto-load containers
$result = app(ContainerLoadingService::class)
    ->autoLoadBestFit($shipment, '40HC');

// 5. Validate shipment
$report = app(ShipmentValidationService::class)
    ->getValidationReport($shipment);

// 6. Confirm shipment (if valid)
if ($report['is_valid']) {
    $shipment->update(['status' => 'confirmed']);
}
```

---

### Workflow 2: Optimize Packing Strategy

```php
// 1. Get product and quantity
$product = Product::find($productId);
$quantity = 1000;

// 2. Get available box types
$boxTypes = PackingBoxType::where('is_active', true)->get();

// 3. Calculate optimal packing
$calculator = app(PackagingCalculatorService::class);
$optimal = $calculator->calculateOptimalPacking($product, $quantity, $boxTypes);

// 4. Review recommendation
$recommended = $optimal['recommended'];
// {
//     'box_type' => PackingBoxType,
//     'boxes_needed' => 50,
//     'items_per_box' => 20,
//     'total_weight' => 1000.0,
//     'total_volume' => 5.5,
//     'utilization' => 85.5,
//     'cost_estimate' => 500.0,
// }

// 5. Compare alternatives
$alternatives = $optimal['alternatives'];
```

---

### Workflow 3: Validate and Optimize Container

```php
// 1. Get container
$container = ShipmentContainer::find($containerId);

// 2. Check capacity warnings
$warnings = app(ShipmentValidationService::class)
    ->checkCapacityWarnings($container);

// 3. Validate balance
$balance = app(ContainerCapacityValidator::class)
    ->validateBalance($container);

// 4. Get optimization suggestions
$suggestions = app(ContainerCapacityValidator::class)
    ->getOptimizationSuggestions($container);

// 5. Apply suggestions
foreach ($suggestions as $suggestion) {
    if ($suggestion['type'] === 'add_more_items') {
        // Add more items to container
    } elseif ($suggestion['type'] === 'split_container') {
        // Create new container and split items
    }
}
```

---

## Best Practices

### 1. Always Validate Before Sealing

```php
try {
    $service->validateContainerSealing($container);
    $container->update(['status' => 'sealed']);
} catch (ValidationException $e) {
    // Handle validation errors
    return back()->withErrors($e->errors());
}
```

### 2. Use Auto-Load for Efficiency

```php
// Instead of manually assigning items
$result = app(ContainerLoadingService::class)
    ->autoLoadBestFit($shipment, '40HC');

if ($result['success']) {
    // Containers created and loaded automatically
}
```

### 3. Check Warnings Before Errors

```php
// Check warnings first (non-blocking)
$warnings = $service->checkCapacityWarnings($container);

if (!empty($warnings)) {
    // Show warnings to user
    foreach ($warnings as $warning) {
        if ($warning['level'] === 'critical') {
            // Highlight critical warnings
        }
    }
}

// Then validate (blocking)
try {
    $service->validateQuantityAddition($container, $piItem, $quantity);
} catch (ValidationException $e) {
    // Handle errors
}
```

### 4. Use Optimization Suggestions

```php
$suggestions = app(ContainerCapacityValidator::class)
    ->getOptimizationSuggestions($container);

// Sort by priority
usort($suggestions, fn($a, $b) => 
    ($a['priority'] === 'high' ? 0 : 1) <=> 
    ($b['priority'] === 'high' ? 0 : 1)
);

// Show top suggestion to user
$topSuggestion = $suggestions[0] ?? null;
```

### 5. Calculate CBM Correctly

```php
// ❌ Wrong: Using meters directly
$cbm = $length * $width * $height;

// ✅ Correct: Convert cm to m
$cbm = $calculator->calculateCBM($length, $width, $height);
// Automatically converts cm → m and calculates
```

---

## API Reference

### PackagingCalculatorService

#### `calculateCBM(float $length, float $width, float $height): float`

Calculates CBM (Cubic Meter) from dimensions in centimeters.

**Parameters:**
- `$length`: Length in cm
- `$width`: Width in cm
- `$height`: Height in cm

**Returns:** Volume in m³

**Example:**
```php
$cbm = $calculator->calculateCBM(100, 50, 30);
// Returns: 0.15 m³
```

---

#### `calculateBoxCapacity(Product $product, PackingBoxType $boxType): array`

Calculates how many items fit in a box.

**Returns:**
```php
[
    'max_quantity' => 20,
    'volume_utilization' => 85.5,
    'weight_utilization' => 78.2,
    'overall_utilization' => 81.85,
]
```

---

#### `calculateOptimalPacking(Product $product, int $quantity, array $boxTypes): array`

Calculates the optimal packing strategy.

**Returns:**
```php
[
    'recommended' => [...],  // Best option
    'alternatives' => [...], // Top 3 alternatives
    'all_options' => [...],  // All options sorted by utilization
]
```

---

### ContainerCapacityValidator

#### `validateItemsFit(ShipmentContainer $container, Collection $items): array`

Validates if items can fit in container.

**Returns:**
```php
[
    'can_fit' => true,
    'issues' => [],
    'warnings' => [
        [
            'type' => 'weight_high',
            'message' => 'Weight capacity high: 22500kg (90%+ of limit)',
            'utilization' => 90.5,
        ]
    ],
    'metrics' => [
        'weight' => [
            'current' => 20000,
            'additional' => 2500,
            'new_total' => 22500,
            'limit' => 25000,
            'utilization' => 90.0,
            'remaining' => 2500,
        ],
        'volume' => [...],
    ],
]
```

---

#### `getOptimizationSuggestions(ShipmentContainer $container): array`

Gets optimization suggestions for container.

**Returns:**
```php
[
    [
        'type' => 'add_more_items',
        'priority' => 'high',
        'message' => 'Container can accommodate more items',
        'remaining_capacity' => [
            'weight' => 5000.0,
            'volume' => 10.5,
        ],
    ],
]
```

---

### ShipmentValidationService

#### `getValidationReport(Shipment $shipment): array`

Gets comprehensive validation report.

**Returns:**
```php
[
    'is_valid' => true,
    'errors' => [],
    'warnings' => [
        'Container CONT-001: Weight capacity at 92% - approaching limit',
    ],
    'info' => [
        'Container CONT-002: Volume utilization is low (45%) - consider consolidation',
    ],
]
```

---

## Troubleshooting

### Issue: "Weight exceeded" error

**Solution:**
1. Check current container weight: `$container->current_weight`
2. Check maximum weight: `$container->containerType->max_weight`
3. Options:
   - Remove some items
   - Use a larger container type
   - Split into multiple containers

---

### Issue: Low utilization warnings

**Solution:**
1. Get suggestions: `$validator->getOptimizationSuggestions($container)`
2. Follow recommendations:
   - Add more items if available
   - Use smaller container type
   - Consolidate with other containers

---

### Issue: Items don't fit in box

**Solution:**
1. Calculate capacity: `$calculator->calculateBoxCapacity($product, $boxType)`
2. If `max_quantity` is 0:
   - Product is too large for this box
   - Use a larger box type
   - Check product dimensions

---

## Support

For questions or issues:
- Check this documentation first
- Review code comments in service files
- Contact the development team

---

**Last Updated:** December 2025  
**Version:** 1.0.0
