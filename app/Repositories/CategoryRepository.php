<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;

/**
 * Repository para gerenciar Categorias
 * 
 * Centraliza a lógica de acesso a dados de categorias,
 * incluindo features e produtos relacionados.
 */
class CategoryRepository extends BaseRepository
{
    /**
     * Retorna a classe do modelo
     */
    protected function resolveModel(): \Illuminate\Database\Eloquent\Model
    {
        return new Category();
    }

    /**
     * Busca categorias ativas
     * 
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findActive(array $relations = []): Collection
    {
        return $this->model
            ->where('is_active', true)
            ->with($relations)
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * Busca categorias por nome
     * 
     * @param string $name Nome da categoria
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findByName(string $name, array $relations = []): Collection
    {
        return $this->model
            ->where('name', 'like', "%{$name}%")
            ->with($relations)
            ->get();
    }

    /**
     * Busca categorias raiz (sem parent)
     * 
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findRoot(array $relations = []): Collection
    {
        return $this->model
            ->whereNull('parent_id')
            ->with($relations)
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * Busca subcategorias de uma categoria
     * 
     * @param int $parentId ID da categoria pai
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findByParent(int $parentId, array $relations = []): Collection
    {
        return $this->model
            ->where('parent_id', $parentId)
            ->with($relations)
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * Obtém query builder para features de uma categoria
     * 
     * @param int $categoryId ID da categoria
     * @return Builder
     */
    public function getFeaturesQuery(int $categoryId): Builder
    {
        return $this->model
            ->find($categoryId)
            ->features()
            ->getQuery();
    }

    /**
     * Obtém query builder para produtos de uma categoria
     * 
     * @param int $categoryId ID da categoria
     * @return Builder
     */
    public function getProductsQuery(int $categoryId): Builder
    {
        return $this->model
            ->find($categoryId)
            ->products()
            ->getQuery()
            ->with(['features']);
    }

    /**
     * Busca categorias com paginação
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
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['parent_id'])) {
            $query->where('parent_id', $filters['parent_id']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * Busca categorias recentes
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
     * Obtém opções de seleção para categorias
     * 
     * @return array
     */
    public function getSelectOptions(): array
    {
        return $this->model
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Conta categorias ativas
     * 
     * @return int
     */
    public function countActive(): int
    {
        return $this->model
            ->where('is_active', true)
            ->count();
    }

    /**
     * Conta subcategorias de uma categoria
     * 
     * @param int $parentId ID da categoria pai
     * @return int
     */
    public function countByParent(int $parentId): int
    {
        return $this->model
            ->where('parent_id', $parentId)
            ->count();
    }

    /**
     * Ativa uma categoria
     * 
     * @param int $id ID da categoria
     * @return Category
     * @throws \Exception
     */
    public function activate(int $id): Category
    {
        try {
            return $this->update($id, ['is_active' => true]);
        } catch (\Exception $e) {
            \Log::error('Erro ao ativar categoria', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Desativa uma categoria
     * 
     * @param int $id ID da categoria
     * @return Category
     * @throws \Exception
     */
    public function deactivate(int $id): Category
    {
        try {
            return $this->update($id, ['is_active' => false]);
        } catch (\Exception $e) {
            \Log::error('Erro ao desativar categoria', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
