<?php

namespace Database\Seeders;

use App\Models\FinancialCategory;
use Illuminate\Database\Seeder;

class FinancialCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Clear existing categories
        FinancialCategory::query()->delete();

        // === EXPENSES ===
        
        // Fixed Costs
        $fixedCosts = FinancialCategory::create([
            'name' => 'Custos Fixos',
            'code' => 'COST-FIXED',
            'description' => 'Despesas fixas mensais',
            'type' => 'expense',
            'is_system' => true,
            'is_active' => true,
            'sort_order' => 100,
        ]);

        FinancialCategory::create([
            'name' => 'Aluguel',
            'code' => 'COST-FIX-RENT',
            'type' => 'expense',
            'parent_id' => $fixedCosts->id,
            'is_active' => true,
            'sort_order' => 101,
        ]);

        FinancialCategory::create([
            'name' => 'Salários',
            'code' => 'COST-FIX-SALARY',
            'type' => 'expense',
            'parent_id' => $fixedCosts->id,
            'is_active' => true,
            'sort_order' => 102,
        ]);

        FinancialCategory::create([
            'name' => 'Impostos Fixos',
            'code' => 'COST-FIX-TAX',
            'type' => 'expense',
            'parent_id' => $fixedCosts->id,
            'is_active' => true,
            'sort_order' => 103,
        ]);

        FinancialCategory::create([
            'name' => 'Seguros',
            'code' => 'COST-FIX-INSURANCE',
            'type' => 'expense',
            'parent_id' => $fixedCosts->id,
            'is_active' => true,
            'sort_order' => 104,
        ]);

        // Variable Costs
        $variableCosts = FinancialCategory::create([
            'name' => 'Custos Variáveis',
            'code' => 'COST-VARIABLE',
            'description' => 'Despesas variáveis de acordo com produção/vendas',
            'type' => 'expense',
            'is_system' => true,
            'is_active' => true,
            'sort_order' => 200,
        ]);

        FinancialCategory::create([
            'name' => 'Compras de Matéria-Prima',
            'code' => 'COST-VAR-PURCHASE',
            'type' => 'expense',
            'parent_id' => $variableCosts->id,
            'is_system' => true,
            'is_active' => true,
            'sort_order' => 201,
        ]);

        FinancialCategory::create([
            'name' => 'Frete',
            'code' => 'COST-VAR-FREIGHT',
            'type' => 'expense',
            'parent_id' => $variableCosts->id,
            'is_active' => true,
            'sort_order' => 202,
        ]);

        FinancialCategory::create([
            'name' => 'Comissões de Vendas',
            'code' => 'COST-VAR-COMMISSION',
            'type' => 'expense',
            'parent_id' => $variableCosts->id,
            'is_active' => true,
            'sort_order' => 203,
        ]);

        FinancialCategory::create([
            'name' => 'Embalagens',
            'code' => 'COST-VAR-PACKAGING',
            'type' => 'expense',
            'parent_id' => $variableCosts->id,
            'is_active' => true,
            'sort_order' => 204,
        ]);

        // Operating Expenses
        $operatingExpenses = FinancialCategory::create([
            'name' => 'Despesas Operacionais',
            'code' => 'EXPENSE-OPERATING',
            'description' => 'Despesas gerais de operação',
            'type' => 'expense',
            'is_system' => true,
            'is_active' => true,
            'sort_order' => 300,
        ]);

        FinancialCategory::create([
            'name' => 'Marketing',
            'code' => 'EXP-OP-MARKETING',
            'type' => 'expense',
            'parent_id' => $operatingExpenses->id,
            'is_active' => true,
            'sort_order' => 301,
        ]);

        FinancialCategory::create([
            'name' => 'Viagens',
            'code' => 'EXP-OP-TRAVEL',
            'type' => 'expense',
            'parent_id' => $operatingExpenses->id,
            'is_active' => true,
            'sort_order' => 302,
        ]);

        FinancialCategory::create([
            'name' => 'Telefone/Internet',
            'code' => 'EXP-OP-TELECOM',
            'type' => 'expense',
            'parent_id' => $operatingExpenses->id,
            'is_active' => true,
            'sort_order' => 303,
        ]);

        FinancialCategory::create([
            'name' => 'Material de Escritório',
            'code' => 'EXP-OP-OFFICE',
            'type' => 'expense',
            'parent_id' => $operatingExpenses->id,
            'is_active' => true,
            'sort_order' => 304,
        ]);

        FinancialCategory::create([
            'name' => 'Manutenção',
            'code' => 'EXP-OP-MAINTENANCE',
            'type' => 'expense',
            'parent_id' => $operatingExpenses->id,
            'is_active' => true,
            'sort_order' => 305,
        ]);

        // === REVENUES ===
        
        $salesRevenue = FinancialCategory::create([
            'name' => 'Receita de Vendas',
            'code' => 'REV-SALES',
            'description' => 'Receitas provenientes de vendas',
            'type' => 'revenue',
            'is_system' => true,
            'is_active' => true,
            'sort_order' => 400,
        ]);

        FinancialCategory::create([
            'name' => 'Vendas Nacionais',
            'code' => 'REV-SALES-DOMESTIC',
            'type' => 'revenue',
            'parent_id' => $salesRevenue->id,
            'is_active' => true,
            'sort_order' => 401,
        ]);

        FinancialCategory::create([
            'name' => 'Vendas Exportação',
            'code' => 'REV-SALES-EXPORT',
            'type' => 'revenue',
            'parent_id' => $salesRevenue->id,
            'is_active' => true,
            'sort_order' => 402,
        ]);

        $otherRevenue = FinancialCategory::create([
            'name' => 'Outras Receitas',
            'code' => 'REV-OTHER',
            'description' => 'Receitas não operacionais',
            'type' => 'revenue',
            'is_system' => true,
            'is_active' => true,
            'sort_order' => 500,
        ]);

        FinancialCategory::create([
            'name' => 'Receitas Financeiras',
            'code' => 'REV-OTH-FINANCIAL',
            'type' => 'revenue',
            'parent_id' => $otherRevenue->id,
            'is_active' => true,
            'sort_order' => 501,
        ]);

        FinancialCategory::create([
            'name' => 'Descontos Obtidos',
            'code' => 'REV-OTH-DISCOUNT',
            'type' => 'revenue',
            'parent_id' => $otherRevenue->id,
            'is_active' => true,
            'sort_order' => 502,
        ]);

        // === EXCHANGE VARIATION ===
        
        $exchangeVariation = FinancialCategory::create([
            'name' => 'Variação Cambial',
            'code' => 'EXCHANGE-VAR',
            'description' => 'Ganhos e perdas com variação cambial',
            'type' => 'exchange_variation',
            'is_system' => true,
            'is_active' => true,
            'sort_order' => 600,
        ]);

        FinancialCategory::create([
            'name' => 'Ganhos Cambiais',
            'code' => 'EXCHANGE-GAIN',
            'type' => 'exchange_variation',
            'parent_id' => $exchangeVariation->id,
            'is_system' => true,
            'is_active' => true,
            'sort_order' => 601,
        ]);

        FinancialCategory::create([
            'name' => 'Perdas Cambiais',
            'code' => 'EXCHANGE-LOSS',
            'type' => 'exchange_variation',
            'parent_id' => $exchangeVariation->id,
            'is_system' => true,
            'is_active' => true,
            'sort_order' => 602,
        ]);

        // === RFQ/PROJECT EXPENSES ===
        
        $rfqExpenses = FinancialCategory::create([
            'name' => 'RFQ/Project Expenses',
            'code' => 'RFQ-EXPENSES',
            'description' => 'Specific expenses for RFQs and import/export projects',
            'type' => 'expense',
            'is_system' => true,
            'is_active' => true,
            'sort_order' => 250,
        ]);

        FinancialCategory::create([
            'name' => 'Tests and Certifications',
            'code' => 'RFQ-EXP-TESTS',
            'description' => 'Quality tests, certifications, technical reports',
            'type' => 'expense',
            'parent_id' => $rfqExpenses->id,
            'is_active' => true,
            'sort_order' => 251,
        ]);

        FinancialCategory::create([
            'name' => 'Business Travel',
            'code' => 'RFQ-EXP-TRAVEL',
            'description' => 'Supplier visits, trade shows, factory inspections',
            'type' => 'expense',
            'parent_id' => $rfqExpenses->id,
            'is_active' => true,
            'sort_order' => 252,
        ]);

        FinancialCategory::create([
            'name' => 'Third-Party Services',
            'code' => 'RFQ-EXP-THIRD-PARTY',
            'description' => 'Customs brokers, consultants, translators, lawyers',
            'type' => 'expense',
            'parent_id' => $rfqExpenses->id,
            'is_active' => true,
            'sort_order' => 253,
        ]);

        FinancialCategory::create([
            'name' => 'Bank Costs',
            'code' => 'RFQ-EXP-BANK',
            'description' => 'International transfers, letters of credit, foreign exchange',
            'type' => 'expense',
            'parent_id' => $rfqExpenses->id,
            'is_active' => true,
            'sort_order' => 254,
        ]);

        FinancialCategory::create([
            'name' => 'Samples',
            'code' => 'RFQ-EXP-SAMPLES',
            'description' => 'Sample shipping, prototypes',
            'type' => 'expense',
            'parent_id' => $rfqExpenses->id,
            'is_active' => true,
            'sort_order' => 255,
        ]);

        FinancialCategory::create([
            'name' => 'Documentation',
            'code' => 'RFQ-EXP-DOCS',
            'description' => 'Legalization, apostille, certificates of origin',
            'type' => 'expense',
            'parent_id' => $rfqExpenses->id,
            'is_active' => true,
            'sort_order' => 256,
        ]);

        FinancialCategory::create([
            'name' => 'Temporary Storage',
            'code' => 'RFQ-EXP-STORAGE',
            'description' => 'Warehouse before final shipment',
            'type' => 'expense',
            'parent_id' => $rfqExpenses->id,
            'is_active' => true,
            'sort_order' => 257,
        ]);

        FinancialCategory::create([
            'name' => 'Specific Insurance',
            'code' => 'RFQ-EXP-INSURANCE',
            'description' => 'RFQ/project specific insurance',
            'type' => 'expense',
            'parent_id' => $rfqExpenses->id,
            'is_active' => true,
            'sort_order' => 258,
        ]);

        $this->command->info('✅ Financial categories seeded successfully!');
        $this->command->info('   - ' . FinancialCategory::whereNull('parent_id')->count() . ' root categories');
        $this->command->info('   - ' . FinancialCategory::whereNotNull('parent_id')->count() . ' sub-categories');
        $this->command->info('   - Total: ' . FinancialCategory::count() . ' categories');
    }
}
