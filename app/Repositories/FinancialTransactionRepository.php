<?php

namespace App\Repositories;

use App\Models\FinancialTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;

/**
 * Repository para gerenciar Transações Financeiras
 * 
 * Centraliza a lógica de acesso a dados de transações financeiras,
 * garantindo consistência e reutilização em toda a aplicação.
 */
class FinancialTransactionRepository extends BaseRepository
{
    /**
     * Retorna a classe do modelo
     */
    public function getModel(): string
    {
        return FinancialTransaction::class;
    }

    /**
     * Busca transações por projeto
     * 
     * @param int $projectId ID do projeto
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findByProject(int $projectId, array $relations = []): Collection
    {
        return $this->model
            ->where('project_id', $projectId)
            ->with($relations)
            ->get();
    }

    /**
     * Busca transações por categoria financeira
     * 
     * @param int $categoryId ID da categoria
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findByCategory(int $categoryId, array $relations = []): Collection
    {
        return $this->model
            ->where('financial_category_id', $categoryId)
            ->with($relations)
            ->get();
    }

    /**
     * Busca transações por status
     * 
     * @param string $status Status da transação (pending, paid, overdue, etc)
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
     * Busca transações por tipo
     * 
     * @param string $type Tipo da transação (payable, receivable)
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findByType(string $type, array $relations = []): Collection
    {
        return $this->model
            ->where('type', $type)
            ->with($relations)
            ->get();
    }

    /**
     * Busca transações vencidas
     * 
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findOverdue(array $relations = []): Collection
    {
        return $this->model
            ->where('due_date', '<', now())
            ->where('status', '!=', 'paid')
            ->with($relations)
            ->get();
    }

    /**
     * Busca transações por período
     * 
     * @param \DateTime $startDate Data inicial
     * @param \DateTime $endDate Data final
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findByDateRange(\DateTime $startDate, \DateTime $endDate, array $relations = []): Collection
    {
        return $this->model
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->with($relations)
            ->get();
    }

    /**
     * Busca transações por entidade (transactable)
     * 
     * @param string $type Tipo da entidade (classe)
     * @param int $id ID da entidade
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findByTransactable(string $type, int $id, array $relations = []): Collection
    {
        return $this->model
            ->where('transactable_type', $type)
            ->where('transactable_id', $id)
            ->with($relations)
            ->get();
    }

    /**
     * Cria uma nova transação financeira
     * 
     * @param array $data Dados da transação
     * @return FinancialTransaction
     * @throws \Exception
     */
    public function create(array $data): FinancialTransaction
    {
        try {
            // Validar dados obrigatórios
            $this->validateRequiredFields($data, [
                'type',
                'status',
                'amount',
                'currency_id',
                'transaction_date',
            ]);

            // Converter valores em centavos se necessário
            if (isset($data['amount']) && !is_int($data['amount'])) {
                $data['amount'] = (int)($data['amount'] * 100);
            }

            if (isset($data['paid_amount']) && !is_int($data['paid_amount'])) {
                $data['paid_amount'] = (int)($data['paid_amount'] * 100);
            }

            // Registrar criador se não fornecido
            if (!isset($data['created_by'])) {
                $data['created_by'] = auth()->id();
            }

            return $this->model->create($data);
        } catch (\Exception $e) {
            \Log::error('Erro ao criar transação financeira', [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Atualiza uma transação financeira
     * 
     * @param int $id ID da transação
     * @param array $data Dados a atualizar
     * @return FinancialTransaction
     * @throws \Exception
     */
    public function update(int $id, array $data): FinancialTransaction
    {
        try {
            $transaction = $this->findById($id);

            if (!$transaction) {
                throw new \Exception("Transação financeira com ID {$id} não encontrada");
            }

            // Converter valores em centavos se necessário
            if (isset($data['amount']) && !is_int($data['amount'])) {
                $data['amount'] = (int)($data['amount'] * 100);
            }

            if (isset($data['paid_amount']) && !is_int($data['paid_amount'])) {
                $data['paid_amount'] = (int)($data['paid_amount'] * 100);
            }

            $transaction->update($data);

            return $transaction->fresh();
        } catch (\Exception $e) {
            \Log::error('Erro ao atualizar transação financeira', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Marca uma transação como paga
     * 
     * @param int $id ID da transação
     * @param int $paidAmount Valor pago (em centavos)
     * @param array $additionalData Dados adicionais
     * @return FinancialTransaction
     */
    public function markAsPaid(int $id, int $paidAmount, array $additionalData = []): FinancialTransaction
    {
        return $this->update($id, array_merge([
            'status' => 'paid',
            'paid_amount' => $paidAmount,
            'paid_at' => now(),
        ], $additionalData));
    }

    /**
     * Marca uma transação como pendente
     * 
     * @param int $id ID da transação
     * @return FinancialTransaction
     */
    public function markAsPending(int $id): FinancialTransaction
    {
        return $this->update($id, [
            'status' => 'pending',
        ]);
    }

    /**
     * Marca uma transação como cancelada
     * 
     * @param int $id ID da transação
     * @param string $reason Motivo do cancelamento
     * @return FinancialTransaction
     */
    public function markAsCancelled(int $id, string $reason = ''): FinancialTransaction
    {
        return $this->update($id, [
            'status' => 'cancelled',
            'notes' => $reason,
        ]);
    }

    /**
     * Calcula o total de transações por status
     * 
     * @param string $status Status
     * @param string $type Tipo (opcional)
     * @return int Valor total em centavos
     */
    public function getTotalByStatus(string $status, string $type = null): int
    {
        $query = $this->model->where('status', $status);

        if ($type) {
            $query->where('type', $type);
        }

        return (int)$query->sum('amount');
    }

    /**
     * Calcula o total de transações pendentes
     * 
     * @return int Valor total em centavos
     */
    public function getTotalPending(): int
    {
        return $this->getTotalByStatus('pending');
    }

    /**
     * Calcula o total de transações pagas
     * 
     * @return int Valor total em centavos
     */
    public function getTotalPaid(): int
    {
        return $this->getTotalByStatus('paid');
    }

    /**
     * Calcula o total de transações vencidas
     * 
     * @return int Valor total em centavos
     */
    public function getTotalOverdue(): int
    {
        return $this->model
            ->where('due_date', '<', now())
            ->where('status', '!=', 'paid')
            ->sum('amount');
    }

    /**
     * Busca transações com paginação
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

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        if (isset($filters['category_id'])) {
            $query->where('financial_category_id', $filters['category_id']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * Valida campos obrigatórios
     * 
     * @param array $data Dados a validar
     * @param array $requiredFields Campos obrigatórios
     * @throws \Exception
     */
    protected function validateRequiredFields(array $data, array $requiredFields): void
    {
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || $data[$field] === null || $data[$field] === '') {
                throw new \Exception("Campo obrigatório ausente: {$field}");
            }
        }
    }
}
