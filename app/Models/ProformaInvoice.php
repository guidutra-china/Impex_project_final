<?php

namespace App\Models;

use App\Models\Scopes\ClientOwnershipScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProformaInvoice extends Model
{
    use SoftDeletes;

    protected static function booted(): void
    {
        static::addGlobalScope(new ClientOwnershipScope());
        
        // Auto-increment revision on update
        static::updating(function ($model) {
            // Fields that should trigger revision increment
            $importantFields = [
                'customer_id',
                'currency_id',
                'payment_term_id',
                'incoterm',
                'incoterm_location',
                'subtotal',
                'tax',
                'total',
                'issue_date',
                'valid_until',
                'due_date',
                'deposit_required',
                'deposit_amount',
                'deposit_percent',
                'terms_and_conditions',
            ];
            
            // Check if any important field was changed
            $hasImportantChanges = false;
            foreach ($importantFields as $field) {
                if ($model->isDirty($field)) {
                    $hasImportantChanges = true;
                    break;
                }
            }
            
            // Increment revision if important fields changed
            if ($hasImportantChanges && !$model->isDirty('revision_number')) {
                $model->revision_number = ($model->revision_number ?? 1) + 1;
            }
        });
    }

    protected $fillable = [
        'proforma_number',
        'revision_number',
        'customer_id',
        'currency_id',
        'payment_term_id',
        'incoterm',
        'incoterm_location',
        'subtotal',
        'tax',
        'total',
        'exchange_rate',
        'issue_date',
        'valid_until',
        'due_date',
        'status',
        'sent_at',
        'approved_at',
        'approved_by',
        'rejection_reason',
        'rejected_at',
        'deposit_required',
        'deposit_amount',
        'deposit_percent',
        'deposit_received',
        'deposit_received_at',
        'deposit_payment_method',
        'deposit_payment_reference',
        'notes',
        'terms_and_conditions',
        'customer_notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'valid_until' => 'date',
        'due_date' => 'date',
        'sent_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'deposit_received_at' => 'datetime',
        'deposit_required' => 'boolean',
        'deposit_received' => 'boolean',
        'exchange_rate' => 'decimal:6',
        'deposit_percent' => 'decimal:2',
    ];

    /**
     * Attribute accessors for amounts (convert cents to dollars)
     */
    protected function subtotal(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value ? $value / 100 : 0,
            set: fn ($value) => is_numeric($value) && $value ? (int) ($value * 100) : 0,
        );
    }

    protected function tax(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value ? $value / 100 : 0,
            set: fn ($value) => is_numeric($value) && $value ? (int) ($value * 100) : 0,
        );
    }

    protected function total(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value ? $value / 100 : 0,
            set: fn ($value) => is_numeric($value) && $value ? (int) ($value * 100) : 0,
        );
    }

    protected function depositAmount(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => $value ? $value / 100 : 0,
            set: fn ($value) => is_numeric($value) && $value ? (int) ($value * 100) : 0,
        );
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate proforma number
        static::creating(function ($proforma) {
            if (!$proforma->proforma_number) {
                $proforma->proforma_number = static::generateProformaNumber();
            }
        });
    }

    /**
     * Generate proforma number
     * Format: PI-YYYY-NNNN
     */
    public static function generateProformaNumber(): string
    {
        $year = now()->year;
        $prefix = "PI-{$year}-";
        
        // Get all proforma numbers for this year (including soft deleted and all clients)
        $lastNumber = static::withTrashed()
            ->withoutGlobalScopes()
            ->where('proforma_number', 'LIKE', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING(proforma_number, -4) AS UNSIGNED) DESC')
            ->value('proforma_number');
        
        if ($lastNumber) {
            // Extract the numeric part and increment
            $nextNumber = (int) substr($lastNumber, -4) + 1;
        } else {
            $nextNumber = 1;
        }
        
        // Ensure uniqueness by checking if number exists (including soft deleted)
        $attempts = 0;
        do {
            $proformaNumber = sprintf('PI-%d-%04d', $year, $nextNumber);
            $exists = static::withTrashed()
                ->withoutGlobalScopes()
                ->where('proforma_number', $proformaNumber)
                ->exists();
            
            if ($exists) {
                $nextNumber++;
                $attempts++;
            }
            
            // Prevent infinite loop
            if ($attempts > 100) {
                throw new \Exception('Unable to generate unique proforma number after 100 attempts');
            }
        } while ($exists);
        
        return $proformaNumber;
    }

    /**
     * Relationships
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'customer_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProformaInvoiceItem::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'proforma_invoice_id');
    }

    /**
     * Helper methods
     */
    public function recalculateTotals(): void
    {
        // Use raw SQL to bypass Attribute getters
        $subtotalCents = \DB::table('proforma_invoice_items')
            ->where('proforma_invoice_id', $this->id)
            ->sum('total');
        
        // Get tax in cents (raw value from database)
        $taxCents = $this->getRawOriginal('tax') ?? 0;
        
        // Calculate total in cents
        $totalCents = $subtotalCents + $taxCents;
        
        // Use raw SQL update to bypass Eloquent casting
        \DB::table('proforma_invoices')
            ->where('id', $this->id)
            ->update([
                'subtotal' => $subtotalCents,
                'total' => $totalCents,
                'updated_at' => now(),
            ]);
        
        // Refresh the model to get the updated values
        $this->refresh();
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isPending(): bool
    {
        return $this->status === 'sent';
    }

    public function isExpired(): bool
    {
        return $this->valid_until && $this->valid_until->isPast() && !$this->isApproved();
    }

    public function canApprove(): bool
    {
        return in_array($this->status, ['sent', 'draft']);
    }

    public function canReject(): bool
    {
        return in_array($this->status, ['sent', 'draft']);
    }

    public function requiresDeposit(): bool
    {
        return $this->deposit_required;
    }

    public function hasDepositReceived(): bool
    {
        return $this->deposit_received;
    }

    public function canProceedToPO(): bool
    {
        if (!$this->isApproved()) {
            return false;
        }

        if ($this->requiresDeposit() && !$this->hasDepositReceived()) {
            return false;
        }

        return true;
    }
}
