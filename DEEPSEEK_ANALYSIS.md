# Filament V4 RelationManager Issue - DeepSeek Analysis

## Context
- Framework: Laravel 12.39.0
- Filament: V4
- PHP: 8.4.15
- Issue: Livewire component not found for ShipmentContainersRelationManager

## Error
```
Livewire\Exceptions\ComponentNotFoundException:
Unable to find component: [app.filament.resources.shipments.relation-managers.shipment-containers-relation-manager]
```

## Key Differences Between Working and Broken RelationManagers

### Working: ItemsRelationManager
1. Has `$navigationIcon` property: `Heroicon::OutlinedCube`
2. Has `protected ShipmentRepository $repository` property
3. Uses `->query($this->repository->getItemsQuery(...))` in table()
4. Uses standard Filament Actions
5. Uses `BulkActionGroup` with `DeleteBulkAction`
6. Has complex form with reactive Select fields
7. Has custom `CreateAction` with `using()` callback
8. Has custom `EditAction` and `DeleteAction` with `using()` callbacks
9. Uses `->headerActions()`, `->recordActions()`, `->toolbarActions()`

### Broken: ShipmentContainersRelationManager
1. Missing `$navigationIcon` property
2. No protected properties for repositories/services
3. Does NOT use `->query()` in table()
4. Uses custom Actions: `SealContainerAction`, `UnsealContainerAction`
5. Uses standard Actions: `CreateAction`, `EditAction`, `DeleteAction`
6. Empty `bulkActions([])`
7. Simple form with basic fields
8. No custom callbacks in Actions
9. Uses `->actions()` instead of `->recordActions()`

## Hypothesis
The issue might be related to:
1. Missing `$navigationIcon` - Could affect component registration
2. Custom Actions (SealContainerAction, UnsealContainerAction) - Could interfere with Livewire discovery
3. Not using `->query()` in table() - Could affect how the component is initialized
4. Empty bulkActions - Could cause component discovery issues

## Solution Approach
Apply the working pattern to the broken RelationManager:
1. Add `$navigationIcon` property
2. Add `protected` properties for services/repositories
3. Use `->query()` in table() method
4. Refactor custom Actions to be more standard
5. Use `->recordActions()` instead of `->actions()`
6. Add `->headerActions()` for CreateAction
7. Add `->toolbarActions()` with BulkActionGroup

## Files to Modify
- `app/Filament/Resources/Shipments/RelationManagers/ShipmentContainersRelationManager.php`
- Possibly `app/Filament/Actions/SealContainerAction.php`
- Possibly `app/Filament/Actions/UnsealContainerAction.php`
