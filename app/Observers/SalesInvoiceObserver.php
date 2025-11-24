<?php

namespace App\Observers;

use App\Models\SalesInvoice;
use App\Models\FinancialTransaction;
use App\Models\FinancialCategory;
use App\Models\Currency;
use Carbon\Carbon;

class SalesInvoiceObserver
{
    /**
     * Handle the SalesInvoice "updated" event.
     */
    public function updated(SalesInvoice $salesInvoice): void
    {
        // Check if status changed to 'sent'
        if ($salesInvoice->isDirty('status') && $salesInvoice->status === 'sent') {
            $this->createFinancialTransactions($salesInvoice);
        }
    }

    /**
     * Create financial transaction(s) (accounts receivable) for the sent invoice
     */
    protected function createFinancialTransactions(SalesInvoice $salesInvoice): void
    {
        // Check if transactions already exist
        $existingTransactions = FinancialTransaction::where('transactable_type', SalesInvoice::class)
            ->where('transactable_id', $salesInvoice->id)
            ->count();

        if ($existingTransactions > 0) {
            return; // Already created
        }

        // Get or create the default category for sales
        $category = FinancialCategory::firstOrCreate(
            ['code' => 'REV-SALES'],
            [
                'name' => 'Receita de Vendas',
                'type' => 'revenue',
                'is_system' => true,
                'is_active' => true,
            ]
        );

        // Get base currency
        $baseCurrency = Currency::where('is_base', true)->first();

        // Check if invoice has payment terms with multiple stages
        if ($salesInvoice->payment_term_id && $salesInvoice->paymentTerm) {
            $this->createTransactionsFromPaymentTerm($salesInvoice, $category);
        } else {
            // Create single transaction
            $this->createSingleTransaction($salesInvoice, $category);
        }
    }

    /**
     * Create multiple transactions based on payment term stages
     */
    protected function createTransactionsFromPaymentTerm(SalesInvoice $salesInvoice, FinancialCategory $category): void
    {
        $stages = $salesInvoice->paymentTerm->stages()->orderBy('sort_order')->get();
        
        foreach ($stages as $index => $stage) {
            // Calculate amount for this stage
            $stageAmount = (int) round($salesInvoice->total * ($stage->percentage / 100));
            
            // Calculate base currency amount
            $stageAmountBase = (int) round($salesInvoice->total_base_currency * ($stage->percentage / 100));
            
            // Calculate due date
            $baseDate = $salesInvoice->invoice_date;
            if ($stage->calculation_base === 'shipment_date' && $salesInvoice->shipment_date) {
                $baseDate = Carbon::parse($salesInvoice->shipment_date);
            }
            $dueDate = $baseDate->copy()->addDays($stage->days);
            
            // Create transaction
            FinancialTransaction::create([
                'description' => "Sales Invoice {$salesInvoice->invoice_number} - Parcela " . ($index + 1) . "/{$stages->count()}",
                'type' => 'receivable',
                'status' => 'pending',
                'amount' => $stageAmount,
                'paid_amount' => 0,
                'currency_id' => $salesInvoice->currency_id,
                'exchange_rate_to_base' => $salesInvoice->exchange_rate,
                'amount_base_currency' => $stageAmountBase,
                'transaction_date' => $salesInvoice->invoice_date,
                'due_date' => $dueDate,
                'financial_category_id' => $category->id,
                'transactable_type' => SalesInvoice::class,
                'transactable_id' => $salesInvoice->id,
                'client_id' => $salesInvoice->client_id,
                'notes' => "Parcela {$stage->percentage}% - {$stage->description}",
                'created_by' => auth()->id(),
            ]);
        }
    }

    /**
     * Create a single transaction for the full invoice amount
     */
    protected function createSingleTransaction(SalesInvoice $salesInvoice, FinancialCategory $category): void
    {
        FinancialTransaction::create([
            'description' => "Sales Invoice {$salesInvoice->invoice_number}",
            'type' => 'receivable',
            'status' => 'pending',
            'amount' => $salesInvoice->total,
            'paid_amount' => 0,
            'currency_id' => $salesInvoice->currency_id,
            'exchange_rate_to_base' => $salesInvoice->exchange_rate,
            'amount_base_currency' => $salesInvoice->total_base_currency,
            'transaction_date' => $salesInvoice->invoice_date,
            'due_date' => $salesInvoice->due_date,
            'financial_category_id' => $category->id,
            'transactable_type' => SalesInvoice::class,
            'transactable_id' => $salesInvoice->id,
            'client_id' => $salesInvoice->client_id,
            'notes' => $salesInvoice->notes,
            'created_by' => auth()->id(),
        ]);
    }
}
