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
     */
    protected static function bootHasProductDimensions()
    {
        static::saving(function ($product) {
            app(ProductDimensionCalculator::class)->updateCartonCbm($product);
        });
    }
}
