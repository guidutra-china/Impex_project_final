<?php

namespace App\Repositories;

use App\Models\SalesInvoice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;

/**
 * Repository para gerenciar Faturas de Venda
 * 
 * Centraliza a lógica de acesso a dados de faturas de venda,
 * incluindo cálculos financeiros e filtros por status.
 */
class SalesInvoiceRepository extends BaseRepository
{
    /**
     * Retorna a classe do modelo
     */
    protected function resolveModel(): \Illuminate\Database\Eloquent\Model
    {
        return new SalesInvoice();
    }

    /**
     * Busca faturas por status
     * 
     * @param string $status Status da fatura
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
     * Busca faturas por cliente
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
     * Busca faturas pendentes de pagamento
     * 
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findPending(array $relations = []): Collection
    {
        return $this->model
            ->whereIn('status', ['draft', 'sent', 'overdue'])
            ->with($relations)
            ->get();
    }

    /**
     * Busca faturas vencidas
     * 
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findOverdue(array $relations = []): Collection
    {
        return $this->model
            ->where('status', 'overdue')
            ->with($relations)
            ->get();
    }

    /**
     * Busca faturas vencidas em próximos N dias
     * 
     * @param int $days Número de dias
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findDueSoon(int $days = 30, array $relations = []): Collection
    {
        return $this->model
            ->whereIn('status', ['sent'])
            ->whereBetween('due_date', [now(), now()->addDays($days)])
            ->with($relations)
            ->get();
    }

    /**
     * Busca faturas pagas
     * 
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findPaid(array $relations = []): Collection
    {
        return $this->model
            ->where('status', 'paid')
            ->with($relations)
            ->get();
    }

    /**
     * Busca faturas por período
     * 
     * @param \DateTime $startDate Data inicial
     * @param \DateTime $endDate Data final
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findByDateRange(\DateTime $startDate, \DateTime $endDate, array $relations = []): Collection
    {
        return $this->model
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->with($relations)
            ->get();
    }

    /**
     * Busca faturas do mês atual
     * 
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findThisMonth(array $relations = []): Collection
    {
        return $this->model
            ->whereYear('invoice_date', now()->year)
            ->whereMonth('invoice_date', now()->month)
            ->with($relations)
            ->get();
    }

    /**
     * Busca faturas do mês anterior
     * 
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findLastMonth(array $relations = []): Collection
    {
        $lastMonth = now()->subMonth();
        
        return $this->model
            ->whereYear('invoice_date', $lastMonth->year)
            ->whereMonth('invoice_date', $lastMonth->month)
            ->with($relations)
            ->get();
    }

    /**
     * Calcula o total de faturas por status
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
     * Calcula o total de faturas pendentes
     * 
     * @return int Valor total em centavos
     */
    public function getTotalPending(): int
    {
        return (int)$this->model
            ->whereIn('status', ['draft', 'sent', 'overdue'])
            ->sum('total_base_currency');
    }

    /**
     * Calcula o total de faturas pagas
     * 
     * @return int Valor total em centavos
     */
    public function getTotalPaid(): int
    {
        return $this->getTotalByStatus('paid');
    }

    /**
     * Calcula o total de faturas vencidas
     * 
     * @return int Valor total em centavos
     */
    public function getTotalOverdue(): int
    {
        return (int)$this->model
            ->where('status', 'overdue')
            ->sum('total_base_currency');
    }

    /**
     * Calcula o total de vendas do mês atual
     * 
     * @return int Valor total em centavos
     */
    public function getThisMonthTotal(): int
    {
        return (int)$this->model
            ->whereYear('invoice_date', now()->year)
            ->whereMonth('invoice_date', now()->month)
            ->sum('total_base_currency');
    }

    /**
     * Calcula o total de vendas do mês anterior
     * 
     * @return int Valor total em centavos
     */
    public function getLastMonthTotal(): int
    {
        $lastMonth = now()->subMonth();
        
        return (int)$this->model
            ->whereYear('invoice_date', $lastMonth->year)
            ->whereMonth('invoice_date', $lastMonth->month)
            ->sum('total_base_currency');
    }

    /**
     * Conta faturas por status
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
     * Conta faturas pendentes
     * 
     * @return int
     */
    public function countPending(): int
    {
        return $this->model
            ->whereIn('status', ['draft', 'sent', 'overdue'])
            ->count();
    }

    /**
     * Conta faturas vencidas
     * 
     * @return int
     */
    public function countOverdue(): int
    {
        return $this->model
            ->where('status', 'overdue')
            ->count();
    }

    /**
     * Conta faturas vencidas em próximos N dias
     * 
     * @param int $days Número de dias
     * @return int
     */
    public function countDueSoon(int $days = 30): int
    {
        return $this->model
            ->whereIn('status', ['sent'])
            ->whereBetween('due_date', [now(), now()->addDays($days)])
            ->count();
    }

    /**
     * Obtém query builder para faturas de um projeto
     * 
     * @param int $projectId ID do projeto
     * @return Builder
     */
    public function getProjectInvoicesQuery(int $projectId): Builder
    {
        return $this->model
            ->where('project_id', $projectId)
            ->with(['client', 'items', 'items.product']);
    }

    /**
     * Busca faturas com paginação
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

        if (isset($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if (isset($filters['date_from'])) {
            $query->where('invoice_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('invoice_date', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Busca faturas recentes
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
     * Aprova uma fatura
     * 
     * @param int $id ID da fatura
     * @param int $approvedBy ID do usuário que aprova
     * @return SalesInvoice
     * @throws \Exception
     */
    public function approve(int $id, int $approvedBy): SalesInvoice
    {
        try {
            return $this->update($id, [
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $approvedBy,
            ]);
        } catch (\Exception $e) {
            \Log::error('Erro ao aprovar fatura de venda', [
                'id' => $id,
                'approved_by' => $approvedBy,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Marca uma fatura como enviada
     * 
     * @param int $id ID da fatura
     * @return SalesInvoice
     * @throws \Exception
     */
    public function markAsSent(int $id): SalesInvoice
    {
        try {
            return $this->update($id, [
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Erro ao marcar fatura como enviada', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Marca uma fatura como paga
     * 
     * @param int $id ID da fatura
     * @param array $data Dados do pagamento
     * @return SalesInvoice
     * @throws \Exception
     */
    public function markAsPaid(int $id, array $data = []): SalesInvoice
    {
        try {
            $updateData = [
                'status' => 'paid',
                'paid_at' => now(),
            ];

            if (isset($data['payment_method'])) {
                $updateData['payment_method'] = $data['payment_method'];
            }

            if (isset($data['payment_reference'])) {
                $updateData['payment_reference'] = $data['payment_reference'];
            }

            return $this->update($id, $updateData);
        } catch (\Exception $e) {
            \Log::error('Erro ao marcar fatura como paga', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Calcula a tendência de vendas
     * 
     * @return float Percentual de mudança
     */
    public function calculateSalesTrend(): float
    {
        $thisMonth = $this->getThisMonthTotal();
        $lastMonth = $this->getLastMonthTotal();

        if ($lastMonth <= 0) {
            return 0;
        }

        return round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1);
    }
}
