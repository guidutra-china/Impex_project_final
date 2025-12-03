<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * RepositoryInterface
 * 
 * Define o contrato que todos os repositories devem implementar.
 * Garante uma interface consistente para acesso a dados.
 */
interface RepositoryInterface
{
    /**
     * Encontra um modelo por ID
     * 
     * @param int|string $id
     * @return Model|null
     */
    public function findById(int|string $id): ?Model;

    /**
     * Retorna todos os modelos
     * 
     * @return Collection
     */
    public function all(): Collection;

    /**
     * Cria um novo modelo
     * 
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model;

    /**
     * Atualiza um modelo existente
     * 
     * @param int|string $id
     * @param array $data
     * @return bool
     */
    public function update(int|string $id, array $data): bool;

    /**
     * Deleta um modelo
     * 
     * @param int|string $id
     * @return bool
     */
    public function delete(int|string $id): bool;

    /**
     * Retorna o modelo Eloquent sendo usado
     * 
     * @return Model
     */
    public function getModel(): Model;

    /**
     * Define o modelo Eloquent a ser usado
     * 
     * @param Model $model
     * @return self
     */
    public function setModel(Model $model): self;
}
