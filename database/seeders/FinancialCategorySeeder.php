<?php

namespace Database\Seeders;

use App\Models\FinancialCategory;
use Illuminate\Database\Seeder;

class FinancialCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            // ========================================
            // REVENUE CATEGORIES
            // ========================================
            [
                'name' => 'Sales Revenue',
                'code' => 'REV-001',
                'type' => 'revenue',
                'parent_id' => null,
                'is_system' => true,
                'children' => [
                    ['name' => 'Product Sales', 'code' => 'REV-001-01', 'type' => 'revenue'],
                    ['name' => 'Service Revenue', 'code' => 'REV-001-02', 'type' => 'revenue'],
                    ['name' => 'Export Sales', 'code' => 'REV-001-03', 'type' => 'revenue'],
                    ['name' => 'Domestic Sales', 'code' => 'REV-001-04', 'type' => 'revenue'],
                ],
            ],
            [
                'name' => 'Other Income',
                'code' => 'REV-002',
                'type' => 'revenue',
                'parent_id' => null,
                'is_system' => true,
                'children' => [
                    ['name' => 'Interest Income', 'code' => 'REV-002-01', 'type' => 'revenue'],
                    ['name' => 'Dividend Income', 'code' => 'REV-002-02', 'type' => 'revenue'],
                    ['name' => 'Rental Income', 'code' => 'REV-002-03', 'type' => 'revenue'],
                    ['name' => 'Commission Income', 'code' => 'REV-002-04', 'type' => 'revenue'],
                ],
            ],

            // ========================================
            // EXPENSE CATEGORIES - COST OF GOODS SOLD
            // ========================================
            [
                'name' => 'Cost of Goods Sold',
                'code' => 'EXP-001',
                'type' => 'expense',
                'parent_id' => null,
                'is_system' => true,
                'children' => [
                    ['name' => 'Product Purchases', 'code' => 'EXP-001-01', 'type' => 'expense'],
                    ['name' => 'Raw Materials', 'code' => 'EXP-001-02', 'type' => 'expense'],
                    ['name' => 'Manufacturing Costs', 'code' => 'EXP-001-03', 'type' => 'expense'],
                    ['name' => 'Inventory Adjustments', 'code' => 'EXP-001-04', 'type' => 'expense'],
                ],
            ],

            // ========================================
            // EXPENSE CATEGORIES - SHIPPING & LOGISTICS
            // ========================================
            [
                'name' => 'Shipping & Logistics',
                'code' => 'EXP-002',
                'type' => 'expense',
                'parent_id' => null,
                'is_system' => true,
                'children' => [
                    ['name' => 'International Freight', 'code' => 'EXP-002-01', 'type' => 'expense'],
                    ['name' => 'Domestic Freight', 'code' => 'EXP-002-02', 'type' => 'expense'],
                    ['name' => 'Courier Services', 'code' => 'EXP-002-03', 'type' => 'expense'],
                    ['name' => 'Warehousing', 'code' => 'EXP-002-04', 'type' => 'expense'],
                    ['name' => 'Packaging Materials', 'code' => 'EXP-002-05', 'type' => 'expense'],
                ],
            ],

            // ========================================
            // EXPENSE CATEGORIES - CUSTOMS & DUTIES
            // ========================================
            [
                'name' => 'Customs & Duties',
                'code' => 'EXP-003',
                'type' => 'expense',
                'parent_id' => null,
                'is_system' => true,
                'children' => [
                    ['name' => 'Import Duties', 'code' => 'EXP-003-01', 'type' => 'expense'],
                    ['name' => 'Export Duties', 'code' => 'EXP-003-02', 'type' => 'expense'],
                    ['name' => 'Customs Clearance Fees', 'code' => 'EXP-003-03', 'type' => 'expense'],
                    ['name' => 'Customs Broker Fees', 'code' => 'EXP-003-04', 'type' => 'expense'],
                    ['name' => 'Import VAT', 'code' => 'EXP-003-05', 'type' => 'expense'],
                ],
            ],

            // ========================================
            // EXPENSE CATEGORIES - OPERATING EXPENSES
            // ========================================
            [
                'name' => 'Salaries & Wages',
                'code' => 'EXP-004',
                'type' => 'expense',
                'parent_id' => null,
                'is_system' => true,
                'children' => [
                    ['name' => 'Employee Salaries', 'code' => 'EXP-004-01', 'type' => 'expense'],
                    ['name' => 'Bonuses', 'code' => 'EXP-004-02', 'type' => 'expense'],
                    ['name' => 'Commissions', 'code' => 'EXP-004-03', 'type' => 'expense'],
                    ['name' => 'Payroll Taxes', 'code' => 'EXP-004-04', 'type' => 'expense'],
                    ['name' => 'Benefits', 'code' => 'EXP-004-05', 'type' => 'expense'],
                ],
            ],
            [
                'name' => 'Office Expenses',
                'code' => 'EXP-005',
                'type' => 'expense',
                'parent_id' => null,
                'is_system' => true,
                'children' => [
                    ['name' => 'Rent', 'code' => 'EXP-005-01', 'type' => 'expense'],
                    ['name' => 'Utilities', 'code' => 'EXP-005-02', 'type' => 'expense'],
                    ['name' => 'Office Supplies', 'code' => 'EXP-005-03', 'type' => 'expense'],
                    ['name' => 'Equipment', 'code' => 'EXP-005-04', 'type' => 'expense'],
                    ['name' => 'Maintenance', 'code' => 'EXP-005-05', 'type' => 'expense'],
                ],
            ],
            [
                'name' => 'Professional Services',
                'code' => 'EXP-006',
                'type' => 'expense',
                'parent_id' => null,
                'is_system' => true,
                'children' => [
                    ['name' => 'Legal Fees', 'code' => 'EXP-006-01', 'type' => 'expense'],
                    ['name' => 'Accounting Fees', 'code' => 'EXP-006-02', 'type' => 'expense'],
                    ['name' => 'Consulting Fees', 'code' => 'EXP-006-03', 'type' => 'expense'],
                    ['name' => 'Audit Fees', 'code' => 'EXP-006-04', 'type' => 'expense'],
                ],
            ],
            [
                'name' => 'Marketing & Advertising',
                'code' => 'EXP-007',
                'type' => 'expense',
                'parent_id' => null,
                'is_system' => true,
                'children' => [
                    ['name' => 'Digital Marketing', 'code' => 'EXP-007-01', 'type' => 'expense'],
                    ['name' => 'Print Advertising', 'code' => 'EXP-007-02', 'type' => 'expense'],
                    ['name' => 'Trade Shows', 'code' => 'EXP-007-03', 'type' => 'expense'],
                    ['name' => 'Promotional Materials', 'code' => 'EXP-007-04', 'type' => 'expense'],
                ],
            ],
            [
                'name' => 'Technology & Software',
                'code' => 'EXP-008',
                'type' => 'expense',
                'parent_id' => null,
                'is_system' => true,
                'children' => [
                    ['name' => 'Software Licenses', 'code' => 'EXP-008-01', 'type' => 'expense'],
                    ['name' => 'Cloud Services', 'code' => 'EXP-008-02', 'type' => 'expense'],
                    ['name' => 'IT Support', 'code' => 'EXP-008-03', 'type' => 'expense'],
                    ['name' => 'Hardware', 'code' => 'EXP-008-04', 'type' => 'expense'],
                ],
            ],
            [
                'name' => 'Travel & Entertainment',
                'code' => 'EXP-009',
                'type' => 'expense',
                'parent_id' => null,
                'is_system' => true,
                'children' => [
                    ['name' => 'Airfare', 'code' => 'EXP-009-01', 'type' => 'expense'],
                    ['name' => 'Hotels', 'code' => 'EXP-009-02', 'type' => 'expense'],
                    ['name' => 'Meals', 'code' => 'EXP-009-03', 'type' => 'expense'],
                    ['name' => 'Ground Transportation', 'code' => 'EXP-009-04', 'type' => 'expense'],
                    ['name' => 'Client Entertainment', 'code' => 'EXP-009-05', 'type' => 'expense'],
                ],
            ],

            // ========================================
            // EXPENSE CATEGORIES - FINANCIAL
            // ========================================
            [
                'name' => 'Financial Expenses',
                'code' => 'EXP-010',
                'type' => 'expense',
                'parent_id' => null,
                'is_system' => true,
                'children' => [
                    ['name' => 'Bank Fees', 'code' => 'EXP-010-01', 'type' => 'expense'],
                    ['name' => 'Interest Expense', 'code' => 'EXP-010-02', 'type' => 'expense'],
                    ['name' => 'Credit Card Fees', 'code' => 'EXP-010-03', 'type' => 'expense'],
                    ['name' => 'Payment Processing Fees', 'code' => 'EXP-010-04', 'type' => 'expense'],
                    ['name' => 'Currency Exchange Loss', 'code' => 'EXP-010-05', 'type' => 'expense'],
                ],
            ],
            [
                'name' => 'Insurance',
                'code' => 'EXP-011',
                'type' => 'expense',
                'parent_id' => null,
                'is_system' => true,
                'children' => [
                    ['name' => 'Cargo Insurance', 'code' => 'EXP-011-01', 'type' => 'expense'],
                    ['name' => 'Business Insurance', 'code' => 'EXP-011-02', 'type' => 'expense'],
                    ['name' => 'Liability Insurance', 'code' => 'EXP-011-03', 'type' => 'expense'],
                    ['name' => 'Health Insurance', 'code' => 'EXP-011-04', 'type' => 'expense'],
                ],
            ],
            [
                'name' => 'Taxes',
                'code' => 'EXP-012',
                'type' => 'expense',
                'parent_id' => null,
                'is_system' => true,
                'children' => [
                    ['name' => 'Income Tax', 'code' => 'EXP-012-01', 'type' => 'expense'],
                    ['name' => 'Property Tax', 'code' => 'EXP-012-02', 'type' => 'expense'],
                    ['name' => 'Business License Fees', 'code' => 'EXP-012-03', 'type' => 'expense'],
                    ['name' => 'Sales Tax', 'code' => 'EXP-012-04', 'type' => 'expense'],
                ],
            ],

            // ========================================
            // EXCHANGE VARIATION (Special)
            // ========================================
            [
                'name' => 'Currency Exchange Gain',
                'code' => 'REV-003',
                'type' => 'exchange_variation',
                'parent_id' => null,
                'is_system' => true,
                'children' => [],
            ],
            [
                'name' => 'Currency Exchange Loss',
                'code' => 'EXP-013',
                'type' => 'exchange_variation',
                'parent_id' => null,
                'is_system' => true,
                'children' => [],
            ],
        ];

        $this->createCategories($categories);

        $this->command->info('✅ Financial Categories seeded successfully!');
        $this->command->info('📊 Created ' . FinancialCategory::count() . ' categories');
    }

    /**
     * Recursively create categories and their children
     */
    private function createCategories(array $categories, ?int $parentId = null): void
    {
        foreach ($categories as $categoryData) {
            $children = $categoryData['children'] ?? [];
            unset($categoryData['children']);

            $category = FinancialCategory::create([
                ...$categoryData,
                'parent_id' => $parentId,
            ]);

            if (!empty($children)) {
                $this->createCategories($children, $category->id);
            }
        }
    }
}
