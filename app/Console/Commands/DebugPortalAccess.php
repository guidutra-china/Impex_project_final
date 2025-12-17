<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\CustomerQuote;
use App\Models\Order;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class DebugPortalAccess extends Command
{
    protected $signature = 'debug:portal-access {email}';
    protected $description = 'Debug portal access for a specific user';

    public function handle()
    {
        $email = $this->argument('email');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User with email '{$email}' not found!");
            return 1;
        }

        $this->info("=== USER INFO ===");
        $this->line("ID: {$user->id}");
        $this->line("Name: {$user->name}");
        $this->line("Email: {$user->email}");
        $this->line("Is Admin: " . ($user->is_admin ? 'YES' : 'NO'));
        $this->line("Client ID: " . ($user->client_id ?? 'NULL'));
        
        if ($user->client_id) {
            $client = Client::find($user->client_id);
            if ($client) {
                $this->line("Client Name: {$client->name}");
            } else {
                $this->error("Client ID {$user->client_id} NOT FOUND!");
            }
        }

        $this->newLine();
        $this->info("=== ROLES ===");
        foreach ($user->roles as $role) {
            $this->line("- {$role->name} (can_see_all: " . ($role->can_see_all ? 'YES' : 'NO') . ")");
        }

        $this->newLine();
        $this->info("=== ALL CUSTOMER QUOTES (WITHOUT SCOPE) ===");
        $allQuotes = CustomerQuote::withoutGlobalScopes()->with(['order' => function($q) {
            $q->withoutGlobalScopes()->with('customer');
        }])->get();
        
        foreach ($allQuotes as $quote) {
            $order = $quote->order;
            $customer = $order ? $order->customer : null;
            $customerId = $order ? $order->customer_id : 'NULL';
            $customerName = $customer ? $customer->name : 'N/A';
            $orderId = $order ? $order->id : 'NULL';
            
            $this->line("Quote: {$quote->quote_number} | Order ID: {$orderId} | Order Customer ID: {$customerId} | Customer: {$customerName}");
        }

        $this->newLine();
        $this->info("=== CUSTOMER QUOTES WITH SCOPE (AS THIS USER) ===");
        
        // Simulate login as this user
        Auth::login($user);
        
        $scopedQuotes = CustomerQuote::with(['order' => function($q) {
            $q->withoutGlobalScopes()->with('customer');
        }])->get();
        
        if ($scopedQuotes->isEmpty()) {
            $this->error("NO QUOTES FOUND WITH SCOPE!");
        } else {
            foreach ($scopedQuotes as $quote) {
                $order = $quote->order;
                $customer = $order ? $order->customer : null;
                $customerId = $order ? $order->customer_id : 'NULL';
                $customerName = $customer ? $customer->name : 'N/A';
                $orderId = $order ? $order->id : 'NULL';
                
                $this->line("Quote: {$quote->quote_number} | Order ID: {$orderId} | Order Customer ID: {$customerId} | Customer: {$customerName}");
            }
        }

        $this->newLine();
        $this->info("=== DIAGNOSIS ===");
        
        // Test without Order scope
        $quotesWithoutOrderScope = CustomerQuote::withoutGlobalScopes()
            ->whereHas('order', function ($query) use ($user) {
                $query->withoutGlobalScopes()->where('customer_id', $user->client_id);
            })
            ->count();
        $this->line("CustomerQuotes (bypassing Order scope): {$quotesWithoutOrderScope}");
        
        if ($user->client_id) {
            $matchingOrders = Order::where('customer_id', $user->client_id)->count();
            $this->line("Orders with customer_id = {$user->client_id}: {$matchingOrders}");
            
            $matchingQuotes = CustomerQuote::withoutGlobalScopes()
                ->whereHas('order', function ($query) use ($user) {
                    $query->where('customer_id', $user->client_id);
                })
                ->count();
            $this->line("CustomerQuotes with matching customer_id: {$matchingQuotes}");
        } else {
            $this->error("User has no client_id set!");
        }

        Auth::logout();
        
        return 0;
    }
}
