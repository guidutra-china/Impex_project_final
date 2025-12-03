<?php

namespace App\Services\Product;

use App\Models\Product;

/**
 * ProductCostCalculator
 * 
 * Encapsula toda a lógica de cálculo de custos e preços de produtos.
 * Responsável por:
 * - Cálculo de custo de material BOM
 * - Cálculo de custo de manufatura total
 * - Cálculo de preço de venda com markup
 * - Conversão de centavos para dólares
 */
class ProductCostCalculator
{
    /**
     * Calcula o custo total de material do BOM
     */
    public function calculateBomMaterialCost(Product $product): int
    {
        return $product->bomItems()->sum('total_cost');
    }

    /**
     * Calcula e atualiza o custo de manufatura total
     * 
     * Inclui:
     * - Custo de material BOM
     * - Custo de mão de obra direta
     * - Custo de overhead direto
     * - Preço de venda calculado com markup
     */
    public function calculateManufacturingCost(Product $product): void
    {
        // Calcula o custo de material BOM
        $product->bom_material_cost = $this->calculateBomMaterialCost($product);

        // Calcula o custo total de manufatura
        $product->total_manufacturing_cost = $product->bom_material_cost
            + ($product->direct_labor_cost ?? 0)
            + ($product->direct_overhead_cost ?? 0);

        // Calcula o preço de venda com markup
        $product->calculated_selling_price = $this->calculateSellingPrice($product);

        // Auto-sincroniza o preço com o preço de venda calculado se o produto tem BOM
        if ($product->bomItems()->count() > 0) {
            $product->price = $product->calculated_selling_price;
        }

        // Salva sem disparar eventos
        $product->saveQuietly();
    }

    /**
     * Calcula o preço de venda com markup
     */
    public function calculateSellingPrice(Product $product): int
    {
        if (($product->markup_percentage ?? 0) > 0) {
            $markupMultiplier = 1 + ($product->markup_percentage / 100);
            return (int) round($product->total_manufacturing_cost * $markupMultiplier);
        }

        return $product->total_manufacturing_cost;
    }

    /**
     * Obtém o custo de material BOM em dólares
     */
    public function getBomMaterialCostDollars(Product $product): float
    {
        return ($product->bom_material_cost ?? 0) / 100;
    }

    /**
     * Obtém o custo de mão de obra direta em dólares
     */
    public function getDirectLaborCostDollars(Product $product): float
    {
        return ($product->direct_labor_cost ?? 0) / 100;
    }

    /**
     * Obtém o custo de overhead direto em dólares
     */
    public function getDirectOverheadCostDollars(Product $product): float
    {
        return ($product->direct_overhead_cost ?? 0) / 100;
    }

    /**
     * Obtém o custo total de manufatura em dólares
     */
    public function getTotalManufacturingCostDollars(Product $product): float
    {
        return ($product->total_manufacturing_cost ?? 0) / 100;
    }

    /**
     * Obtém o preço de venda calculado em dólares
     */
    public function getCalculatedSellingPriceDollars(Product $product): float
    {
        return ($product->calculated_selling_price ?? 0) / 100;
    }
}
