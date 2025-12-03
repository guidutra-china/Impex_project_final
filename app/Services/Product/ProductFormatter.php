<?php

namespace App\Services\Product;

/**
 * ProductFormatter
 * 
 * Encapsula toda a lógica de formatação de valores de produtos.
 * Responsável por:
 * - Formatação de preços
 * - Formatação de pesos
 * - Formatação de dimensões
 */
class ProductFormatter
{
    /**
     * Formata um preço em centavos para formato de moeda
     * 
     * @param int|null $price Preço em centavos
     * @return string Preço formatado (ex: "$1,234.56")
     */
    public function formatPrice(?int $price): string
    {
        if ($price === null) {
            return '-';
        }

        return '$' . number_format($price / 100, 2);
    }

    /**
     * Formata um peso em gramas para formato legível
     * 
     * @param float|null $weight Peso em gramas
     * @return string Peso formatado (ex: "1.5 kg" ou "500 g")
     */
    public function formatWeight(?float $weight): string
    {
        if ($weight === null) {
            return '-';
        }

        if ($weight >= 1000) {
            return number_format($weight / 1000, 2) . ' kg';
        }

        return number_format($weight, 2) . ' g';
    }

    /**
     * Formata dimensões em um formato legível
     * 
     * @param float|null $length Comprimento em cm
     * @param float|null $width Largura em cm
     * @param float|null $height Altura em cm
     * @return string Dimensões formatadas (ex: "10 × 20 × 30 cm")
     */
    public function formatDimensions(?float $length, ?float $width, ?float $height): string
    {
        if ($length === null || $width === null || $height === null) {
            return '-';
        }

        return number_format($length, 2) . ' × ' . 
               number_format($width, 2) . ' × ' . 
               number_format($height, 2) . ' cm';
    }

    /**
     * Formata CBM (metros cúbicos) para formato legível
     * 
     * @param float|null $cbm CBM
     * @return string CBM formatado (ex: "0.0012 m³")
     */
    public function formatCbm(?float $cbm): string
    {
        if ($cbm === null) {
            return '-';
        }

        return number_format($cbm, 4) . ' m³';
    }

    /**
     * Formata quantidade com unidade de medida
     * 
     * @param int|null $quantity Quantidade
     * @param string|null $unit Unidade de medida
     * @return string Quantidade formatada (ex: "100 pcs")
     */
    public function formatQuantity(?int $quantity, ?string $unit = null): string
    {
        if ($quantity === null) {
            return '-';
        }

        if ($unit === null) {
            return (string) $quantity;
        }

        return $quantity . ' ' . $unit;
    }
}
