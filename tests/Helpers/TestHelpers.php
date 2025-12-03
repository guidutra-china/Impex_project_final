<?php

namespace Tests\Helpers;

use App\Models\Client;
use App\Models\Currency;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierQuote;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;

/**
 * Test Helpers
 * 
 * Provides utility functions for creating test data and setting up test scenarios.
 * These helpers reduce boilerplate and make tests more readable and maintainable.
 */
class TestHelpers
{
    use WithFaker;

    /**
     * Create a test user with optional roles
     */
    public static function createTestUser(array $attributes = [], array $roles = []): User
    {
        $user = User::factory()->create($attributes);

        if (!empty($roles)) {
            $user->assignRole($roles);
        }

        return $user;
    }

    /**
     * Create a test client with a user
     */
    public static function createTestClient(User $user = null, array $attributes = []): Client
    {
        if (!$user) {
            $user = self::createTestUser();
        }

        return Client::factory()->create(array_merge([
            'user_id' => $user->id,
        ], $attributes));
    }

    /**
     * Create a test product with category
     */
    public static function createTestProduct(array $attributes = []): Product
    {
        return Product::factory()->create($attributes);
    }

    /**
     * Create a test supplier
     */
    public static function createTestSupplier(array $attributes = []): Supplier
    {
        return Supplier::factory()->create($attributes);
    }

    /**
     * Create a test currency
     */
    public static function createTestCurrency(array $attributes = []): Currency
    {
        return Currency::factory()->create(array_merge([
            'is_active' => true,
        ], $attributes));
    }

    /**
     * Create a complete RFQ (Order) with items
     */
    public static function createTestRFQWithItems(
        Client $client = null,
        Currency $currency = null,
        int $itemCount = 2,
        array $orderAttributes = [],
        array $itemAttributes = []
    ): Order {
        if (!$client) {
            $client = self::createTestClient();
        }

        if (!$currency) {
            $currency = Currency::where('code', 'USD')->first() ?? self::createTestCurrency(['code' => 'USD']);
        }

        $order = Order::factory()->create(array_merge([
            'customer_id' => $client->id,
            'currency_id' => $currency->id,
            'status' => 'pending',
        ], $orderAttributes));

        for ($i = 0; $i < $itemCount; $i++) {
            OrderItem::factory()->create(array_merge([
                'order_id' => $order->id,
                'product_id' => self::createTestProduct()->id,
                'quantity' => rand(10, 100),
            ], $itemAttributes));
        }

        return $order->refresh();
    }

    /**
     * Create a supplier quote for an order
     */
    public static function createTestSupplierQuote(
        Order $order = null,
        Supplier $supplier = null,
        Currency $currency = null,
        array $attributes = []
    ): SupplierQuote {
        if (!$order) {
            $order = self::createTestRFQWithItems();
        }

        if (!$supplier) {
            $supplier = self::createTestSupplier();
        }

        if (!$currency) {
            $currency = $order->currency;
        }

        return SupplierQuote::factory()->create(array_merge([
            'order_id' => $order->id,
            'supplier_id' => $supplier->id,
            'currency_id' => $currency->id,
            'status' => 'draft',
        ], $attributes));
    }

    /**
     * Create multiple supplier quotes for comparison
     */
    public static function createTestQuotesForComparison(
        Order $order = null,
        int $supplierCount = 3,
        Currency $currency = null
    ): array {
        if (!$order) {
            $order = self::createTestRFQWithItems();
        }

        if (!$currency) {
            $currency = $order->currency;
        }

        $quotes = [];
        for ($i = 0; $i < $supplierCount; $i++) {
            $supplier = self::createTestSupplier();
            $quote = self::createTestSupplierQuote($order, $supplier, $currency);
            $quotes[] = $quote;
        }

        return $quotes;
    }

    /**
     * Authenticate a user for testing
     */
    public static function authenticateAs(User $user = null): User
    {
        if (!$user) {
            $user = self::createTestUser();
        }

        auth()->login($user);

        return $user;
    }

    /**
     * Create a test scenario with a complete RFQ workflow
     */
    public static function createCompleteRFQScenario(): array
    {
        $user = self::createTestUser();
        $client = self::createTestClient($user);
        $currency = self::createTestCurrency(['code' => 'USD']);
        $order = self::createTestRFQWithItems($client, $currency, 2);
        $suppliers = [
            self::createTestSupplier(),
            self::createTestSupplier(),
            self::createTestSupplier(),
        ];

        $quotes = [];
        foreach ($suppliers as $supplier) {
            $quote = self::createTestSupplierQuote($order, $supplier, $currency);
            $quotes[] = $quote;
        }

        return [
            'user' => $user,
            'client' => $client,
            'currency' => $currency,
            'order' => $order,
            'suppliers' => $suppliers,
            'quotes' => $quotes,
        ];
    }
}
