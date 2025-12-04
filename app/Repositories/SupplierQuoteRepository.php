<?php

namespace App\Repositories;

use App\Models\SupplierQuote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;

/**
 * Repository para gerenciar Cotações de Fornecedores
 * 
 * Centraliza a lógica de acesso a dados de cotações,
 * incluindo cálculos e operações de importação.
 */
class SupplierQuoteRepository extends BaseRepository
{
    /**
     * Retorna a classe do modelo
     */
    protected function resolveModel(): \Illuminate\Database\Eloquent\Model
    {
        return new SupplierQuote();
    }

    /**
     * Busca cotações por ordem
     * 
     * @param int $orderId ID da ordem
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findByOrder(int $orderId, array $relations = []): Collection
    {
        return $this->model
            ->where('order_id', $orderId)
            ->with($relations)
            ->get();
    }

    /**
     * Busca cotações por fornecedor
     * 
     * @param int $supplierId ID do fornecedor
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findBySupplier(int $supplierId, array $relations = []): Collection
    {
        return $this->model
            ->where('supplier_id', $supplierId)
            ->with($relations)
            ->get();
    }

    /**
     * Busca cotações por status
     * 
     * @param string $status Status da cotação
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
     * Busca cotações de uma ordem com relações
     * 
     * @param int $orderId ID da ordem
     * @return Collection
     */
    public function getOrderQuotesWithRelations(int $orderId): Collection
    {
        return $this->model
            ->where('order_id', $orderId)
            ->with(['supplier', 'items', 'items.product'])
            ->get();
    }

    /**
     * Busca a cotação mais barata para uma ordem
     * 
     * @param int $orderId ID da ordem
     * @return SupplierQuote|null
     */
    public function findCheapestForOrder(int $orderId): ?SupplierQuote
    {
        return $this->model
            ->where('order_id', $orderId)
            ->orderBy('total_amount', 'asc')
            ->first();
    }

    /**
     * Busca a cotação mais cara para uma ordem
     * 
     * @param int $orderId ID da ordem
     * @return SupplierQuote|null
     */
    public function findMostExpensiveForOrder(int $orderId): ?SupplierQuote
    {
        return $this->model
            ->where('order_id', $orderId)
            ->orderBy('total_amount', 'desc')
            ->first();
    }

    /**
     * Recalcula todos os valores de uma cotação
     * 
     * @param int $id ID da cotação
     * @return SupplierQuote
     * @throws \Exception
     */
    public function recalculateAll(int $id): SupplierQuote
    {
        try {
            $quote = $this->findById($id);

            if (!$quote) {
                throw new \Exception("Cotação com ID {$id} não encontrada");
            }

            // Recalcular todos os valores
            $quote->calculateTotalAmount();
            $quote->calculateCommission();
            $quote->lockExchangeRate();

            $quote->save();

            return $quote->fresh();
        } catch (\Exception $e) {
            \Log::error('Erro ao recalcular cotação', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Bloqueia a taxa de câmbio de uma cotação
     * 
     * @param int $id ID da cotação
     * @return SupplierQuote
     * @throws \Exception
     */
    public function lockExchangeRate(int $id): SupplierQuote
    {
        try {
            $quote = $this->findById($id);

            if (!$quote) {
                throw new \Exception("Cotação com ID {$id} não encontrada");
            }

            $quote->lockExchangeRate();
            $quote->save();

            return $quote->fresh();
        } catch (\Exception $e) {
            \Log::error('Erro ao bloquear taxa de câmbio', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Calcula a comissão de uma cotação
     * 
     * @param int $id ID da cotação
     * @return SupplierQuote
     * @throws \Exception
     */
    public function calculateCommission(int $id): SupplierQuote
    {
        try {
            $quote = $this->findById($id);

            if (!$quote) {
                throw new \Exception("Cotação com ID {$id} não encontrada");
            }

            $quote->calculateCommission();
            $quote->save();

            return $quote->fresh();
        } catch (\Exception $e) {
            \Log::error('Erro ao calcular comissão', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Aprova uma cotação
     * 
     * @param int $id ID da cotação
     * @param int $approvedBy ID do usuário que aprova
     * @return SupplierQuote
     * @throws \Exception
     */
    public function approve(int $id, int $approvedBy): SupplierQuote
    {
        try {
            return $this->update($id, [
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $approvedBy,
            ]);
        } catch (\Exception $e) {
            \Log::error('Erro ao aprovar cotação', [
                'id' => $id,
                'approved_by' => $approvedBy,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Rejeita uma cotação
     * 
     * @param int $id ID da cotação
     * @param string $reason Motivo da rejeição
     * @return SupplierQuote
     * @throws \Exception
     */
    public function reject(int $id, string $reason): SupplierQuote
    {
        try {
            return $this->update($id, [
                'status' => 'rejected',
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);
        } catch (\Exception $e) {
            \Log::error('Erro ao rejeitar cotação', [
                'id' => $id,
                'reason' => $reason,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Busca cotações com paginação
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
        if (isset($filters['order_id'])) {
            $query->where('order_id', $filters['order_id']);
        }

        if (isset($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
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
     * Busca cotações recentes
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
     * Calcula o total de cotações por status
     * 
     * @param string $status Status
     * @return int Valor total em centavos
     */
    public function getTotalByStatus(string $status): int
    {
        return (int)$this->model
            ->where('status', $status)
            ->sum('total_amount');
    }

    /**
     * Calcula o total de cotações pendentes
     * 
     * @return int Valor total em centavos
     */
    public function getTotalPending(): int
    {
        return $this->getTotalByStatus('pending');
    }

    /**
     * Calcula o total de cotações aprovadas
     * 
     * @return int Valor total em centavos
     */
    public function getTotalApproved(): int
    {
        return $this->getTotalByStatus('approved');
    }

    /**
     * Busca cotações por período
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
     * Busca cotações com itens
     * 
     * @param int $orderId ID da ordem
     * @return Collection
     */
    public function getWithItems(int $orderId): Collection
    {
        return $this->model
            ->where('order_id', $orderId)
            ->with(['items', 'items.product'])
            ->get();
    }

    /**
     * Conta cotações por status
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
     * Conta cotações por ordem
     * 
     * @param int $orderId ID da ordem
     * @return int
     */
    public function countByOrder(int $orderId): int
    {
        return $this->model
            ->where('order_id', $orderId)
            ->count();
    }
}
