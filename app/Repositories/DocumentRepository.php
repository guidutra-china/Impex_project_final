<?php

namespace App\Repositories;

use App\Models\Document;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;

/**
 * Repository para gerenciar Documentos
 * 
 * Centraliza a lógica de acesso a dados de documentos,
 * incluindo documentos relacionados a diferentes entidades.
 */
class DocumentRepository extends BaseRepository
{
    /**
     * Retorna a classe do modelo
     */
    protected function resolveModel(): \Illuminate\Database\Eloquent\Model
    {
        return new Document();
    }

    /**
     * Busca documentos por tipo
     * 
     * @param string $type Tipo do documento
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findByType(string $type, array $relations = []): Collection
    {
        return $this->model
            ->where('document_type', $type)
            ->with($relations)
            ->get();
    }

    /**
     * Busca documentos por entidade (transactable)
     * 
     * @param string $type Tipo da entidade (classe)
     * @param int $id ID da entidade
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findByTransactable(string $type, int $id, array $relations = []): Collection
    {
        return $this->model
            ->where('documentable_type', $type)
            ->where('documentable_id', $id)
            ->with($relations)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Busca documentos de um projeto
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
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Busca documentos por status
     * 
     * @param string $status Status do documento
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
     * Busca documentos por período
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
     * Busca documentos recentes
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
     * Obtém query builder para documentos de uma entidade
     * 
     * @param string $type Tipo da entidade
     * @param int $id ID da entidade
     * @return Builder
     */
    public function getTransactableDocumentsQuery(string $type, int $id): Builder
    {
        return $this->model
            ->where('documentable_type', $type)
            ->where('documentable_id', $id)
            ->with(['uploader'])
            ->orderBy('created_at', 'desc');
    }

    /**
     * Busca documentos com paginação
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
        if (isset($filters['type'])) {
            $query->where('document_type', $filters['type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
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
     * Conta documentos por tipo
     * 
     * @param string $type Tipo do documento
     * @return int
     */
    public function countByType(string $type): int
    {
        return $this->model
            ->where('document_type', $type)
            ->count();
    }

    /**
     * Conta documentos por entidade
     * 
     * @param string $type Tipo da entidade
     * @param int $id ID da entidade
     * @return int
     */
    public function countByTransactable(string $type, int $id): int
    {
        return $this->model
            ->where('documentable_type', $type)
            ->where('documentable_id', $id)
            ->count();
    }

    /**
     * Marca um documento como verificado
     * 
     * @param int $id ID do documento
     * @param int $verifiedBy ID do usuário que verifica
     * @return Document
     * @throws \Exception
     */
    public function markAsVerified(int $id, int $verifiedBy): Document
    {
        try {
            return $this->update($id, [
                'status' => 'verified',
                'verified_at' => now(),
                'verified_by' => $verifiedBy,
            ]);
        } catch (\Exception $e) {
            \Log::error('Erro ao marcar documento como verificado', [
                'id' => $id,
                'verified_by' => $verifiedBy,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Marca um documento como rejeitado
     * 
     * @param int $id ID do documento
     * @param string $reason Motivo da rejeição
     * @return Document
     * @throws \Exception
     */
    public function markAsRejected(int $id, string $reason): Document
    {
        try {
            return $this->update($id, [
                'status' => 'rejected',
                'rejection_reason' => $reason,
                'rejected_at' => now(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Erro ao rejeitar documento', [
                'id' => $id,
                'reason' => $reason,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
