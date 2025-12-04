<?php

namespace App\Repositories;

use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;

/**
 * Repository para gerenciar Ordens de Compra
 * 
 * Centraliza a lógica de acesso a dados de ordens de compra,
 * incluindo cálculos financeiros e filtros por status.
 */
class PurchaseOrderRepository extends BaseRepository
{
    /**
     * Retorna a classe do modelo
     */
    protected function resolveModel(): \Illuminate\Database\Eloquent\Model
    {
        return new PurchaseOrder();
    }

    /**
     * Busca ordens de compra por status
     * 
     * @param string $status Status da ordem
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
     * Busca ordens de compra por fornecedor
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
     * Busca ordens de compra ativas (pendentes de pagamento)
     * 
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findActive(array $relations = []): Collection
    {
        return $this->model
            ->whereIn('status', ['approved', 'sent', 'confirmed', 'in_production', 'partially_received'])
            ->with($relations)
            ->get();
    }

    /**
     * Busca ordens de compra por período
     * 
     * @param \DateTime $startDate Data inicial
     * @param \DateTime $endDate Data final
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findByDateRange(\DateTime $startDate, \DateTime $endDate, array $relations = []): Collection
    {
        return $this->model
            ->whereBetween('order_date', [$startDate, $endDate])
            ->with($relations)
            ->get();
    }

    /**
     * Busca ordens de compra do mês atual
     * 
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findThisMonth(array $relations = []): Collection
    {
        return $this->model
            ->whereYear('order_date', now()->year)
            ->whereMonth('order_date', now()->month)
            ->with($relations)
            ->get();
    }

    /**
     * Busca ordens de compra com vencimento próximo
     * 
     * @param int $days Número de dias
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findDueSoon(int $days = 30, array $relations = []): Collection
    {
        return $this->model
            ->whereIn('status', ['confirmed', 'in_production', 'partially_received'])
            ->whereBetween('expected_delivery_date', [now(), now()->addDays($days)])
            ->with($relations)
            ->get();
    }

    /**
     * Calcula o total de ordens de compra por status
     * 
     * @param string $status Status
     * @return int Valor total em centavos
     */
    public function getTotalByStatus(string $status): int
    {
        return (int)$this->model
            ->where('status', $status)
            ->sum('total_base_currency');
    }

    /**
     * Calcula o total de ordens de compra ativas
     * 
     * @return int Valor total em centavos
     */
    public function getTotalActive(): int
    {
        return (int)$this->model
            ->whereIn('status', ['approved', 'sent', 'confirmed', 'in_production', 'partially_received'])
            ->sum('total_base_currency');
    }

    /**
     * Calcula o total de ordens de compra pendentes de recebimento
     * 
     * @return int Valor total em centavos
     */
    public function getTotalPending(): int
    {
        return (int)$this->model
            ->whereIn('status', ['approved', 'sent', 'confirmed', 'in_production', 'partially_received'])
            ->sum('total_base_currency');
    }

    /**
     * Calcula o total de ordens de compra recebidas
     * 
     * @return int Valor total em centavos
     */
    public function getTotalReceived(): int
    {
        return (int)$this->model
            ->where('status', 'received')
            ->sum('total_base_currency');
    }

    /**
     * Conta ordens de compra por status
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
     * Conta ordens de compra ativas
     * 
     * @return int
     */
    public function countActive(): int
    {
        return $this->model
            ->whereIn('status', ['approved', 'sent', 'confirmed', 'in_production', 'partially_received'])
            ->count();
    }

    /**
     * Conta ordens de compra pendentes
     * 
     * @return int
     */
    public function countPending(): int
    {
        return $this->model
            ->whereIn('status', ['approved', 'sent', 'confirmed', 'in_production', 'partially_received'])
            ->count();
    }

    /**
     * Obtém query builder para ordens de compra de um projeto
     * 
     * @param int $projectId ID do projeto
     * @return Builder
     */
    public function getProjectOrdersQuery(int $projectId): Builder
    {
        return $this->model
            ->where('project_id', $projectId)
            ->with(['supplier', 'items', 'items.product']);
    }

    /**
     * Busca ordens de compra com paginação
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

        if (isset($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (isset($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if (isset($filters['date_from'])) {
            $query->where('order_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('order_date', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Busca ordens de compra recentes
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
     * Aprova uma ordem de compra
     * 
     * @param int $id ID da ordem
     * @param int $approvedBy ID do usuário que aprova
     * @return PurchaseOrder
     * @throws \Exception
     */
    public function approve(int $id, int $approvedBy): PurchaseOrder
    {
        try {
            return $this->update($id, [
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $approvedBy,
            ]);
        } catch (\Exception $e) {
            \Log::error('Erro ao aprovar ordem de compra', [
                'id' => $id,
                'approved_by' => $approvedBy,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Marca uma ordem de compra como enviada
     * 
     * @param int $id ID da ordem
     * @return PurchaseOrder
     * @throws \Exception
     */
    public function markAsSent(int $id): PurchaseOrder
    {
        try {
            return $this->update($id, [
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Erro ao marcar ordem de compra como enviada', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Marca uma ordem de compra como confirmada
     * 
     * @param int $id ID da ordem
     * @return PurchaseOrder
     * @throws \Exception
     */
    public function markAsConfirmed(int $id): PurchaseOrder
    {
        try {
            return $this->update($id, [
                'status' => 'confirmed',
                'confirmed_at' => now(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Erro ao marcar ordem de compra como confirmada', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Marca uma ordem de compra como recebida
     * 
     * @param int $id ID da ordem
     * @param array $data Dados do recebimento
     * @return PurchaseOrder
     * @throws \Exception
     */
    public function markAsReceived(int $id, array $data = []): PurchaseOrder
    {
        try {
            $updateData = [
                'status' => 'received',
                'received_at' => now(),
            ];

            if (isset($data['received_quantity'])) {
                $updateData['received_quantity'] = $data['received_quantity'];
            }

            if (isset($data['received_date'])) {
                $updateData['received_date'] = $data['received_date'];
            }

            return $this->update($id, $updateData);
        } catch (\Exception $e) {
            \Log::error('Erro ao marcar ordem de compra como recebida', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Calcula o total de compras do mês
     * 
     * @return int Valor total em centavos
     */
    public function getThisMonthTotal(): int
    {
        return (int)$this->model
            ->whereYear('order_date', now()->year)
            ->whereMonth('order_date', now()->month)
            ->sum('total_base_currency');
    }

    /**
     * Busca ordens de compra por RFQ
     * 
     * @param int $rfqId ID do RFQ
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findByRFQ(int $rfqId, array $relations = []): Collection
    {
        return $this->model
            ->where('rfq_id', $rfqId)
            ->with($relations)
            ->get();
    }
}
