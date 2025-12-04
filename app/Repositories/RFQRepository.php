<?php

namespace App\Repositories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;

/**
 * Repository para gerenciar RFQs (Requisições de Cotação)
 * 
 * Centraliza a lógica de acesso a dados de RFQs,
 * incluindo cálculos e filtros por status.
 */
class RFQRepository extends BaseRepository
{
    /**
     * Retorna a classe do modelo
     */
    protected function resolveModel(): \Illuminate\Database\Eloquent\Model
    {
        return new Order();
    }

    /**
     * Busca RFQs por status
     * 
     * @param string $status Status do RFQ
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findByStatus(string $status, array $relations = []): Collection
    {
        return $this->model
            ->where('status', $status)
            ->with($relations)
            ->get();
    }

    /**
     * Busca RFQs por cliente
     * 
     * @param int $clientId ID do cliente
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findByClient(int $clientId, array $relations = []): Collection
    {
        return $this->model
            ->where('client_id', $clientId)
            ->with($relations)
            ->get();
    }

    /**
     * Busca RFQs abertos (não finalizados)
     * 
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findOpen(array $relations = []): Collection
    {
        return $this->model
            ->whereIn('status', ['draft', 'sent', 'pending_quotes'])
            ->with($relations)
            ->get();
    }

    /**
     * Busca RFQs em progresso
     * 
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findInProgress(array $relations = []): Collection
    {
        return $this->model
            ->whereIn('status', ['pending_quotes', 'quotes_received', 'under_analysis'])
            ->with($relations)
            ->get();
    }

    /**
     * Busca RFQs finalizados
     * 
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findClosed(array $relations = []): Collection
    {
        return $this->model
            ->whereIn('status', ['approved', 'cancelled', 'completed'])
            ->with($relations)
            ->get();
    }

    /**
     * Busca RFQs por período
     * 
     * @param \DateTime $startDate Data inicial
     * @param \DateTime $endDate Data final
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findByDateRange(\DateTime $startDate, \DateTime $endDate, array $relations = []): Collection
    {
        return $this->model
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with($relations)
            ->get();
    }

    /**
     * Busca RFQs recentes
     * 
     * @param int $limit Quantidade de registros
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function getRecent(int $limit = 10, array $relations = []): Collection
    {
        return $this->model
            ->with($relations)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Calcula o total de RFQs por status
     * 
     * @param string $status Status
     * @return int Valor total em centavos
     */
    public function getTotalByStatus(string $status): int
    {
        return (int)$this->model
            ->where('status', $status)
            ->sum('total_value');
    }

    /**
     * Calcula o total de RFQs abertos
     * 
     * @return int Valor total em centavos
     */
    public function getTotalOpen(): int
    {
        return (int)$this->model
            ->whereIn('status', ['draft', 'sent', 'pending_quotes'])
            ->sum('total_value');
    }

    /**
     * Calcula o total de RFQs em progresso
     * 
     * @return int Valor total em centavos
     */
    public function getTotalInProgress(): int
    {
        return (int)$this->model
            ->whereIn('status', ['pending_quotes', 'quotes_received', 'under_analysis'])
            ->sum('total_value');
    }

    /**
     * Conta RFQs por status
     * 
     * @param string $status Status
     * @return int
     */
    public function countByStatus(string $status): int
    {
        return $this->model
            ->where('status', $status)
            ->count();
    }

    /**
     * Conta RFQs abertos
     * 
     * @return int
     */
    public function countOpen(): int
    {
        return $this->model
            ->whereIn('status', ['draft', 'sent', 'pending_quotes'])
            ->count();
    }

    /**
     * Conta RFQs em progresso
     * 
     * @return int
     */
    public function countInProgress(): int
    {
        return $this->model
            ->whereIn('status', ['pending_quotes', 'quotes_received', 'under_analysis'])
            ->count();
    }

    /**
     * Conta RFQs finalizados
     * 
     * @return int
     */
    public function countClosed(): int
    {
        return $this->model
            ->whereIn('status', ['approved', 'cancelled', 'completed'])
            ->count();
    }

    /**
     * Busca RFQs com paginação
     * 
     * @param int $perPage Itens por página
     * @param array $filters Filtros a aplicar
     * @param array $relations Relações a carregar
     * @return Paginator
     */
    public function paginate(int $perPage = 15, array $filters = [], array $relations = []): Paginator
    {
        $query = $this->model->with($relations);

        // Aplicar filtros
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('rfq_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Aprova um RFQ
     * 
     * @param int $id ID do RFQ
     * @param int $approvedBy ID do usuário que aprova
     * @return Order
     * @throws \Exception
     */
    public function approve(int $id, int $approvedBy): Order
    {
        try {
            return $this->update($id, [
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $approvedBy,
            ]);
        } catch (\Exception $e) {
            \Log::error('Erro ao aprovar RFQ', [
                'id' => $id,
                'approved_by' => $approvedBy,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Cancela um RFQ
     * 
     * @param int $id ID do RFQ
     * @param string $reason Motivo do cancelamento
     * @return Order
     * @throws \Exception
     */
    public function cancel(int $id, string $reason): Order
    {
        try {
            return $this->update($id, [
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);
        } catch (\Exception $e) {
            \Log::error('Erro ao cancelar RFQ', [
                'id' => $id,
                'reason' => $reason,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
