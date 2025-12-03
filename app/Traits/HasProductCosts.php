<?php

namespace App\Traits;

use App\Services\Product\ProductCostCalculator;

/**
 * HasProductCosts
 * 
 * Trait que encapsula métodos relacionados a custos de produtos.
 * Delega a lógica para ProductCostCalculator.
 * 
 * Uso: use HasProductCosts; no model Product
 */
trait HasProductCosts
{
    /**
     * Calcula o custo total de material do BOM
     */
    public function calculateBomMaterialCost(): int
    {
        return app(ProductCostCalculator::class)->calculateBomMaterialCost($this);
    }

    /**
     * Calcula e atualiza o custo de manufatura total
     */
    public function calculateManufacturingCost(): void
    {
        app(ProductCostCalculator::class)->calculateManufacturingCost($this);
    }

    /**
     * Calcula e atualiza todos os custos (alias para calculateManufacturingCost)
     */
    public function calculateAndUpdateCosts(): void
    {
        $this->calculateManufacturingCost();
    }

    /**
     * Obtém o custo de material BOM em dólares
     */
    public function getBomMaterialCostDollarsAttribute(): float
    {
        return app(ProductCostCalculator::class)->getBomMaterialCostDollars($this);
    }

    /**
     * Obtém o custo de mão de obra direta em dólares
     */
    public function getDirectLaborCostDollarsAttribute(): float
    {
        return app(ProductCostCalculator::class)->getDirectLaborCostDollars($this);
    }

    /**
     * Obtém o custo de overhead direto em dólares
     */
    public function getDirectOverheadCostDollarsAttribute(): float
    {
        return app(ProductCostCalculator::class)->getDirectOverheadCostDollars($this);
    }

    /**
     * Obtém o custo total de manufatura em dólares
     */
    public function getTotalManufacturingCostDollarsAttribute(): float
    {
        return app(ProductCostCalculator::class)->getTotalManufacturingCostDollars($this);
    }

    /**
     * Obtém o preço de venda calculado em dólares
     */
    public function getCalculatedSellingPriceDollarsAttribute(): float
    {
        return app(ProductCostCalculator::class)->getCalculatedSellingPriceDollars($this);
    }
}
