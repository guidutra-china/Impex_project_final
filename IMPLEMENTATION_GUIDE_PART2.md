# 🚀 GUIA DE IMPLEMENTAÇÃO - PARTE 2

**Continuação do IMPLEMENTATION_GUIDE.md**

---

## 📦 QUALITY CONTROL MODELS

### 11. QualityInspection.php

Crie em `app/Models/QualityInspection.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityInspection extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'inspection_number',
        'inspectable_type',
        'inspectable_id',
        'inspection_type',
        'status',
        'result',
        'inspection_date',
        'completed_date',
        'inspector_id',
        'inspector_name',
        'notes',
        'failure_reason',
        'corrective_action',
        'created_by',
    ];

    protected $casts = [
        'inspection_date' => 'date',
        'completed_date' => 'date',
    ];

    // Relationships
    public function inspectable(): MorphTo
    {
        return $this->morphTo();
    }

    public function items(): HasMany
    {
        return $this->hasMany(QualityInspectionItem::class);
    }

    public function checkpoints(): HasMany
    {
        return $this->hasMany(QualityInspectionCheckpoint::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(QualityCertificate::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'in_progress']);
    }

    public function scopeFailed($query)
    {
        return $query->where('result', 'failed');
    }
}
```

### 12. QualityInspectionItem.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityInspectionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'quality_inspection_id',
        'product_id',
        'quantity_inspected',
        'quantity_passed',
        'quantity_failed',
        'result',
        'defects_found',
        'notes',
    ];

    // Relationships
    public function inspection(): BelongsTo
    {
        return $this->belongsTo(QualityInspection::class, 'quality_inspection_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
```

### 13. QualityCheckpoint.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QualityCheckpoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'checkpoint_type',
        'criterion',
        'applies_to',
        'product_category_id',
        'product_id',
        'is_active',
        'is_mandatory',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_mandatory' => 'boolean',
    ];

    // Relationships
    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'product_category_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inspectionCheckpoints(): HasMany
    {
        return $this->hasMany(QualityInspectionCheckpoint::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }
}
```

### 14. QualityInspectionCheckpoint.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityInspectionCheckpoint extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $dates = ['checked_at', 'created_at'];

    protected $fillable = [
        'quality_inspection_id',
        'quality_checkpoint_id',
        'result',
        'measured_value',
        'expected_value',
        'notes',
        'checked_by',
        'checked_at',
    ];

    // Relationships
    public function inspection(): BelongsTo
    {
        return $this->belongsTo(QualityInspection::class, 'quality_inspection_id');
    }

    public function checkpoint(): BelongsTo
    {
        return $this->belongsTo(QualityCheckpoint::class, 'quality_checkpoint_id');
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
```

### 15. QualityCertificate.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityCertificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'quality_inspection_id',
        'certificate_number',
        'certificate_type',
        'issue_date',
        'expiry_date',
        'file_path',
        'status',
        'notes',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    // Relationships
    public function inspection(): BelongsTo
    {
        return $this->belongsTo(QualityInspection::class, 'quality_inspection_id');
    }

    // Scopes
    public function scopeValid($query)
    {
        return $query->where('status', 'valid')
            ->where(function($q) {
                $q->whereNull('expiry_date')
                  ->orWhere('expiry_date', '>=', now());
            });
    }

    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now());
    }
}
```

---

## 📦 SUPPLIER PERFORMANCE MODELS

### 16. SupplierPerformanceMetric.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPerformanceMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'period_year',
        'period_month',
        'total_orders',
        'on_time_deliveries',
        'late_deliveries',
        'average_delay_days',
        'total_inspections',
        'passed_inspections',
        'failed_inspections',
        'quality_score',
        'total_purchase_value',
        'total_orders_value',
        'average_order_value',
        'response_time_hours',
        'communication_score',
        'overall_score',
        'rating',
        'notes',
    ];

    // Relationships
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    // Accessors
    public function getOnTimeDeliveryRateAttribute(): float
    {
        if ($this->total_orders == 0) return 0;
        return ($this->on_time_deliveries / $this->total_orders) * 100;
    }

    public function getQualityPassRateAttribute(): float
    {
        if ($this->total_inspections == 0) return 0;
        return ($this->passed_inspections / $this->total_inspections) * 100;
    }

    // Scopes
    public function scopeForPeriod($query, int $year, int $month)
    {
        return $query->where('period_year', $year)
            ->where('period_month', $month);
    }

    public function scopeExcellent($query)
    {
        return $query->where('rating', 'excellent');
    }

    public function scopePoor($query)
    {
        return $query->whereIn('rating', ['poor', 'unacceptable']);
    }
}
```

### 17. SupplierPerformanceReview.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPerformanceReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'review_date',
        'review_period_start',
        'review_period_end',
        'delivery_score',
        'quality_score',
        'pricing_score',
        'communication_score',
        'overall_score',
        'rating',
        'strengths',
        'weaknesses',
        'recommendations',
        'decision',
        'reviewed_by',
    ];

    protected $casts = [
        'review_date' => 'date',
        'review_period_start' => 'date',
        'review_period_end' => 'date',
    ];

    // Relationships
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // Scopes
    public function scopeRecent($query, int $months = 6)
    {
        return $query->where('review_date', '>=', now()->subMonths($months));
    }

    public function scopeByRating($query, string $rating)
    {
        return $query->where('rating', $rating);
    }
}
```

### 18. SupplierIssue.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierIssue extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'purchase_order_id',
        'issue_type',
        'severity',
        'status',
        'description',
        'resolution',
        'resolution_date',
        'financial_impact',
        'reported_date',
        'reported_by',
        'assigned_to',
    ];

    protected $casts = [
        'reported_date' => 'date',
        'resolution_date' => 'date',
    ];

    // Relationships
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'in_progress']);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    public function scopeUnresolved($query)
    {
        return $query->whereIn('status', ['open', 'in_progress']);
    }
}
```

---

## 📋 CONTINUAÇÃO NO PRÓXIMO ARQUIVO

No próximo arquivo vou incluir:
- Purchase Order Models
- Financial Models (Banking, Payments)
- Enums
- Services básicos

Continue para **IMPLEMENTATION_GUIDE_PART3.md**...
