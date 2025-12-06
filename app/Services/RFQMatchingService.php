<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Supplier;
use App\Models\Product;
use Illuminate\Support\Collection;

class RFQMatchingService
{
    /**
     * Encontra fornecedores para cada produto da Order baseado em tags
     * 
     * @param Order $order
     * @return array [
     *   'product_suppliers' => ['product_id' => [supplier_ids]],
     *   'supplier_products' => ['supplier_id' => [product_ids]]
     * ]
     */
    public function matchSuppliersToProducts(Order $order): array
    {
        $productSuppliers = [];  // product_id => [supplier_ids]
        $supplierProducts = [];  // supplier_id => [product_ids]
        
        foreach ($order->items as $item) {
            $product = $item->product;
            
            // Obter tags do produto
            $productTags = $product->tags()->pluck('tags.id')->toArray();
            
            if (empty($productTags)) {
                // Produto sem tags, não pode ser matched
                $productSuppliers[$product->id] = [];
                continue;
            }
            
            // Encontrar fornecedores que têm pelo menos 1 tag em comum com o produto
            $matchingSuppliers = Supplier::whereHas('tags', function($q) use ($productTags) {
                $q->whereIn('tags.id', $productTags);
            })
            ->where('status', 'active')  // Apenas fornecedores ativos
            ->get();
            
            // Armazenar matching
            $productSuppliers[$product->id] = $matchingSuppliers->pluck('id')->toArray();
            
            foreach ($matchingSuppliers as $supplier) {
                if (!isset($supplierProducts[$supplier->id])) {
                    $supplierProducts[$supplier->id] = [];
                }
                $supplierProducts[$supplier->id][] = $product->id;
            }
        }
        
        return [
            'product_suppliers' => $productSuppliers,  // Quais fornecedores para cada produto
            'supplier_products' => $supplierProducts,  // Quais produtos para cada fornecedor
        ];
    }
    
    /**
     * Obter lista de fornecedores que podem cotar PELO MENOS 1 produto
     * 
     * @param Order $order
     * @return Collection
     */
    public function getMatchingSuppliers(Order $order): Collection
    {
        $matching = $this->matchSuppliersToProducts($order);
        $supplierIds = array_keys($matching['supplier_products']);
        
        if (empty($supplierIds)) {
            return collect([]);
        }
        
        return Supplier::whereIn('id', $supplierIds)
            ->with('tags')
            ->get()
            ->map(function($supplier) use ($matching) {
                // Adicionar informações de matching ao supplier
                $supplier->matched_product_ids = $matching['supplier_products'][$supplier->id] ?? [];
                $supplier->matched_product_count = count($supplier->matched_product_ids);
                return $supplier;
            });
    }
    
    /**
     * Obter produtos que um fornecedor específico pode cotar
     * 
     * @param Order $order
     * @param Supplier $supplier
     * @return Collection
     */
    public function getProductsForSupplier(Order $order, Supplier $supplier): Collection
    {
        $matching = $this->matchSuppliersToProducts($order);
        $productIds = $matching['supplier_products'][$supplier->id] ?? [];
        
        if (empty($productIds)) {
            return collect([]);
        }
        
        return Product::whereIn('id', $productIds)->get();
    }
    
    /**
     * Obter order items que um fornecedor específico pode cotar
     * 
     * @param Order $order
     * @param Supplier $supplier
     * @return Collection
     */
    public function getOrderItemsForSupplier(Order $order, Supplier $supplier): Collection
    {
        $matching = $this->matchSuppliersToProducts($order);
        $productIds = $matching['supplier_products'][$supplier->id] ?? [];
        
        if (empty($productIds)) {
            return collect([]);
        }
        
        return $order->items()->whereIn('product_id', $productIds)->get();
    }
}
