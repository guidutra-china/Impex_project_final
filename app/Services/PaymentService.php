<?php

namespace App\Services;

use App\Models\SupplierPayment;
use App\Models\SupplierPaymentAllocation;
use App\Models\CustomerReceipt;
use App\Models\CustomerReceiptAllocation;
use App\Models\PurchaseOrder;
use App\Models\Order;
use App\Models\BankAccount;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    /**
     * Create supplier payment
     *
     * @param array $data
     * @return SupplierPayment
     */
    public function createSupplierPayment(array $data): SupplierPayment
    {
        return DB::transaction(function () use ($data) {
            // Get payment method to calculate fee
            $paymentMethod = PaymentMethod::findOrFail($data['payment_method_id']);
            $fee = $paymentMethod->calculateFee($data['amount']);
            $netAmount = $data['amount'] - $fee;

            // Create payment
            $payment = SupplierPayment::create([
                'payment_number' => $this->generateSupplierPaymentNumber(),
                'supplier_id' => $data['supplier_id'],
                'bank_account_id' => $data['bank_account_id'],
                'payment_method_id' => $data['payment_method_id'],
                'currency_id' => $data['currency_id'],
                'amount' => $data['amount'],
                'fee' => $fee,
                'net_amount' => $netAmount,
                'exchange_rate' => $data['exchange_rate'] ?? 1,
                'amount_base_currency' => (int) ($data['amount'] * ($data['exchange_rate'] ?? 1)),
                'payment_date' => $data['payment_date'] ?? now(),
                'reference_number' => $data['reference_number'] ?? null,
                'transaction_id' => $data['transaction_id'] ?? null,
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Update bank account balance
            $this->updateBankAccountBalance($payment->bankAccount, -$data['amount']);

            return $payment;
        });
    }

    /**
     * Allocate supplier payment to purchase orders
     *
     * @param SupplierPayment $payment
     * @param array $allocations [['purchase_order_id' => 1, 'amount' => 10000], ...]
     * @return SupplierPayment
     */
    public function allocateSupplierPayment(SupplierPayment $payment, array $allocations): SupplierPayment
    {
        return DB::transaction(function () use ($payment, $allocations) {
            $totalAllocated = 0;

            foreach ($allocations as $allocation) {
                SupplierPaymentAllocation::create([
                    'supplier_payment_id' => $payment->id,
                    'purchase_order_id' => $allocation['purchase_order_id'],
                    'allocated_amount' => $allocation['amount'],
                    'allocation_type' => 'manual',
                    'notes' => $allocation['notes'] ?? null,
                ]);

                $totalAllocated += $allocation['amount'];

                // Update PO status if fully paid
                $this->updatePurchaseOrderPaymentStatus(
                    PurchaseOrder::find($allocation['purchase_order_id'])
                );
            }

            // Validate total allocation
            if ($totalAllocated > $payment->amount) {
                throw new \Exception('Total allocated amount exceeds payment amount');
            }

            return $payment->fresh('allocations');
        });
    }

    /**
     * Create customer receipt
     *
     * @param array $data
     * @return CustomerReceipt
     */
    public function createCustomerReceipt(array $data): CustomerReceipt
    {
        return DB::transaction(function () use ($data) {
            // Get payment method to calculate fee
            $paymentMethod = PaymentMethod::findOrFail($data['payment_method_id']);
            $fee = $paymentMethod->calculateFee($data['amount']);
            $netAmount = $data['amount'] - $fee;

            // Create receipt
            $receipt = CustomerReceipt::create([
                'receipt_number' => $this->generateCustomerReceiptNumber(),
                'client_id' => $data['client_id'],
                'bank_account_id' => $data['bank_account_id'],
                'payment_method_id' => $data['payment_method_id'],
                'currency_id' => $data['currency_id'],
                'amount' => $data['amount'],
                'fee' => $fee,
                'net_amount' => $netAmount,
                'exchange_rate' => $data['exchange_rate'] ?? 1,
                'amount_base_currency' => (int) ($data['amount'] * ($data['exchange_rate'] ?? 1)),
                'receipt_date' => $data['receipt_date'] ?? now(),
                'reference_number' => $data['reference_number'] ?? null,
                'transaction_id' => $data['transaction_id'] ?? null,
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Update bank account balance
            $this->updateBankAccountBalance($receipt->bankAccount, $netAmount);

            return $receipt;
        });
    }

    /**
     * Allocate customer receipt to sales orders
     *
     * @param CustomerReceipt $receipt
     * @param array $allocations [['sales_order_id' => 1, 'amount' => 10000], ...]
     * @return CustomerReceipt
     */
    public function allocateCustomerReceipt(CustomerReceipt $receipt, array $allocations): CustomerReceipt
    {
        return DB::transaction(function () use ($receipt, $allocations) {
            $totalAllocated = 0;

            foreach ($allocations as $allocation) {
                CustomerReceiptAllocation::create([
                    'customer_receipt_id' => $receipt->id,
                    'sales_order_id' => $allocation['sales_order_id'],
                    'allocated_amount' => $allocation['amount'],
                    'allocation_type' => 'manual',
                    'notes' => $allocation['notes'] ?? null,
                ]);

                $totalAllocated += $allocation['amount'];

                // Update Sales Order status if fully paid
                $this->updateSalesOrderPaymentStatus(
                    Order::find($allocation['sales_order_id'])
                );
            }

            // Validate total allocation
            if ($totalAllocated > $receipt->amount) {
                throw new \Exception('Total allocated amount exceeds receipt amount');
            }

            return $receipt->fresh('allocations');
        });
    }

    /**
     * Update bank account balance
     *
     * @param BankAccount $account
     * @param int $amount (positive for credit, negative for debit)
     * @return void
     */
    private function updateBankAccountBalance(BankAccount $account, int $amount): void
    {
        $account->increment('current_balance', $amount);
        $account->increment('available_balance', $amount);
    }

    /**
     * Update Purchase Order payment status
     *
     * @param PurchaseOrder $po
     * @return void
     */
    private function updatePurchaseOrderPaymentStatus(PurchaseOrder $po): void
    {
        if ($po->is_fully_paid && $po->status === 'received') {
            $po->update(['status' => 'closed']);
        }
    }

    /**
     * Update Sales Order payment status
     *
     * @param Order $order
     * @return void
     */
    private function updateSalesOrderPaymentStatus(Order $order): void
    {
        // TODO: Implement when Sales Order model is ready
        // Check if fully paid and update status accordingly
    }

    /**
     * Generate unique supplier payment number
     *
     * @return string
     */
    private function generateSupplierPaymentNumber(): string
    {
        $year = date('Y');
        $lastPayment = SupplierPayment::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastPayment ? (int) substr($lastPayment->payment_number, -4) + 1 : 1;

        return sprintf('PAY-SUP-%s-%04d', $year, $nextNumber);
    }

    /**
     * Generate unique customer receipt number
     *
     * @return string
     */
    private function generateCustomerReceiptNumber(): string
    {
        $year = date('Y');
        $lastReceipt = CustomerReceipt::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastReceipt ? (int) substr($lastReceipt->receipt_number, -4) + 1 : 1;

        return sprintf('REC-CUST-%s-%04d', $year, $nextNumber);
    }
}
