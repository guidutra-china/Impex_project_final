<?php

namespace App\Traits;

use App\Services\Product\ProductDimensionCalculator;

/**
 * HasProductDimensions
 * 
 * Trait que encapsula métodos relacionados a dimensões de produtos.
 * Delega a lógica para ProductDimensionCalculator.
 * 
 * Uso: use HasProductDimensions; no model Product
 */
trait HasProductDimensions
{
    /**
     * Calcula o CBM (metros cúbicos) do produto
     */
    public function getProductCbmAttribute(): ?float
    {
        return app(ProductDimensionCalculator::class)->getProductCbm($this);
    }

    /**
     * Calcula o CBM da caixa interna
     */
    public function getInnerBoxCbmAttribute(): ?float
    {
        return app(ProductDimensionCalculator::class)->getInnerBoxCbm($this);
    }

    /**
     * Auto-calcula o CBM do carton se as dimensões forem fornecidas
     * Auto-calcula pcs_per_carton quando inner boxes são usados
     */
    protected static function bootHasProductDimensions()
    {
        static::saving(function ($product) {
            $calculator = app(ProductDimensionCalculator::class);
            
            // Auto-calcula CBM
            $calculator->updateCartonCbm($product);
            
            // Auto-calcula pcs_per_carton se inner boxes forem usados
            $calculator->updatePcsPerCarton($product);
        });
    }

    /**
     * Obtém peso estimado do carton
     */
    public function getEstimatedCartonWeightAttribute(): ?float
    {
        return app(ProductDimensionCalculator::class)->estimateCartonWeight($this);
    }

    /**
     * Valida consistência de embalagem
     */
    public function getPackagingWarningsAttribute(): array
    {
        return app(ProductDimensionCalculator::class)->validatePackaging($this);
    }
}
