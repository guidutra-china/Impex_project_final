<?php

namespace App\Repositories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;

/**
 * Repository para gerenciar Eventos
 * 
 * Centraliza a lógica de acesso a dados de eventos,
 * incluindo eventos do calendário e notificações.
 */
class EventRepository extends BaseRepository
{
    /**
     * Retorna a classe do modelo
     */
    public function getModel(): string
    {
        return Event::class;
    }

    /**
     * Busca eventos por tipo
     * 
     * @param string $type Tipo do evento
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findByType(string $type, array $relations = []): Collection
    {
        return $this->model
            ->where('event_type', $type)
            ->with($relations)
            ->get();
    }

    /**
     * Busca eventos por período
     * 
     * @param \DateTime $startDate Data inicial
     * @param \DateTime $endDate Data final
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findByDateRange(\DateTime $startDate, \DateTime $endDate, array $relations = []): Collection
    {
        return $this->model
            ->whereBetween('event_date', [$startDate, $endDate])
            ->with($relations)
            ->orderBy('event_date', 'asc')
            ->get();
    }

    /**
     * Busca eventos de hoje
     * 
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findToday(array $relations = []): Collection
    {
        return $this->model
            ->whereDate('event_date', now())
            ->with($relations)
            ->orderBy('event_date', 'asc')
            ->get();
    }

    /**
     * Busca eventos de uma semana
     * 
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findThisWeek(array $relations = []): Collection
    {
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        return $this->model
            ->whereBetween('event_date', [$startOfWeek, $endOfWeek])
            ->with($relations)
            ->orderBy('event_date', 'asc')
            ->get();
    }

    /**
     * Busca eventos de um mês
     * 
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findThisMonth(array $relations = []): Collection
    {
        return $this->model
            ->whereYear('event_date', now()->year)
            ->whereMonth('event_date', now()->month)
            ->with($relations)
            ->orderBy('event_date', 'asc')
            ->get();
    }

    /**
     * Busca eventos próximos
     * 
     * @param int $days Número de dias
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findUpcoming(int $days = 30, array $relations = []): Collection
    {
        return $this->model
            ->whereBetween('event_date', [now(), now()->addDays($days)])
            ->with($relations)
            ->orderBy('event_date', 'asc')
            ->get();
    }

    /**
     * Busca eventos passados
     * 
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findPast(array $relations = []): Collection
    {
        return $this->model
            ->where('event_date', '<', now())
            ->with($relations)
            ->orderBy('event_date', 'desc')
            ->get();
    }

    /**
     * Busca eventos por entidade
     * 
     * @param string $type Tipo da entidade
     * @param int $id ID da entidade
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findByEntity(string $type, int $id, array $relations = []): Collection
    {
        return $this->model
            ->where('eventable_type', $type)
            ->where('eventable_id', $id)
            ->with($relations)
            ->orderBy('event_date', 'asc')
            ->get();
    }

    /**
     * Busca eventos recentes
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
     * Obtém query builder para eventos de uma entidade
     * 
     * @param string $type Tipo da entidade
     * @param int $id ID da entidade
     * @return Builder
     */
    public function getEntityEventsQuery(string $type, int $id): Builder
    {
        return $this->model
            ->where('eventable_type', $type)
            ->where('eventable_id', $id)
            ->with(['creator'])
            ->orderBy('event_date', 'asc');
    }

    /**
     * Busca eventos com paginação
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
            $query->where('event_type', $filters['type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (isset($filters['date_from'])) {
            $query->where('event_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('event_date', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Conta eventos por tipo
     * 
     * @param string $type Tipo do evento
     * @return int
     */
    public function countByType(string $type): int
    {
        return $this->model
            ->where('event_type', $type)
            ->count();
    }

    /**
     * Conta eventos de hoje
     * 
     * @return int
     */
    public function countToday(): int
    {
        return $this->model
            ->whereDate('event_date', now())
            ->count();
    }

    /**
     * Conta eventos próximos
     * 
     * @param int $days Número de dias
     * @return int
     */
    public function countUpcoming(int $days = 30): int
    {
        return $this->model
            ->whereBetween('event_date', [now(), now()->addDays($days)])
            ->count();
    }

    /**
     * Marca um evento como completo
     * 
     * @param int $id ID do evento
     * @return Event
     * @throws \Exception
     */
    public function markAsComplete(int $id): Event
    {
        try {
            return $this->update($id, [
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Erro ao marcar evento como completo', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Marca um evento como cancelado
     * 
     * @param int $id ID do evento
     * @param string $reason Motivo do cancelamento
     * @return Event
     * @throws \Exception
     */
    public function markAsCancelled(int $id, string $reason = ''): Event
    {
        try {
            return $this->update($id, [
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);
        } catch (\Exception $e) {
            \Log::error('Erro ao cancelar evento', [
                'id' => $id,
                'reason' => $reason,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
