<?php

namespace App\Observers;

use App\Models\PurchaseOrder;
use App\Models\FinancialTransaction;
use App\Models\FinancialCategory;
use App\Models\Currency;

class PurchaseOrderObserver
{
    /**
     * Handle the PurchaseOrder "updated" event.
     */
    public function updated(PurchaseOrder $purchaseOrder): void
    {
        // Check if status changed to 'confirmed' (when supplier confirms the PO)
        if ($purchaseOrder->isDirty('status') && $purchaseOrder->status === 'confirmed') {
            $this->createFinancialTransaction($purchaseOrder);
        }
    }

    /**
     * Create a financial transaction (account payable) for the approved PO
     */
    protected function createFinancialTransaction(PurchaseOrder $purchaseOrder): void
    {
        // Check if transaction already exists
        $existingTransaction = FinancialTransaction::where('transactable_type', PurchaseOrder::class)
            ->where('transactable_id', $purchaseOrder->id)
            ->first();

        if ($existingTransaction) {
            return; // Already created
        }

        // Get or create the default category for purchase orders
        $category = FinancialCategory::firstOrCreate(
            ['code' => 'COST-VAR-PURCHASE'],
            [
                'name' => 'Compras de Matéria-Prima',
                'type' => 'expense',
                'is_system' => true,
                'is_active' => true,
            ]
        );

        // Get base currency
        $baseCurrency = Currency::where('is_base', true)->first();

        // Calculate due date based on payment terms
        $dueDate = $this->calculateDueDate($purchaseOrder);

        // Create the financial transaction
        FinancialTransaction::create([
            'description' => "Purchase Order {$purchaseOrder->po_number}",
            'type' => 'payable',
            'status' => 'pending',
            'amount' => $purchaseOrder->total,
            'paid_amount' => 0,
            'currency_id' => $purchaseOrder->currency_id,
            'exchange_rate_to_base' => $purchaseOrder->exchange_rate,
            'amount_base_currency' => $purchaseOrder->total_base_currency,
            'transaction_date' => $purchaseOrder->po_date,
            'due_date' => $dueDate,
            'financial_category_id' => $category->id,
            'transactable_type' => PurchaseOrder::class,
            'transactable_id' => $purchaseOrder->id,
            'supplier_id' => $purchaseOrder->supplier_id,
            'notes' => $purchaseOrder->notes,
            'created_by' => $purchaseOrder->created_by,
        ]);
    }

    /**
     * Calculate due date based on payment terms
     */
    protected function calculateDueDate(PurchaseOrder $purchaseOrder): string
    {
        // If payment term is set, use the first stage's due date
        if ($purchaseOrder->payment_term_id && $purchaseOrder->paymentTerm) {
            $firstStage = $purchaseOrder->paymentTerm->stages()->orderBy('sort_order')->first();
            
            if ($firstStage) {
                $baseDate = $purchaseOrder->po_date;
                
                // Calculate based on calculation_base
                if ($firstStage->calculation_base === 'invoice_date') {
                    $baseDate = $purchaseOrder->po_date;
                } elseif ($firstStage->calculation_base === 'shipment_date' && $purchaseOrder->expected_delivery_date) {
                    $baseDate = $purchaseOrder->expected_delivery_date;
                }
                
                return $baseDate->addDays($firstStage->days);
            }
        }

        // Default: 30 days after PO date
        return $purchaseOrder->po_date->addDays(30);
    }
}
