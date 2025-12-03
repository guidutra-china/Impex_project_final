<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Models\Client;

/**
 * OrderNumberGenerator
 *
 * Responsável por gerar números de pedido únicos no formato [CLIENT_CODE]-[YY]-[NNNN]
 * Exemplo: AMA-25-0001
 *
 * Esta classe encapsula toda a lógica de geração de números de pedido, tornando-a
 * reutilizável e testável de forma independente.
 */
class OrderNumberGenerator
{
    /**
     * Gera um número de pedido único para um cliente
     *
     * @param Client $client
     * @return string
     * @throws \Exception Se o cliente não tiver código
     */
    public function generate(Client $client): string
    {
        if (!$client || !$client->code) {
            throw new \Exception('Cannot generate order number: Client not found or has no code');
        }

        $clientCode = $client->code;
        $year = now()->format('y');

        return $this->findNextAvailableNumber($clientCode, $year);
    }

    /**
     * Encontra o próximo número sequencial disponível para um cliente em um ano específico
     *
     * @param string $clientCode
     * @param string $year
     * @return string
     */
    private function findNextAvailableNumber(string $clientCode, string $year): string
    {
        $sequentialNumber = 1;

        do {
            $orderNumber = "{$clientCode}-{$year}-" . str_pad($sequentialNumber, 4, '0', STR_PAD_LEFT);

            $exists = Order::withTrashed()
                ->where('order_number', $orderNumber)
                ->exists();

            if ($exists) {
                $sequentialNumber++;
            }
        } while ($exists);

        return $orderNumber;
    }

    /**
     * Obtém o próximo número sequencial que será usado para um cliente
     *
     * @param Client $client
     * @return int
     */
    public function getNextSequentialNumber(Client $client): int
    {
        $year = now()->format('y');
        $clientCode = $client->code;

        // Encontra o maior número sequencial para este cliente neste ano
        $lastOrder = Order::withTrashed()
            ->where('order_number', 'like', "{$clientCode}-{$year}-%")
            ->orderBy('order_number', 'desc')
            ->first();

        if (!$lastOrder) {
            return 1;
        }

        // Extrai o número sequencial do formato [CODE]-[YY]-[NNNN]
        $parts = explode('-', $lastOrder->order_number);
        if (count($parts) === 3) {
            return (int)$parts[2] + 1;
        }

        return 1;
    }
}
