<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RFQSupplierStatus extends Model
{
    protected $table = 'rfq_supplier_statuses';

    protected $fillable = [
        'order_id',
        'supplier_id',
        'sent',
        'sent_at',
        'sent_method',
        'sent_by',
    ];

    protected $casts = [
        'sent' => 'boolean',
        'sent_at' => 'datetime',
    ];

    /**
     * Get the order (RFQ) that owns this status
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the supplier that this status is for
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the user who sent the quotation
     */
    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    /**
     * Mark as sent
     */
    public function markAsSent(string $method = 'email', ?int $userId = null): void
    {
        $this->update([
            'sent' => true,
            'sent_at' => now(),
            'sent_method' => $method,
            'sent_by' => $userId ?? auth()->id(),
        ]);
    }

    /**
     * Check if sent
     */
    public function isSent(): bool
    {
        return $this->sent;
    }

    /**
     * Get status badge color
     */
    public function getStatusColor(): string
    {
        return $this->sent ? 'success' : 'gray';
    }

    /**
     * Get status label
     */
    public function getStatusLabel(): string
    {
        if (!$this->sent) {
            return 'Not Sent';
        }

        return 'Sent' . ($this->sent_at ? ' on ' . $this->sent_at->format('M d, Y') : '');
    }
}
