<?php

namespace App\Models;

use App\Models\Scopes\ClientOwnershipScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CustomerQuote extends Model
{
    use SoftDeletes;

    protected static function booted(): void
    {
        static::addGlobalScope(new ClientOwnershipScope());
    }

    protected $fillable = [
        'order_id',
        'quote_number',
        'status',
        'public_token',
        'sent_at',
        'approved_at',
        'rejected_at',
        'expires_at',
        'viewed_at',
        'responded_at',
        'approved_by_user_id',
        'commission_type',
        'commission_percent',
        'customer_notes',
        'internal_notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'expires_at' => 'datetime',
        'viewed_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate quote number and public token
        static::creating(function ($quote) {
            if (!$quote->quote_number) {
                $quote->quote_number = $quote->generateQuoteNumber();
            }

            if (!$quote->public_token) {
                $quote->public_token = Str::random(32);
            }

            // Set expiry date (default 7 days)
            if (!$quote->expires_at) {
                $quote->expires_at = now()->addDays(7);
            }
        });
    }

    /**
     * Generate a unique quote number
     */
    protected function generateQuoteNumber(): string
    {
        $prefix = 'CQ';
        $year = now()->format('Y');
        $month = now()->format('m');

        // Get the last quote number for this month
        $lastQuote = static::withoutGlobalScopes()
            ->where('quote_number', 'like', "{$prefix}-{$year}{$month}-%")
            ->orderBy('quote_number', 'desc')
            ->first();

        if ($lastQuote) {
            // Extract the sequence number and increment
            $lastNumber = (int) substr($lastQuote->quote_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('%s-%s%s-%04d', $prefix, $year, $month, $newNumber);
    }

    /**
     * Get the order
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the quote items
     */
    public function items(): HasMany
    {
        return $this->hasMany(CustomerQuoteItem::class);
    }

    /**
     * Get the user who approved the quote
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    /**
     * Get the user who created the quote
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the quote
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the selected item (approved by customer)
     */
    public function selectedItem()
    {
        return $this->items()->where('is_selected_by_customer', true)->first();
    }

    /**
     * Check if the quote is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && now()->isAfter($this->expires_at);
    }

    /**
     * Check if the quote is pending customer action
     */
    public function isPending(): bool
    {
        return $this->status === 'sent' && !$this->isExpired();
    }

    /**
     * Mark the quote as sent
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark the quote as approved
     */
    public function markAsApproved(int $userId = null): void
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by_user_id' => $userId,
        ]);
    }

    /**
     * Mark the quote as rejected
     */
    public function markAsRejected(): void
    {
        $this->update([
            'status' => 'rejected',
            'rejected_at' => now(),
        ]);
    }

    /**
     * Get the public URL for customer access
     */
    public function getPublicUrl(): string
    {
        return route('customer-quote.show', ['token' => $this->public_token]);
    }
}
