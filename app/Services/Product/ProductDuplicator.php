<?php

namespace App\Services\Product;

use App\Models\BomItem;
use App\Models\Product;
use App\Models\ProductFeature;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * ProductDuplicator
 * 
 * Encapsula toda a lógica de duplicação de produtos com todas as suas relações.
 * Responsável por:
 * - Duplicação do produto base
 * - Duplicação de itens BOM
 * - Duplicação de features
 * - Duplicação de tags
 * - Duplicação de avatar
 */
class ProductDuplicator
{
    /**
     * Duplica um produto com todas as suas relações
     * 
     * @param Product $product O produto a ser duplicado
     * @param array $options Opções de duplicação
     *   - 'bom_items' (bool): Duplicar itens BOM (padrão: true)
     *   - 'features' (bool): Duplicar features (padrão: true)
     *   - 'tags' (bool): Duplicar tags (padrão: true)
     *   - 'avatar' (bool): Duplicar avatar (padrão: true)
     * 
     * @return Product O novo produto duplicado
     */
    public function duplicate(Product $product, array $options = []): Product
    {
        // Opções padrão - duplicar tudo por padrão
        $defaultOptions = [
            'bom_items' => true,
            'features' => true,
            'tags' => true,
            'avatar' => true,
        ];

        $options = array_merge($defaultOptions, $options);

        return DB::transaction(function () use ($product, $options) {
            // Carrega as relações que serão duplicadas
            $relationships = [];
            if ($options['bom_items']) {
                $relationships[] = 'bomItems';
            }
            if ($options['features']) {
                $relationships[] = 'features';
            }
            if ($options['tags']) {
                $relationships[] = 'tags';
            }

            if (!empty($relationships)) {
                $product->load($relationships);
            }

            // Prepara os dados para o novo produto
            $newProductData = $product->toArray();
            
            // Remove campos que não devem ser duplicados
            unset(
                $newProductData['id'],
                $newProductData['created_at'],
                $newProductData['updated_at'],
                $newProductData['deleted_at']
            );

            // Modifica o nome para indicar que é uma cópia
            $newProductData['name'] = $product->name . ' (Copy)';
            
            // Gera um SKU temporário (SKU original + timestamp)
            // O usuário pode alterá-lo depois para um SKU apropriado
            $newProductData['sku'] = $product->sku . '-' . now()->timestamp;

            // Duplica o avatar se a opção estiver habilitada
            if ($options['avatar'] && $product->avatar) {
                $newProductData['avatar'] = $this->duplicateAvatar($product);
            } else {
                $newProductData['avatar'] = null;
            }

            // Cria o novo produto
            $newProduct = Product::create($newProductData);

            // Duplica itens BOM se a opção estiver habilitada
            if ($options['bom_items'] && $product->bomItems->isNotEmpty()) {
                $this->duplicateBomItems($newProduct, $product);
            }

            // Duplica features se a opção estiver habilitada
            if ($options['features'] && $product->features->isNotEmpty()) {
                $this->duplicateFeatures($newProduct, $product);
            }

            // Duplica tags se a opção estiver habilitada (relação many-to-many)
            if ($options['tags'] && $product->tags->isNotEmpty()) {
                $newProduct->tags()->attach($product->tags->pluck('id'));
            }

            // Recalcula os custos para o novo produto
            $newProduct->refresh();
            app(ProductCostCalculator::class)->calculateManufacturingCost($newProduct);

            return $newProduct;
        });
    }

    /**
     * Duplica os itens BOM do produto original para o novo produto
     */
    public function duplicateBomItems(Product $newProduct, Product $original): void
    {
        foreach ($original->bomItems as $bomItem) {
            BomItem::create([
                'product_id' => $newProduct->id,
                'component_product_id' => $bomItem->component_product_id,
                'quantity' => $bomItem->quantity,
                'unit_of_measure' => $bomItem->unit_of_measure,
                'waste_factor' => $bomItem->waste_factor,
                'actual_quantity' => $bomItem->actual_quantity,
                'unit_cost' => $bomItem->unit_cost,
                'total_cost' => $bomItem->total_cost,
                'sort_order' => $bomItem->sort_order,
                'notes' => $bomItem->notes,
                'is_optional' => $bomItem->is_optional,
            ]);
        }
    }

    /**
     * Duplica as features do produto original para o novo produto
     */
    public function duplicateFeatures(Product $newProduct, Product $original): void
    {
        foreach ($original->features as $feature) {
            ProductFeature::create([
                'product_id' => $newProduct->id,
                'feature_name' => $feature->feature_name,
                'feature_value' => $feature->feature_value,
                'sort_order' => $feature->sort_order,
            ]);
        }
    }

    /**
     * Duplica o arquivo de avatar do produto
     * 
     * @return string|null O caminho do avatar duplicado, ou null se falhar
     */
    public function duplicateAvatar(Product $product): ?string
    {
        if (!$product->avatar || !Storage::disk('public')->exists($product->avatar)) {
            return null;
        }

        try {
            // Gera novo nome de arquivo
            $extension = pathinfo($product->avatar, PATHINFO_EXTENSION);
            $newFilename = 'products/avatars/' . uniqid() . '.' . $extension;

            // Copia o arquivo
            Storage::disk('public')->copy($product->avatar, $newFilename);

            return $newFilename;
        } catch (\Exception $e) {
            // Se a duplicação falhar, retorna null (produto será criado sem avatar)
            \Log::warning('Failed to duplicate product avatar', [
                'original_avatar' => $product->avatar,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
