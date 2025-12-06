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

    /**
     * Calcula o peso estimado do carton baseado no peso unitário
     * 
     * Fórmula: (Unit Gross Weight × Pieces per Carton) + peso da embalagem (estimado 10%)
     * 
     * @return float|null
     */
    public function estimateCartonWeight(Product $product): ?float
    {
        if ($product->gross_weight && $product->pcs_per_carton) {
            $productsWeight = $product->gross_weight * $product->pcs_per_carton;
            $packagingWeight = $productsWeight * 0.10; // Estimativa de 10% para embalagem
            
            return round($productsWeight + $packagingWeight, 3);
        }

        return null;
    }

    /**
     * Auto-calcula pcs_per_carton quando inner boxes são usados
     * 
     * Fórmula: Pieces per Inner Box × Inner Boxes per Carton
     */
    public function updatePcsPerCarton(Product $product): void
    {
        if ($product->pcs_per_inner_box && $product->inner_boxes_per_carton) {
            $product->pcs_per_carton = $product->pcs_per_inner_box * $product->inner_boxes_per_carton;
        }
    }

    /**
     * Valida consistência entre campos de embalagem
     * 
     * @return array Array de mensagens de aviso (vazio se tudo OK)
     */
    public function validatePackaging(Product $product): array
    {
        $warnings = [];

        // Validação 1: Se tem inner boxes, deve ter inner_boxes_per_carton
        if ($product->pcs_per_inner_box && !$product->inner_boxes_per_carton) {
            $warnings[] = 'You have Pieces per Inner Box but no Inner Boxes per Carton';
        }

        // Validação 2: Se tem inner_boxes_per_carton, deve ter pcs_per_inner_box
        if ($product->inner_boxes_per_carton && !$product->pcs_per_inner_box) {
            $warnings[] = 'You have Inner Boxes per Carton but no Pieces per Inner Box';
        }

        // Validação 3: Carton weight deve ser maior que unit weight × pcs_per_carton
        if ($product->gross_weight && $product->pcs_per_carton && $product->carton_weight) {
            $minCartonWeight = $product->gross_weight * $product->pcs_per_carton;
            if ($product->carton_weight < $minCartonWeight) {
                $warnings[] = sprintf(
                    'Carton Gross Weight (%.3f kg) is less than Unit Weight × Pieces (%.3f kg)',
                    $product->carton_weight,
                    $minCartonWeight
                );
            }
        }

        return $warnings;
    }
}
