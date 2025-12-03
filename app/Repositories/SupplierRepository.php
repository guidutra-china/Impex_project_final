<?php

namespace App\Repositories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * SupplierRepository
 * 
 * Repository para acesso a dados de Suppliers.
 * Encapsula toda a lógica de consulta relacionada a fornecedores.
 */
class SupplierRepository extends BaseRepository
{
    /**
     * Resolve e retorna uma nova instância do modelo Supplier
     */
    protected function resolveModel(): Model
    {
        return new Supplier();
    }

    /**
     * Encontra um fornecedor por ID com todas as relações
     */
    public function findByIdWithRelations(int|string $id): ?Supplier
    {
        return $this->query()
            ->with([
                'user',
                'products',
                'supplierQuotes',
                'supplierQuotes.order',
            ])
            ->find($id);
    }

    /**
     * Obtém fornecedores ativos
     */
    public function getActiveSuppliers(): Collection
    {
        return $this->query()
            ->where('status', 'active')
            ->with('user')
            ->orderBy('name')
            ->get();
    }

    /**
     * Obtém fornecedores por país
     */
    public function getSuppliersByCountry(string $country): Collection
    {
        return $this->query()
            ->where('country', $country)
            ->with('user')
            ->orderBy('name')
            ->get();
    }

    /**
     * Obtém fornecedores por região/estado
     */
    public function getSuppliersByRegion(string $region): Collection
    {
        return $this->query()
            ->where('region', $region)
            ->with('user')
            ->orderBy('name')
            ->get();
    }

    /**
     * Busca fornecedores por nome, email ou código
     */
    public function searchSuppliers(string $query): Collection
    {
        return $this->query()
            ->where('name', 'like', "%{$query}%")
            ->orWhere('code', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->with('user')
            ->orderBy('name')
            ->get();
    }

    /**
     * Obtém fornecedores com produtos
     */
    public function getSuppliersWithProducts(): Collection
    {
        return $this->query()
            ->whereHas('products')
            ->with('products', 'user')
            ->orderBy('name')
            ->get();
    }

    /**
     * Obtém fornecedores sem produtos
     */
    public function getSuppliersWithoutProducts(): Collection
    {
        return $this->query()
            ->doesntHave('products')
            ->with('user')
            ->orderBy('name')
            ->get();
    }

    /**
     * Obtém fornecedores de um produto específico
     */
    public function getSuppliersByProduct(int|string $productId): Collection
    {
        return $this->query()
            ->whereHas('products', function ($q) use ($productId) {
                $q->where('product_id', $productId);
            })
            ->with('user')
            ->orderBy('name')
            ->get();
    }

    /**
     * Obtém fornecedores recentes (últimos N dias)
     */
    public function getRecentSuppliers(int $days = 30): Collection
    {
        return $this->query()
            ->where('created_at', '>=', now()->subDays($days))
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Obtém fornecedores com maior número de produtos
     */
    public function getTopSuppliersByProductCount(int $limit = 10): Collection
    {
        return $this->query()
            ->withCount('products')
            ->orderBy('products_count', 'desc')
            ->limit($limit)
            ->with('user')
            ->get();
    }

    /**
     * Obtém fornecedores com maior número de cotações
     */
    public function getTopSuppliersByQuoteCount(int $limit = 10): Collection
    {
        return $this->query()
            ->withCount('supplierQuotes')
            ->orderBy('supplier_quotes_count', 'desc')
            ->limit($limit)
            ->with('user')
            ->get();
    }

    /**
     * Obtém fornecedores por tipo de produto
     */
    public function getSuppliersByProductCategory(int|string $categoryId): Collection
    {
        return $this->query()
            ->whereHas('products', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            })
            ->with('user')
            ->orderBy('name')
            ->get();
    }

    /**
     * Obtém fornecedores por moeda padrão
     */
    public function getSuppliersByCurrency(int|string $currencyId): Collection
    {
        return $this->query()
            ->where('currency_id', $currencyId)
            ->with('user')
            ->orderBy('name')
            ->get();
    }

    /**
     * Obtém fornecedores com lead time curto
     */
    public function getSuppliersWithShortLeadTime(int $days = 30): Collection
    {
        return $this->query()
            ->whereHas('products', function ($q) use ($days) {
                $q->where('lead_time_days', '<=', $days);
            })
            ->with('user')
            ->orderBy('name')
            ->get();
    }

    /**
     * Conta fornecedores por país
     */
    public function countByCountry(string $country): int
    {
        return $this->query()
            ->where('country', $country)
            ->count();
    }

    /**
     * Conta fornecedores por status
     */
    public function countByStatus(string $status): int
    {
        return $this->query()
            ->where('status', $status)
            ->count();
    }

    /**
     * Obtém número de produtos por fornecedor
     */
    public function getProductCountBySupplier(int|string $supplierId): int
    {
        return $this->query()
            ->find($supplierId)
            ->products()
            ->count();
    }

    /**
     * Obtém número de cotações por fornecedor
     */
    public function getQuoteCountBySupplier(int|string $supplierId): int
    {
        return $this->query()
            ->find($supplierId)
            ->supplierQuotes()
            ->count();
    }

    /**
     * Obtém estatísticas de fornecedores
     */
    public function getStatistics(): array
    {
        return [
            'total_suppliers' => $this->count(),
            'active_suppliers' => $this->query()->where('status', 'active')->count(),
            'suppliers_with_products' => $this->query()->whereHas('products')->count(),
            'suppliers_with_quotes' => $this->query()->whereHas('supplierQuotes')->count(),
        ];
    }
}
