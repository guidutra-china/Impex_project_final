<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * ProductRepository
 * 
 * Repository para acesso a dados de Products.
 * Encapsula toda a lógica de consulta relacionada a produtos.
 */
class ProductRepository extends BaseRepository
{
    /**
     * Resolve e retorna uma nova instância do modelo Product
     */
    protected function resolveModel(): Model
    {
        return new Product();
    }

    /**
     * Encontra um produto por ID com todas as relações
     */
    public function findByIdWithRelations(int|string $id): ?Product
    {
        return $this->query()
            ->with([
                'category',
                'supplier',
                'client',
                'currency',
                'bomItems.componentProduct',
                'features',
                'tags',
                'files',
            ])
            ->find($id);
    }

    /**
     * Encontra um produto por SKU
     */
    public function findBySku(string $sku): ?Product
    {
        return $this->query()
            ->where('sku', $sku)
            ->first();
    }

    /**
     * Obtém produtos ativos
     */
    public function getActiveProducts(): Collection
    {
        return $this->query()
            ->where('status', 'active')
            ->with('category', 'supplier')
            ->orderBy('name')
            ->get();
    }

    /**
     * Obtém produtos por categoria
     */
    public function getProductsByCategory(int|string $categoryId): Collection
    {
        return $this->query()
            ->where('category_id', $categoryId)
            ->with('supplier', 'category')
            ->orderBy('name')
            ->get();
    }

    /**
     * Obtém produtos com BOM
     */
    public function getProductsWithBom(): Collection
    {
        return $this->query()
            ->whereHas('bomItems')
            ->with('bomItems.componentProduct', 'category')
            ->orderBy('name')
            ->get();
    }

    /**
     * Obtém produtos por fornecedor
     */
    public function getProductsBySupplier(int|string $supplierId): Collection
    {
        return $this->query()
            ->where('supplier_id', $supplierId)
            ->with('supplier', 'category')
            ->orderBy('name')
            ->get();
    }

    /**
     * Obtém produtos por cliente
     */
    public function getProductsByClient(int|string $clientId): Collection
    {
        return $this->query()
            ->where('client_id', $clientId)
            ->with('client', 'category')
            ->orderBy('name')
            ->get();
    }

    /**
     * Busca produtos por nome, SKU ou descrição
     */
    public function searchProducts(string $query): Collection
    {
        return $this->query()
            ->where('name', 'like', "%{$query}%")
            ->orWhere('sku', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->with('category', 'supplier')
            ->orderBy('name')
            ->get();
    }

    /**
     * Obtém produtos com preço acima de um valor
     */
    public function getProductsAbovePrice(int $price): Collection
    {
        return $this->query()
            ->where('price', '>=', $price)
            ->with('category', 'supplier')
            ->orderBy('price', 'desc')
            ->get();
    }

    /**
     * Obtém produtos com preço abaixo de um valor
     */
    public function getProductsBelowPrice(int $price): Collection
    {
        return $this->query()
            ->where('price', '<=', $price)
            ->with('category', 'supplier')
            ->orderBy('price', 'asc')
            ->get();
    }

    /**
     * Obtém produtos com MOQ (Minimum Order Quantity)
     */
    public function getProductsWithMoq(): Collection
    {
        return $this->query()
            ->whereNotNull('moq')
            ->with('supplier', 'category')
            ->orderBy('moq')
            ->get();
    }

    /**
     * Obtém produtos com lead time
     */
    public function getProductsWithLeadTime(): Collection
    {
        return $this->query()
            ->whereNotNull('lead_time_days')
            ->with('supplier', 'category')
            ->orderBy('lead_time_days')
            ->get();
    }

    /**
     * Obtém produtos com tags específicas
     */
    public function getProductsByTag(string $tagName): Collection
    {
        return $this->query()
            ->whereHas('tags', function ($q) use ($tagName) {
                $q->where('name', $tagName);
            })
            ->with('tags', 'category')
            ->orderBy('name')
            ->get();
    }

    /**
     * Obtém produtos recentes (últimos N dias)
     */
    public function getRecentProducts(int $days = 30): Collection
    {
        return $this->query()
            ->where('created_at', '>=', now()->subDays($days))
            ->with('category', 'supplier')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Obtém produtos com custo de manufatura calculado
     */
    public function getProductsWithManufacturingCost(): Collection
    {
        return $this->query()
            ->whereNotNull('total_manufacturing_cost')
            ->where('total_manufacturing_cost', '>', 0)
            ->with('category', 'supplier')
            ->orderBy('total_manufacturing_cost', 'desc')
            ->get();
    }

    /**
     * Obtém produtos com dimensões (para cálculo de CBM)
     */
    public function getProductsWithDimensions(): Collection
    {
        return $this->query()
            ->whereNotNull('product_length')
            ->whereNotNull('product_width')
            ->whereNotNull('product_height')
            ->with('category')
            ->orderBy('name')
            ->get();
    }

    /**
     * Conta produtos por categoria
     */
    public function countByCategory(int|string $categoryId): int
    {
        return $this->query()
            ->where('category_id', $categoryId)
            ->count();
    }

    /**
     * Conta produtos por fornecedor
     */
    public function countBySupplier(int|string $supplierId): int
    {
        return $this->query()
            ->where('supplier_id', $supplierId)
            ->count();
    }

    /**
     * Obtém valor total de produtos em estoque (por preço)
     */
    public function getTotalInventoryValue(): int
    {
        return $this->query()->sum('price');
    }

    /**
     * Obtém preço médio de produtos
     */
    public function getAveragePrice(): float
    {
        return (float) $this->query()->avg('price');
    }

    /**
     * Obtém estatísticas de produtos
     */
    public function getStatistics(): array
    {
        return [
            'total_products' => $this->count(),
            'active_products' => $this->query()->where('status', 'active')->count(),
            'products_with_bom' => $this->query()->whereHas('bomItems')->count(),
            'average_price' => $this->getAveragePrice(),
            'total_inventory_value' => $this->getTotalInventoryValue(),
        ];
    }
}
