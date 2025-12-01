<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class ClientOwnershipScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        // Skip if no user authenticated
        if (!$user) {
            return;
        }

        // Super Admin sees everything
        if ($user->hasRole('super_admin')) {
            return;
        }

        // For Client model - filter by user_id
        if ($model instanceof \App\Models\Client) {
            $builder->where('clients.user_id', $user->id);
            return;
        }

        // For models with direct client_id
        if ($model->getTable() === 'orders' || 
            $model->getTable() === 'purchase_orders' || 
            $model->getTable() === 'sales_invoices') {
            
            $builder->whereHas('client', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            });
            return;
        }

        // For SupplierQuote (nested: SupplierQuote -> Order -> Client)
        if ($model->getTable() === 'supplier_quotes') {
            $builder->whereHas('order.client', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            });
            return;
        }

        // For OrderItems (nested: OrderItem -> Order -> Client)
        if ($model->getTable() === 'order_items') {
            $builder->whereHas('order.client', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            });
            return;
        }

        // For PurchaseOrderItems (nested: POItem -> PO -> Client)
        if ($model->getTable() === 'purchase_order_items') {
            $builder->whereHas('purchaseOrder.client', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            });
            return;
        }

        // For SalesInvoiceItems (nested: SIItem -> SI -> Client)
        if ($model->getTable() === 'sales_invoice_items') {
            $builder->whereHas('salesInvoice.client', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            });
            return;
        }
    }
}
