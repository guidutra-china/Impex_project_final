<?php

namespace App\Traits;

use App\Models\User;

trait HasClientOwnership
{
    /**
     * Check if user can see all records
     * Based on role's can_see_all flag
     */
    protected function canSeeAll(User $user): bool
    {
        // Check if user has any role with can_see_all = true
        return $user->roles()->where('can_see_all', true)->exists();
    }

    /**
     * Check if user owns the client
     */
    protected function ownsClient(User $user, $client): bool
    {
        if (!$client) {
            return false;
        }

        return $client->user_id === $user->id;
    }

    /**
     * Check if user can access record based on client ownership
     * Works for models that have direct client_id or nested client relationship
     */
    protected function canAccessRecord(User $user, $record): bool
    {
        // Users with can_see_all role can access everything
        if ($this->canSeeAll($user)) {
            return true;
        }

        // If record has direct client relationship
        if (method_exists($record, 'client') && $record->client) {
            return $this->ownsClient($user, $record->client);
        }

        // If record has direct client_id
        if (isset($record->client_id)) {
            $client = \App\Models\Client::find($record->client_id);
            return $this->ownsClient($user, $client);
        }

        // If record IS a client
        if ($record instanceof \App\Models\Client) {
            return $this->ownsClient($user, $record);
        }

        // For nested relationships (e.g., SupplierQuote -> Order -> Client)
        if (method_exists($record, 'order') && $record->order && method_exists($record->order, 'customer')) {
            return $this->ownsClient($user, $record->order->customer);
        }

        // Default: deny access
        return false;
    }
}
