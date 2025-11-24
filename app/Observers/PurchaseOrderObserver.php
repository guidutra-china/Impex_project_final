<?php

namespace App\Observers;

use App\Models\PurchaseOrder;
use App\Models\FinancialTransaction;
use App\Models\FinancialCategory;
use App\Models\Currency;
use Carbon\Carbon;

class PurchaseOrderObserver
{
    /**
     * Handle the PurchaseOrder "updated" event.
     */
    public function updated(PurchaseOrder $purchaseOrder): void
    {
        // Check if status changed to 'confirmed' (when supplier confirms the PO)
        if ($purchaseOrder->isDirty('status') && $purchaseOrder->status === 'confirmed') {
            $this->createFinancialTransactions($purchaseOrder);
        }
    }

    /**
     * Create financial transaction(s) (accounts payable) for the confirmed PO
     */
    protected function createFinancialTransactions(PurchaseOrder $purchaseOrder): void
    {
        // Check if transactions already exist
        $existingTransactions = FinancialTransaction::where('transactable_type', PurchaseOrder::class)
            ->where('transactable_id', $purchaseOrder->id)
            ->count();

        if ($existingTransactions > 0) {
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

        // Check if PO has payment terms with multiple stages
        if ($purchaseOrder->payment_term_id && $purchaseOrder->paymentTerm) {
            $this->createTransactionsFromPaymentTerm($purchaseOrder, $category);
        } else {
            // Create single transaction
            $this->createSingleTransaction($purchaseOrder, $category);
        }
    }

    /**
     * Create multiple transactions based on payment term stages
     */
    protected function createTransactionsFromPaymentTerm(PurchaseOrder $purchaseOrder, FinancialCategory $category): void
    {
        $stages = $purchaseOrder->paymentTerm->stages()->orderBy('sort_order')->get();
        
        // Get raw values in cents from database (bypass Attribute getters)
        $totalCents = $purchaseOrder->getRawOriginal('total');
        $totalBaseCents = $purchaseOrder->getRawOriginal('total_base_currency');
        
        foreach ($stages as $index => $stage) {
            // Calculate amount for this stage (already in cents)
            $stageAmount = (int) round($totalCents * ($stage->percentage / 100));
            
            // Calculate base currency amount (already in cents)
            $stageAmountBase = (int) round($totalBaseCents * ($stage->percentage / 100));
            
            // Calculate due date
            $baseDate = $purchaseOrder->po_date;
            if ($stage->calculation_base === 'shipment_date' && $purchaseOrder->expected_delivery_date) {
                $baseDate = Carbon::parse($purchaseOrder->expected_delivery_date);
            }
            $dueDate = $baseDate->copy()->addDays($stage->days);
            
            // Create transaction
            FinancialTransaction::create([
                'description' => "Purchase Order {$purchaseOrder->po_number} - Parcela " . ($index + 1) . "/{$stages->count()}",
                'type' => 'payable',
                'status' => 'pending',
                'amount' => $stageAmount,
                'paid_amount' => 0,
                'currency_id' => $purchaseOrder->currency_id,
                'exchange_rate_to_base' => $purchaseOrder->exchange_rate,
                'amount_base_currency' => $stageAmountBase,
                'transaction_date' => $purchaseOrder->po_date,
                'due_date' => $dueDate,
                'financial_category_id' => $category->id,
                'transactable_type' => PurchaseOrder::class,
                'transactable_id' => $purchaseOrder->id,
                'supplier_id' => $purchaseOrder->supplier_id,
                'notes' => "Parcela {$stage->percentage}% - Vencimento em {$stage->days} dias",
                'created_by' => $purchaseOrder->created_by,
            ]);
        }
    }

    /**
     * Create a single transaction for the full PO amount
     */
    protected function createSingleTransaction(PurchaseOrder $purchaseOrder, FinancialCategory $category): void
    {
        // Calculate due date (default: 30 days after PO date)
        $dueDate = $purchaseOrder->po_date->copy()->addDays(30);
        
        // Get raw values in cents from database (bypass Attribute getters)
        $totalCents = $purchaseOrder->getRawOriginal('total');
        $totalBaseCents = $purchaseOrder->getRawOriginal('total_base_currency');

        FinancialTransaction::create([
            'description' => "Purchase Order {$purchaseOrder->po_number}",
            'type' => 'payable',
            'status' => 'pending',
            'amount' => $totalCents, // Already in cents
            'paid_amount' => 0,
            'currency_id' => $purchaseOrder->currency_id,
            'exchange_rate_to_base' => $purchaseOrder->exchange_rate,
            'amount_base_currency' => $totalBaseCents, // Already in cents
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
}
