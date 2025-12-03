<?php

namespace App\Repositories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * OrderRepository
 * 
 * Repository para acesso a dados de Orders.
 * Encapsula toda a lógica de consulta relacionada a pedidos.
 */
class OrderRepository extends BaseRepository
{
    /**
     * Resolve e retorna uma nova instância do modelo Order
     */
    protected function resolveModel(): Model
    {
        return new Order();
    }

    /**
     * Encontra uma ordem por ID com todas as relações
     */
    public function findByIdWithRelations(int|string $id): ?Order
    {
        return $this->query()
            ->with([
                'customer',
                'currency',
                'items',
                'items.product',
                'supplierQuotes',
                'supplierQuotes.supplier',
            ])
            ->find($id);
    }

    /**
     * Obtém ordens pendentes de um cliente
     */
    public function getPendingOrdersByCustomer(int|string $customerId): Collection
    {
        return $this->query()
            ->where('customer_id', $customerId)
            ->where('status', 'pending')
            ->with('items', 'customer')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Obtém ordens completadas
     */
    public function getCompletedOrders(int $limit = 10): Collection
    {
        return $this->query()
            ->where('status', 'completed')
            ->with('customer', 'items')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtém ordens por status
     */
    public function getOrdersByStatus(string $status): Collection
    {
        return $this->query()
            ->where('status', $status)
            ->with('customer', 'items')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Obtém ordens com itens de um cliente
     */
    public function getOrdersWithItems(int|string $customerId): Collection
    {
        return $this->query()
            ->where('customer_id', $customerId)
            ->with('items.product', 'customer')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Obtém ordens recentes (últimos N dias)
     */
    public function getRecentOrders(int $days = 30): Collection
    {
        return $this->query()
            ->where('created_at', '>=', now()->subDays($days))
            ->with('customer', 'items')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Busca ordens por número, cliente ou status
     */
    public function searchOrders(string $query): Collection
    {
        return $this->query()
            ->where('order_number', 'like', "%{$query}%")
            ->orWhereHas('customer', function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%");
            })
            ->with('customer', 'items')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Obtém ordens de um cliente com filtro de status
     */
    public function getOrdersByCustomerAndStatus(int|string $customerId, string $status): Collection
    {
        return $this->query()
            ->where('customer_id', $customerId)
            ->where('status', $status)
            ->with('items', 'customer')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Obtém ordens com valor total acima de um limite
     */
    public function getOrdersAboveAmount(int $amount): Collection
    {
        return $this->query()
            ->where('total_amount', '>=', $amount)
            ->with('customer', 'items')
            ->orderBy('total_amount', 'desc')
            ->get();
    }

    /**
     * Obtém ordens com cotações de fornecedores
     */
    public function getOrdersWithSupplierQuotes(): Collection
    {
        return $this->query()
            ->whereHas('supplierQuotes')
            ->with('supplierQuotes.supplier', 'customer', 'items')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Obtém ordens enviadas para fornecedores
     */
    public function getSentToSupplierOrders(): Collection
    {
        return $this->query()
            ->where('sent_to_supplier', true)
            ->with('customer', 'items')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Conta ordens por status
     */
    public function countByStatus(string $status): int
    {
        return $this->query()
            ->where('status', $status)
            ->count();
    }

    /**
     * Conta ordens de um cliente
     */
    public function countByCustomer(int|string $customerId): int
    {
        return $this->query()
            ->where('customer_id', $customerId)
            ->count();
    }

    /**
     * Obtém valor total de ordens por status
     */
    public function getTotalAmountByStatus(string $status): int
    {
        return $this->query()
            ->where('status', $status)
            ->sum('total_amount');
    }

    /**
     * Obtém valor total de ordens de um cliente
     */
    public function getTotalAmountByCustomer(int|string $customerId): int
    {
        return $this->query()
            ->where('customer_id', $customerId)
            ->sum('total_amount');
    }

    /**
     * Obtém estatísticas de ordens
     */
    public function getStatistics(): array
    {
        return [
            'total_orders' => $this->count(),
            'pending_orders' => $this->countByStatus('pending'),
            'completed_orders' => $this->countByStatus('completed'),
            'total_amount' => $this->query()->sum('total_amount'),
            'average_amount' => $this->query()->avg('total_amount'),
        ];
    }
}
