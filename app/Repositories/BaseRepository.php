<?php

namespace App\Repositories;

use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * BaseRepository
 * 
 * Classe base abstrata que implementa métodos comuns de acesso a dados.
 * Todos os repositories específicos devem herdar desta classe.
 */
abstract class BaseRepository implements RepositoryInterface
{
    /**
     * O modelo Eloquent sendo usado
     */
    protected Model $model;

    /**
     * Construtor
     */
    public function __construct()
    {
        $this->model = $this->resolveModel();
    }

    /**
     * Resolve e retorna uma nova instância do modelo
     * 
     * @return Model
     */
    abstract protected function resolveModel(): Model;

    /**
     * Encontra um modelo por ID
     */
    public function findById(int|string $id): ?Model
    {
        return $this->model->find($id);
    }

    /**
     * Encontra um modelo por ID ou lança exceção
     */
    public function findByIdOrFail(int|string $id): Model
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Retorna todos os modelos
     */
    public function all(): Collection
    {
        return $this->model->all();
    }

    /**
     * Retorna todos os modelos com paginação
     */
    public function paginate(int $perPage = 15)
    {
        return $this->model->paginate($perPage);
    }

    /**
     * Cria um novo modelo
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Atualiza um modelo existente
     */
    public function update(int|string $id, array $data): bool
    {
        $model = $this->findById($id);

        if (!$model) {
            return false;
        }

        return $model->update($data);
    }

    /**
     * Deleta um modelo
     */
    public function delete(int|string $id): bool
    {
        $model = $this->findById($id);

        if (!$model) {
            return false;
        }

        return $model->delete();
    }

    /**
     * Retorna o modelo Eloquent sendo usado
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * Define o modelo Eloquent a ser usado
     */
    public function setModel(Model $model): self
    {
        $this->model = $model;
        return $this;
    }

    /**
     * Retorna um novo query builder
     */
    protected function query(): Builder
    {
        return $this->model->query();
    }

    /**
     * Conta modelos
     */
    public function count(): int
    {
        return $this->model->count();
    }

    /**
     * Verifica se existe um modelo com os critérios
     */
    public function exists(array $criteria): bool
    {
        return $this->model->where($criteria)->exists();
    }

    /**
     * Encontra o primeiro modelo que corresponde aos critérios
     */
    public function findWhere(array $criteria): ?Model
    {
        return $this->model->where($criteria)->first();
    }

    /**
     * Encontra todos os modelos que correspondem aos critérios
     */
    public function findAllWhere(array $criteria): Collection
    {
        return $this->model->where($criteria)->get();
    }

    /**
     * Encontra o primeiro modelo ou cria um novo
     */
    public function firstOrCreate(array $criteria, array $values = []): Model
    {
        return $this->model->firstOrCreate($criteria, $values);
    }

    /**
     * Atualiza ou cria um modelo
     */
    public function updateOrCreate(array $criteria, array $values = []): Model
    {
        return $this->model->updateOrCreate($criteria, $values);
    }

    /**
     * Deleta modelos que correspondem aos critérios
     */
    public function deleteWhere(array $criteria): int
    {
        return $this->model->where($criteria)->delete();
    }

    /**
     * Restaura modelos soft-deleted que correspondem aos critérios
     */
    public function restoreWhere(array $criteria): int
    {
        return $this->model->onlyTrashed()->where($criteria)->restore();
    }

    /**
     * Força a exclusão de modelos soft-deleted
     */
    public function forceDeleteWhere(array $criteria): int
    {
        return $this->model->onlyTrashed()->where($criteria)->forceDelete();
    }
}
