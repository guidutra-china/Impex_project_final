<?php

namespace App\Services\Order;

use App\Models\Order;

/**
 * OrderCalculator
 *
 * Responsável por todos os cálculos relacionados a pedidos, incluindo:
 * - Margem real considerando despesas do projeto
 * - Percentual de margem
 * - Despesas totais do projeto
 *
 * Esta classe encapsula toda a lógica de cálculo, tornando-a reutilizável e testável.
 */
class OrderCalculator
{
    /**
     * Calcula a margem real considerando despesas do projeto
     * Fórmula: Receita - Custos de Compra - Despesas do Projeto
     *
     * @param Order $order
     * @return float
     */
    public function calculateRealMargin(Order $order): float
    {
        $revenue = $this->getRevenue($order);
        $purchaseCosts = $this->getPurchaseCosts($order);
        $projectExpenses = $this->getProjectExpenses($order);

        return ($revenue - $purchaseCosts - $projectExpenses) / 100;
    }

    /**
     * Calcula o percentual de margem real
     *
     * @param Order $order
     * @return float
     */
    public function calculateRealMarginPercentage(Order $order): float
    {
        $revenue = $this->getRevenue($order);

        if ($revenue == 0) {
            return 0;
        }

        $realMargin = $this->calculateRealMargin($order);
        return ($realMargin / ($revenue / 100)) * 100;
    }

    /**
     * Obtém a receita total do pedido
     * Usa a cotação selecionada ou o valor total do pedido
     *
     * @param Order $order
     * @return int
     */
    public function getRevenue(Order $order): int
    {
        if ($order->selectedQuote) {
            return $order->selectedQuote->total_price_after_commission;
        }

        return $order->total_amount ?? 0;
    }

    /**
     * Obtém o custo total de compra
     *
     * @param Order $order
     * @return int
     */
    public function getPurchaseCosts(Order $order): int
    {
        return $order->purchaseOrders()->sum('total');
    }

    /**
     * Obtém o total de despesas do projeto
     *
     * @param Order $order
     * @return int
     */
    public function getProjectExpenses(Order $order): int
    {
        return $order->projectExpenses()->sum('amount_base_currency');
    }

    /**
     * Obtém o total de despesas do projeto em dólares
     *
     * @param Order $order
     * @return float
     */
    public function getProjectExpensesDollars(Order $order): float
    {
        return $this->getProjectExpenses($order) / 100;
    }

    /**
     * Calcula a média ponderada de comissão baseada nos itens do pedido
     *
     * @param Order $order
     * @return float|null
     */
    public function calculateCommissionAverage(Order $order): ?float
    {
        $items = $order->items;

        if ($items->isEmpty()) {
            return null;
        }

        $totalQuantity = $items->sum('quantity');
        $weightedSum = 0;

        foreach ($items as $item) {
            $weightedSum += ($item->commission_percent ?? 0) * $item->quantity;
        }

        return $totalQuantity > 0 ? $weightedSum / $totalQuantity : 0;
    }

    /**
     * Atualiza o valor total do pedido com a cotação mais barata
     *
     * @param Order $order
     * @return void
     */
    public function updateTotalAmountWithCheapestQuote(Order $order): void
    {
        $cheapest = $order->getCheapestQuote();
        if ($cheapest) {
            $order->total_amount = $cheapest->total_price_after_commission;
            $order->save();
        }
    }
}
