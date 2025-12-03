<?php

namespace App\Repositories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * ClientRepository
 * 
 * Repository para acesso a dados de Clients.
 * Encapsula toda a lógica de consulta relacionada a clientes.
 */
class ClientRepository extends BaseRepository
{
    /**
     * Resolve e retorna uma nova instância do modelo Client
     */
    protected function resolveModel(): Model
    {
        return new Client();
    }

    /**
     * Encontra um cliente por ID com todas as relações
     */
    public function findByIdWithRelations(int|string $id): ?Client
    {
        return $this->query()
            ->with([
                'user',
                'orders',
                'orders.items',
                'products',
            ])
            ->find($id);
    }

    /**
     * Obtém clientes ativos
     */
    public function getActiveClients(): Collection
    {
        return $this->query()
            ->where('status', 'active')
            ->with('user')
            ->orderBy('name')
            ->get();
    }

    /**
     * Obtém clientes por país
     */
    public function getClientsByCountry(string $country): Collection
    {
        return $this->query()
            ->where('country', $country)
            ->with('user')
            ->orderBy('name')
            ->get();
    }

    /**
     * Obtém clientes por região/estado
     */
    public function getClientsByRegion(string $region): Collection
    {
        return $this->query()
            ->where('region', $region)
            ->with('user')
            ->orderBy('name')
            ->get();
    }

    /**
     * Busca clientes por nome, email ou código
     */
    public function searchClients(string $query): Collection
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
     * Obtém clientes com pedidos
     */
    public function getClientsWithOrders(): Collection
    {
        return $this->query()
            ->whereHas('orders')
            ->with('orders', 'user')
            ->orderBy('name')
            ->get();
    }

    /**
     * Obtém clientes sem pedidos
     */
    public function getClientsWithoutOrders(): Collection
    {
        return $this->query()
            ->doesntHave('orders')
            ->with('user')
            ->orderBy('name')
            ->get();
    }

    /**
     * Obtém clientes recentes (últimos N dias)
     */
    public function getRecentClients(int $days = 30): Collection
    {
        return $this->query()
            ->where('created_at', '>=', now()->subDays($days))
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Obtém clientes com maior volume de pedidos
     */
    public function getTopClientsByOrderCount(int $limit = 10): Collection
    {
        return $this->query()
            ->withCount('orders')
            ->orderBy('orders_count', 'desc')
            ->limit($limit)
            ->with('user')
            ->get();
    }

    /**
     * Obtém clientes com maior valor de pedidos
     */
    public function getTopClientsByOrderValue(int $limit = 10): Collection
    {
        return $this->query()
            ->with('orders')
            ->get()
            ->map(function ($client) {
                $client->total_order_value = $client->orders->sum('total_amount');
                return $client;
            })
            ->sortByDesc('total_order_value')
            ->take($limit);
    }

    /**
     * Obtém clientes por tipo de negócio
     */
    public function getClientsByBusinessType(string $businessType): Collection
    {
        return $this->query()
            ->where('business_type', $businessType)
            ->with('user')
            ->orderBy('name')
            ->get();
    }

    /**
     * Obtém clientes por moeda padrão
     */
    public function getClientsByCurrency(int|string $currencyId): Collection
    {
        return $this->query()
            ->where('currency_id', $currencyId)
            ->with('user')
            ->orderBy('name')
            ->get();
    }

    /**
     * Conta clientes por país
     */
    public function countByCountry(string $country): int
    {
        return $this->query()
            ->where('country', $country)
            ->count();
    }

    /**
     * Conta clientes por status
     */
    public function countByStatus(string $status): int
    {
        return $this->query()
            ->where('status', $status)
            ->count();
    }

    /**
     * Obtém valor total de pedidos por cliente
     */
    public function getTotalOrderValueByClient(int|string $clientId): int
    {
        return $this->query()
            ->find($clientId)
            ->orders()
            ->sum('total_amount');
    }

    /**
     * Obtém número de pedidos por cliente
     */
    public function getOrderCountByClient(int|string $clientId): int
    {
        return $this->query()
            ->find($clientId)
            ->orders()
            ->count();
    }

    /**
     * Obtém estatísticas de clientes
     */
    public function getStatistics(): array
    {
        return [
            'total_clients' => $this->count(),
            'active_clients' => $this->query()->where('status', 'active')->count(),
            'clients_with_orders' => $this->query()->whereHas('orders')->count(),
            'total_order_value' => $this->query()->with('orders')->get()->sum(function ($client) {
                return $client->orders->sum('total_amount');
            }),
        ];
    }
}
