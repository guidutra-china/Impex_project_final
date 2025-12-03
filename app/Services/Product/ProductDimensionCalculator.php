<?php

namespace App\Services\Product;

use App\Models\Product;

/**
 * ProductDimensionCalculator
 * 
 * Encapsula toda a lógica de cálculo de dimensões e volume (CBM).
 * Responsável por:
 * - Cálculo de CBM do produto
 * - Cálculo de CBM da caixa interna
 * - Cálculo de CBM do carton
 */
class ProductDimensionCalculator
{
    /**
     * Calcula o CBM (metros cúbicos) do produto
     * 
     * Fórmula: (comprimento × largura × altura) / 1.000.000
     */
    public function getProductCbm(Product $product): ?float
    {
        if ($product->product_length && $product->product_width && $product->product_height) {
            return round(
                ($product->product_length * $product->product_width * $product->product_height) / 1000000,
                4
            );
        }

        return null;
    }

    /**
     * Calcula o CBM da caixa interna
     * 
     * Fórmula: (comprimento × largura × altura) / 1.000.000
     */
    public function getInnerBoxCbm(Product $product): ?float
    {
        if ($product->inner_box_length && $product->inner_box_width && $product->inner_box_height) {
            return round(
                ($product->inner_box_length * $product->inner_box_width * $product->inner_box_height) / 1000000,
                4
            );
        }

        return null;
    }

    /**
     * Calcula o CBM do carton
     * 
     * Fórmula: (comprimento × largura × altura) / 1.000.000
     */
    public function calculateCartonCbm(Product $product): ?float
    {
        if ($product->carton_length && $product->carton_width && $product->carton_height) {
            return round(
                ($product->carton_length * $product->carton_width * $product->carton_height) / 1000000,
                4
            );
        }

        return null;
    }

    /**
     * Auto-calcula e atualiza o CBM do carton se as dimensões forem fornecidas
     */
    public function updateCartonCbm(Product $product): void
    {
        $cbm = $this->calculateCartonCbm($product);
        
        if ($cbm !== null) {
            $product->carton_cbm = $cbm;
        }
    }
}
