<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardConfiguration extends Model
{
    protected $fillable = [
        'user_id',
        'visible_widgets',
        'widget_order',
        'widget_settings',
    ];

    protected $casts = [
        'visible_widgets' => 'array',
        'widget_order' => 'array',
        'widget_settings' => 'array',
    ];

    /**
     * Relacionamento com User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obter widgets visíveis ordenados
     */
    public function getVisibleWidgetsOrdered(): array
    {
        $order = $this->widget_order ?? [];
        $visible = $this->visible_widgets ?? [];

        // Retornar widgets visíveis na ordem especificada
        return array_intersect($order, $visible);
    }

    /**
     * Adicionar widget à configuração
     */
    public function addWidget(string $widgetId): void
    {
        $visible = $this->visible_widgets ?? [];
        $order = $this->widget_order ?? [];

        if (!in_array($widgetId, $visible)) {
            $visible[] = $widgetId;
            $order[] = $widgetId;

            $this->visible_widgets = $visible;
            $this->widget_order = $order;
            $this->save();
        }
    }

    /**
     * Remover widget da configuração
     */
    public function removeWidget(string $widgetId): void
    {
        $visible = $this->visible_widgets ?? [];
        $order = $this->widget_order ?? [];

        $visible = array_filter($visible, fn($w) => $w !== $widgetId);
        $order = array_filter($order, fn($w) => $w !== $widgetId);

        $this->visible_widgets = array_values($visible);
        $this->widget_order = array_values($order);
        $this->save();
    }

    /**
     * Atualizar ordem dos widgets
     */
    public function updateWidgetOrder(array $order): void
    {
        $this->widget_order = $order;
        $this->save();
    }

    /**
     * Obter configurações de um widget específico
     */
    public function getWidgetSettings(string $widgetId): array
    {
        $settings = $this->widget_settings ?? [];
        return $settings[$widgetId] ?? [];
    }

    /**
     * Atualizar configurações de um widget
     */
    public function updateWidgetSettings(string $widgetId, array $settings): void
    {
        $allSettings = $this->widget_settings ?? [];
        $allSettings[$widgetId] = $settings;

        $this->widget_settings = $allSettings;
        $this->save();
    }

    /**
     * Resetar para configuração padrão
     */
    public function resetToDefault(array $defaultWidgets): void
    {
        $this->visible_widgets = $defaultWidgets;
        $this->widget_order = $defaultWidgets;
        $this->widget_settings = [];
        $this->save();
    }
}
