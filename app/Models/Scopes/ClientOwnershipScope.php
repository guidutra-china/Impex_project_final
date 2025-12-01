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

        // Check if user has any role with can_see_all = true
        $canSeeAll = $user->roles()->where('can_see_all', true)->exists();
        
        if ($canSeeAll) {
            return; // User can see everything
        }

        // For Client model - filter by user_id
        if ($model instanceof \App\Models\Client) {
            $builder->where('clients.user_id', $user->id);
            return;
        }

        // For Order (uses customer_id -> Client)
        if ($model instanceof \App\Models\Order) {
            $builder->whereHas('customer', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            });
            return;
        }

        // For PurchaseOrder (nested: PO -> Order -> Customer/Client)
        if ($model instanceof \App\Models\PurchaseOrder) {
            $builder->whereHas('order.customer', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            });
            return;
        }

        // For SalesInvoice (uses client_id -> Client)
        if ($model instanceof \App\Models\SalesInvoice) {
            $builder->whereHas('client', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            });
            return;
        }

        // For SupplierQuote (nested: SupplierQuote -> Order -> Customer/Client)
        if ($model instanceof \App\Models\SupplierQuote) {
            $builder->whereHas('order.customer', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            });
            return;
        }
    }
}
