<?php

namespace App\Repositories;

use App\Models\Shipment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;

/**
 * Repository para gerenciar Envios (Shipments)
 * 
 * Centraliza a lógica de acesso a dados de envios,
 * incluindo itens, invoices e packing boxes.
 */
class ShipmentRepository extends BaseRepository
{
    /**
     * Retorna a classe do modelo
     */
    protected function resolveModel(): \Illuminate\Database\Eloquent\Model
    {
        return new Shipment();
    }

    /**
     * Busca envios por status
     * 
     * @param string $status Status do envio
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
     * Busca envios por ordem
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
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Busca envios em trânsito
     * 
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findInTransit(array $relations = []): Collection
    {
        return $this->model
            ->whereIn('status', ['shipped', 'in_transit', 'out_for_delivery'])
            ->with($relations)
            ->get();
    }

    /**
     * Busca envios entregues
     * 
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findDelivered(array $relations = []): Collection
    {
        return $this->model
            ->where('status', 'delivered')
            ->with($relations)
            ->get();
    }

    /**
     * Busca envios por período
     * 
     * @param \DateTime $startDate Data inicial
     * @param \DateTime $endDate Data final
     * @param array $relations Relações a carregar
     * @return Collection
     */
    public function findByDateRange(\DateTime $startDate, \DateTime $endDate, array $relations = []): Collection
    {
        return $this->model
            ->whereBetween('shipped_at', [$startDate, $endDate])
            ->with($relations)
            ->get();
    }

    /**
     * Obtém query builder para itens de um envio
     * 
     * @param int $shipmentId ID do envio
     * @return Builder
     */
    public function getItemsQuery(int $shipmentId): Builder
    {
        return $this->model
            ->find($shipmentId)
            ->items()
            ->query()
            ->with(['product', 'orderItem']);
    }

    /**
     * Obtém query builder para invoices de um envio
     * 
     * @param int $shipmentId ID do envio
     * @return Builder
     */
    public function getInvoicesQuery(int $shipmentId): Builder
    {
        return $this->model
            ->find($shipmentId)
            ->invoices()
            ->getQuery()
            ->with(['client']);
    }

    /**
     * Obtém query builder para packing boxes de um envio
     * 
     * @param int $shipmentId ID do envio
     * @return Builder
     */
    public function getPackingBoxesQuery(int $shipmentId): Builder
    {
        return $this->model
            ->find($shipmentId)
            ->packingBoxes()
            ->query()
            ->with(['items']);
    }

    /**
     * Busca envios recentes
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
     * Busca envios com paginação
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

        if (isset($filters['order_id'])) {
            $query->where('order_id', $filters['order_id']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('tracking_number', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if (isset($filters['date_from'])) {
            $query->where('shipped_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('shipped_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Conta envios por status
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
     * Conta envios em trânsito
     * 
     * @return int
     */
    public function countInTransit(): int
    {
        return $this->model
            ->whereIn('status', ['shipped', 'in_transit', 'out_for_delivery'])
            ->count();
    }

    /**
     * Marca um envio como enviado
     * 
     * @param int $id ID do envio
     * @param array $data Dados do envio
     * @return Shipment
     * @throws \Exception
     */
    public function markAsShipped(int $id, array $data = []): Shipment
    {
        try {
            $updateData = [
                'status' => 'shipped',
                'shipped_at' => now(),
            ];

            if (isset($data['tracking_number'])) {
                $updateData['tracking_number'] = $data['tracking_number'];
            }

            if (isset($data['carrier'])) {
                $updateData['carrier'] = $data['carrier'];
            }

            return $this->update($id, $updateData);
        } catch (\Exception $e) {
            \Log::error('Erro ao marcar envio como enviado', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Marca um envio como entregue
     * 
     * @param int $id ID do envio
     * @param array $data Dados da entrega
     * @return Shipment
     * @throws \Exception
     */
    public function markAsDelivered(int $id, array $data = []): Shipment
    {
        try {
            $updateData = [
                'status' => 'delivered',
                'delivered_at' => now(),
            ];

            if (isset($data['delivery_date'])) {
                $updateData['delivery_date'] = $data['delivery_date'];
            }

            if (isset($data['received_by'])) {
                $updateData['received_by'] = $data['received_by'];
            }

            return $this->update($id, $updateData);
        } catch (\Exception $e) {
            \Log::error('Erro ao marcar envio como entregue', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
