<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class RecurringTransaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'type',
        'financial_category_id',
        'amount',
        'currency_id',
        'frequency',
        'interval',
        'day_of_month',
        'day_of_week',
        'start_date',
        'end_date',
        'next_due_date',
        'supplier_id',
        'client_id',
        'is_active',
        'auto_generate',
        'days_before_due',
        'last_generated_date',
        'last_generated_transaction_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'integer',
        'interval' => 'integer',
        'day_of_month' => 'integer',
        'day_of_week' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'next_due_date' => 'date',
        'is_active' => 'boolean',
        'auto_generate' => 'boolean',
        'days_before_due' => 'integer',
        'last_generated_date' => 'date',
    ];

    /**
     * Get the financial category
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(FinancialCategory::class, 'financial_category_id');
    }

    /**
     * Get the currency
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the supplier (for payables)
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the client (for receivables)
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the user who created this recurring transaction
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the last generated transaction
     */
    public function lastGeneratedTransaction(): BelongsTo
    {
        return $this->belongsTo(FinancialTransaction::class, 'last_generated_transaction_id');
    }

    /**
     * Scope to get active recurring transactions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get recurring transactions ready to generate
     */
    public function scopeReadyToGenerate($query)
    {
        return $query->where('is_active', true)
            ->where('auto_generate', true)
            ->where('next_due_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });
    }

    /**
     * Generate the next financial transaction
     */
    public function generateTransaction(): FinancialTransaction
    {
        // Get current exchange rate
        $baseCurrency = Currency::where('is_base', true)->first();
        $exchangeRate = ExchangeRate::getConversionRate(
            $this->currency_id,
            $baseCurrency->id,
            now()->toDateString()
        ) ?? 1.0;

        $transaction = FinancialTransaction::create([
            'description' => $this->name . ' - ' . $this->next_due_date->format('M/Y'),
            'type' => $this->type,
            'amount' => $this->amount,
            'currency_id' => $this->currency_id,
            'exchange_rate_to_base' => $exchangeRate,
            'amount_base_currency' => (int) ($this->amount * $exchangeRate),
            'transaction_date' => now()->toDateString(),
            'due_date' => $this->next_due_date,
            'financial_category_id' => $this->financial_category_id,
            'transactable_type' => RecurringTransaction::class,
            'transactable_id' => $this->id,
            'supplier_id' => $this->supplier_id,
            'client_id' => $this->client_id,
            'notes' => $this->notes,
        ]);

        // Update recurring transaction
        $this->last_generated_date = now();
        $this->last_generated_transaction_id = $transaction->id;
        $this->next_due_date = $this->calculateNextDueDate();
        $this->save();

        return $transaction;
    }

    /**
     * Calculate the next due date based on frequency
     */
    public function calculateNextDueDate(): Carbon
    {
        $current = $this->next_due_date ?? $this->start_date;

        switch ($this->frequency) {
            case 'daily':
                return $current->addDays($this->interval);

            case 'weekly':
                return $current->addWeeks($this->interval);

            case 'monthly':
                $next = $current->addMonths($this->interval);
                if ($this->day_of_month) {
                    $next->day = min($this->day_of_month, $next->daysInMonth);
                }
                return $next;

            case 'quarterly':
                return $current->addMonths(3 * $this->interval);

            case 'yearly':
                return $current->addYears($this->interval);

            default:
                return $current->addMonths(1);
        }
    }

    /**
     * Check if recurring transaction should still generate
     */
    public function shouldGenerate(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->end_date && $this->next_due_date > $this->end_date) {
            return false;
        }

        return true;
    }

    /**
     * Get preview of next N occurrences
     */
    public function getNextOccurrences(int $count = 5): array
    {
        $occurrences = [];
        $date = $this->next_due_date;

        for ($i = 0; $i < $count; $i++) {
            if ($this->end_date && $date > $this->end_date) {
                break;
            }

            $occurrences[] = [
                'date' => $date->format('Y-m-d'),
                'formatted' => $date->format('d/m/Y'),
                'amount' => $this->amount,
            ];

            // Calculate next date
            $recurring = new self($this->toArray());
            $recurring->next_due_date = $date;
            $date = $recurring->calculateNextDueDate();
        }

        return $occurrences;
    }
}
