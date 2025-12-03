<?php

namespace App\Repositories;

use App\Models\ProformaInvoice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;

/**
 * Repository para gerenciar Proforma Invoices
 * 
 * Centraliza a lógica de acesso a dados de proforma invoices,
 * incluindo transições de estado e operações relacionadas.
 */
class ProformaInvoiceRepository extends BaseRepository
{
    /**
     * Retorna a classe do modelo
     */
    public function getModel(): string
    {
        return ProformaInvoice::class;
    }

    /**
     * Busca proforma invoices por status
     * 
     * @param string $status Status da proforma
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
     * Busca proforma invoices por cliente
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
     * Busca proforma invoices por order
     * 
     * @param int $orderId ID do order
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
     * Busca proforma invoices em rascunho
     * 
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findDrafts(array $relations = []): Collection
    {
        return $this->findByStatus('draft', $relations);
    }

    /**
     * Busca proforma invoices aprovadas
     * 
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findApproved(array $relations = []): Collection
    {
        return $this->findByStatus('approved', $relations);
    }

    /**
     * Busca proforma invoices enviadas
     * 
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findSent(array $relations = []): Collection
    {
        return $this->findByStatus('sent', $relations);
    }

    /**
     * Busca proforma invoices com depósito recebido
     * 
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findWithDepositReceived(array $relations = []): Collection
    {
        return $this->model
            ->where('deposit_received', true)
            ->with($relations)
            ->get();
    }

    /**
     * Aprova uma proforma invoice
     * 
     * @param int $id ID da proforma
     * @param int $approvedBy ID do usuário que aprova
     * @return ProformaInvoice
     * @throws \Exception
     */
    public function approve(int $id, int $approvedBy): ProformaInvoice
    {
        try {
            $proforma = $this->findById($id);

            if (!$proforma) {
                throw new \Exception("Proforma Invoice com ID {$id} não encontrada");
            }

            if (!$proforma->canApprove()) {
                throw new \Exception("Esta Proforma Invoice não pode ser aprovada no status atual");
            }

            return $this->update($id, [
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $approvedBy,
            ]);
        } catch (\Exception $e) {
            \Log::error('Erro ao aprovar Proforma Invoice', [
                'id' => $id,
                'approved_by' => $approvedBy,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Rejeita uma proforma invoice
     * 
     * @param int $id ID da proforma
     * @param string $reason Motivo da rejeição
     * @return ProformaInvoice
     * @throws \Exception
     */
    public function reject(int $id, string $reason): ProformaInvoice
    {
        try {
            $proforma = $this->findById($id);

            if (!$proforma) {
                throw new \Exception("Proforma Invoice com ID {$id} não encontrada");
            }

            if (!$proforma->canReject()) {
                throw new \Exception("Esta Proforma Invoice não pode ser rejeitada no status atual");
            }

            return $this->update($id, [
                'status' => 'rejected',
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);
        } catch (\Exception $e) {
            \Log::error('Erro ao rejeitar Proforma Invoice', [
                'id' => $id,
                'reason' => $reason,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Marca uma proforma invoice como enviada
     * 
     * @param int $id ID da proforma
     * @return ProformaInvoice
     * @throws \Exception
     */
    public function markAsSent(int $id): ProformaInvoice
    {
        try {
            $proforma = $this->findById($id);

            if (!$proforma) {
                throw new \Exception("Proforma Invoice com ID {$id} não encontrada");
            }

            if ($proforma->status !== 'draft') {
                throw new \Exception("Apenas Proforma Invoices em rascunho podem ser marcadas como enviadas");
            }

            return $this->update($id, [
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Erro ao marcar Proforma Invoice como enviada', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Marca depósito como recebido
     * 
     * @param int $id ID da proforma
     * @param array $data Dados do depósito (payment_method, payment_reference)
     * @return ProformaInvoice
     * @throws \Exception
     */
    public function markDepositReceived(int $id, array $data): ProformaInvoice
    {
        try {
            $proforma = $this->findById($id);

            if (!$proforma) {
                throw new \Exception("Proforma Invoice com ID {$id} não encontrada");
            }

            if (!$proforma->deposit_required) {
                throw new \Exception("Esta Proforma Invoice não requer depósito");
            }

            if ($proforma->deposit_received) {
                throw new \Exception("O depósito já foi marcado como recebido");
            }

            $updateData = [
                'deposit_received' => true,
                'deposit_received_at' => now(),
            ];

            if (isset($data['deposit_payment_method'])) {
                $updateData['deposit_payment_method'] = $data['deposit_payment_method'];
            }

            if (isset($data['deposit_payment_reference'])) {
                $updateData['deposit_payment_reference'] = $data['deposit_payment_reference'];
            }

            return $this->update($id, $updateData);
        } catch (\Exception $e) {
            \Log::error('Erro ao marcar depósito como recebido', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Atualiza o status de uma proforma invoice
     * 
     * @param int $id ID da proforma
     * @param string $status Novo status
     * @param array $additionalData Dados adicionais
     * @return ProformaInvoice
     */
    public function updateStatus(int $id, string $status, array $additionalData = []): ProformaInvoice
    {
        $data = array_merge(['status' => $status], $additionalData);
        return $this->update($id, $data);
    }

    /**
     * Busca proforma invoices com paginação
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

        if (isset($filters['order_id'])) {
            $query->where('order_id', $filters['order_id']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
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
     * Calcula o total de proforma invoices por status
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
     * Calcula o total de proforma invoices pendentes de aprovação
     * 
     * @return int Valor total em centavos
     */
    public function getTotalPendingApproval(): int
    {
        return $this->getTotalByStatus('draft');
    }

    /**
     * Calcula o total de proforma invoices aprovadas
     * 
     * @return int Valor total em centavos
     */
    public function getTotalApproved(): int
    {
        return $this->getTotalByStatus('approved');
    }

    /**
     * Busca proforma invoices recentes
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
     * Busca proforma invoices por período
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
}
