<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                Welcome to Customer Portal
            </h2>
            <p class="text-gray-600 dark:text-gray-300">
                Access your quotes, orders, invoices, and shipments.
            </p>
        </div>

        @if (auth()->user()->hasRole('purchasing'))
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-2">
                        Customer Quotes
                    </h3>
                    <p class="text-blue-700 dark:text-blue-300 text-sm">
                        View and select quote options
                    </p>
                </div>
                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-green-900 dark:text-green-100 mb-2">
                        Purchase Orders
                    </h3>
                    <p class="text-green-700 dark:text-green-300 text-sm">
                        Track your purchase orders
                    </p>
                </div>
            </div>
        @endif

        @if (auth()->user()->hasRole('finance'))
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-purple-900 dark:text-purple-100 mb-2">
                        Proforma Invoices
                    </h3>
                    <p class="text-purple-700 dark:text-purple-300 text-sm">
                        View and manage invoices
                    </p>
                </div>
                <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-yellow-900 dark:text-yellow-100 mb-2">
                        Payments
                    </h3>
                    <p class="text-yellow-700 dark:text-yellow-300 text-sm">
                        Track payment status
                    </p>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
