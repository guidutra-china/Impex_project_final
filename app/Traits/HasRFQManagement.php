<?php

namespace App\Traits;

use App\Models\RFQSupplierStatus;
use Illuminate\Support\Collection;

/**
 * HasRFQManagement
 *
 * Trait que encapsula a lógica de gerenciamento de RFQ (Request for Quotation)
 * Pode ser usado por qualquer model que precise gerenciar RFQs
 *
 * Responsabilidades:
 * - Verificar se RFQ foi enviado para fornecedor
 * - Marcar RFQ como enviado para fornecedor
 * - Obter status de fornecedor
 * - Contar fornecedores correspondentes e que receberam RFQ
 */
trait HasRFQManagement
{
    /**
     * Verifica se o RFQ foi enviado para um fornecedor específico
     *
     * @param int $supplierId
     * @return bool
     */
    public function isSentToSupplier(int $supplierId): bool
    {
        return $this->supplierStatuses()
            ->where('supplier_id', $supplierId)
            ->where('sent', true)
            ->exists();
    }

    /**
     * Obtém o status de envio para um fornecedor específico
     *
     * @param int $supplierId
     * @return RFQSupplierStatus|null
     */
    public function getSupplierStatus(int $supplierId): ?RFQSupplierStatus
    {
        return $this->supplierStatuses()
            ->where('supplier_id', $supplierId)
            ->first();
    }

    /**
     * Marca o RFQ como enviado para um fornecedor
     *
     * @param int $supplierId
     * @param string $method
     * @return RFQSupplierStatus
     */
    public function markSentToSupplier(int $supplierId, string $method = 'email'): RFQSupplierStatus
    {
        $status = RFQSupplierStatus::updateOrCreate(
            [
                'order_id' => $this->id,
                'supplier_id' => $supplierId,
            ],
            [
                'sent' => true,
                'sent_at' => now(),
                'sent_method' => $method,
                'sent_by' => auth()->id(),
            ]
        );

        return $status;
    }

    /**
     * Obtém a contagem de fornecedores que receberam este RFQ
     *
     * @return int
     */
    public function getSentSuppliersCount(): int
    {
        return $this->supplierStatuses()->where('sent', true)->count();
    }

    /**
     * Obtém a contagem de fornecedores correspondentes para este RFQ
     *
     * @return int
     */
    public function getMatchingSuppliersCount(): int
    {
        return $this->matchingSuppliers()->count();
    }

    /**
     * Obtém os fornecedores que correspondem à categoria deste RFQ
     *
     * @return Collection
     */
    abstract public function matchingSuppliers(): Collection;

    /**
     * Obtém a relação de status de fornecedor
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    abstract public function supplierStatuses();
}
