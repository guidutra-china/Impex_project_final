# 🚀 GUIA COMPLETO DE IMPLEMENTAÇÃO - MÓDULOS PRIORITÁRIOS

**Data:** 2025-11-19  
**Versão:** 1.0  
**Projeto:** IMPEX ERP System

---

## ✅ STATUS ATUAL

### Já Implementado (no GitHub):
- ✅ **18 Migrations** - Todas as tabelas criadas
- ✅ **Commit:** 86f64ea
- ✅ **Branch:** main

### Falta Implementar (você vai fazer no servidor):
- ⏳ 18 Models
- ⏳ 7 Services (com segurança)
- ⏳ Enums
- ⏳ Filament Resources
- ⏳ Policies

---

## 📋 INSTRUÇÕES PARA VOCÊ

### Passo 1: Pull do Código
```bash
cd /path/to/your/project
git pull origin main
```

### Passo 2: Instalar Dependências
```bash
composer install
```

### Passo 3: Rodar Migrations
```bash
php artisan migrate
```

### Passo 4: Criar Models

Crie os arquivos abaixo em `app/Models/`:

---

## 📦 MODELS COMPLETOS

### 1. Document.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'document_number',
        'title',
        'description',
        'document_type',
        'related_type',
        'related_id',
        'file_path',
        'file_name',
        'safe_filename',
        'mime_type',
        'file_size',
        'status',
        'issue_date',
        'expiry_date',
        'is_public',
        'is_confidential',
        'notes',
        'uploaded_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'is_public' => 'boolean',
        'is_confidential' => 'boolean',
    ];

    // Relationships
    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // Scopes
    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('status', 'valid')
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now(), now()->addDays($days)]);
    }

    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now())
            ->where('status', 'valid');
    }
}
```

### 2. DocumentVersion.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentVersion extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $dates = ['created_at'];

    protected $fillable = [
        'document_id',
        'version_number',
        'file_path',
        'file_name',
        'file_size',
        'change_notes',
        'uploaded_by',
    ];

    // Relationships
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
```

### 3. Shipment.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Shipment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'shipment_number',
        'sales_order_id',
        'purchase_order_id',
        'shipment_type',
        'carrier',
        'tracking_number',
        'container_number',
        'shipping_method',
        'status',
        'origin_address',
        'destination_address',
        'shipment_date',
        'estimated_delivery_date',
        'actual_delivery_date',
        'shipping_cost',
        'currency_id',
        'total_weight',
        'total_volume',
        'notes',
        'special_instructions',
        'notification_sent_at',
        'created_by',
    ];

    protected $casts = [
        'shipment_date' => 'date',
        'estimated_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
        'notification_sent_at' => 'datetime',
    ];

    // Relationships
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'sales_order_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShipmentItem::class);
    }

    public function trackingEvents(): HasMany
    {
        return $this->hasMany(TrackingEvent::class)->orderBy('event_date', 'desc');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'related');
    }

    // Scopes
    public function scopeInTransit($query)
    {
        return $query->whereIn('status', ['picked_up', 'in_transit', 'customs_clearance', 'out_for_delivery']);
    }

    public function scopeDelayed($query)
    {
        return $query->where('estimated_delivery_date', '<', now())
            ->whereNull('actual_delivery_date')
            ->whereNotIn('status', ['delivered', 'cancelled']);
    }
}
```

### 4. ShipmentItem.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentItem extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $dates = ['created_at'];

    protected $fillable = [
        'shipment_id',
        'sales_order_item_id',
        'product_id',
        'quantity',
        'product_name',
        'product_sku',
        'weight',
        'volume',
    ];

    // Relationships
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function salesOrderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'sales_order_item_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
```

### 5. TrackingEvent.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackingEvent extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $dates = ['event_date', 'created_at'];

    protected $fillable = [
        'shipment_id',
        'event_type',
        'event_description',
        'notes',
        'location',
        'city',
        'country',
        'event_date',
        'source',
    ];

    // Relationships
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }
}
```

### 6. Warehouse.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Warehouse extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'type',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'contact_person',
        'contact_phone',
        'contact_email',
        'total_capacity',
        'used_capacity',
        'is_active',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function locations(): HasMany
    {
        return $this->hasMany(WarehouseLocation::class);
    }

    public function stock(): HasMany
    {
        return $this->hasMany(WarehouseStock::class);
    }

    public function transfersFrom(): HasMany
    {
        return $this->hasMany(WarehouseTransfer::class, 'from_warehouse_id');
    }

    public function transfersTo(): HasMany
    {
        return $this->hasMany(WarehouseTransfer::class, 'to_warehouse_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
```

### 7. WarehouseLocation.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'location_code',
        'location_name',
        'location_type',
        'capacity',
        'is_available',
    ];

    protected $casts = [
        'is_available' => 'boolean',
    ];

    // Relationships
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function stock(): HasMany
    {
        return $this->hasMany(WarehouseStock::class);
    }
}
```

### 8. WarehouseStock.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseStock extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $dates = ['last_movement_date', 'updated_at'];

    protected $fillable = [
        'warehouse_id',
        'warehouse_location_id',
        'product_id',
        'quantity',
        'unit_cost',
        'total_value',
        'last_movement_date',
    ];

    // Relationships
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'warehouse_location_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
```

### 9. WarehouseTransfer.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_number',
        'from_warehouse_id',
        'to_warehouse_id',
        'status',
        'transfer_date',
        'expected_arrival_date',
        'actual_arrival_date',
        'notes',
        'created_by',
        'approved_by',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'expected_arrival_date' => 'date',
        'actual_arrival_date' => 'date',
    ];

    // Relationships
    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(WarehouseTransferItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
```

### 10. WarehouseTransferItem.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseTransferItem extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $dates = ['created_at'];

    protected $fillable = [
        'warehouse_transfer_id',
        'product_id',
        'quantity',
        'unit_cost',
    ];

    // Relationships
    public function transfer(): BelongsTo
    {
        return $this->belongsTo(WarehouseTransfer::class, 'warehouse_transfer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
```

---

## 📝 CONTINUAÇÃO NO PRÓXIMO ARQUIVO

Devido ao tamanho, vou criar arquivos separados para:
- Quality Control Models (5 models)
- Supplier Performance Models (3 models)
- Services (7 services)
- Enums
- Filament Resources

Continue lendo os próximos arquivos...
