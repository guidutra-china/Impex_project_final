<?php

namespace App\Services;

use App\Models\DashboardConfiguration;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class DashboardConfigurationService
{
    private WidgetRegistryService $registryService;

    public function __construct(WidgetRegistryService $registryService)
    {
        $this->registryService = $registryService;
    }

    /**
     * Obter ou criar configuração do dashboard para um usuário
     */
    public function getOrCreateConfiguration(User $user): DashboardConfiguration
    {
        $config = DashboardConfiguration::where('user_id', $user->id)->first();

        if (!$config) {
            $config = $this->createDefaultConfiguration($user);
        }

        return $config;
    }

    /**
     * Criar configuração padrão para novo usuário
     */
    public function createDefaultConfiguration(User $user): DashboardConfiguration
    {
        $defaultWidgets = $this->getDefaultWidgets($user);

        return DashboardConfiguration::create([
            'user_id' => $user->id,
            'visible_widgets' => $defaultWidgets,
            'widget_order' => $defaultWidgets,
            'widget_settings' => [],
        ]);
    }

    /**
     * Obter widgets padrão para um usuário
     */
    public function getDefaultWidgets(User $user): array
    {
        $availableWidgets = $this->registryService->getAvailableWidgetsForUser($user);

        return array_map(fn($w) => $w['id'], $availableWidgets);
    }

    /**
     * Obter widgets visíveis para um usuário
     */
    public function getVisibleWidgets(User $user): array
    {
        $config = $this->getOrCreateConfiguration($user);
        $visibleIds = $config->getVisibleWidgetsOrdered();

        $widgets = [];
        foreach ($visibleIds as $id) {
            $widget = $this->registryService->getWidget($id);
            if ($widget) {
                $widgets[] = $widget;
            }
        }

        return $widgets;
    }

    /**
     * Obter widgets visíveis com suas classes
     */
    public function getVisibleWidgetClasses(User $user): array
    {
        $config = $this->getOrCreateConfiguration($user);
        $visibleIds = $config->getVisibleWidgetsOrdered();

        $classes = [];
        foreach ($visibleIds as $id) {
            $widget = $this->registryService->getWidget($id);
            if ($widget) {
                $classes[] = $widget['class'];
            }
        }

        return $classes;
    }

    /**
     * Adicionar widget à configuração do usuário
     */
    public function addWidget(User $user, string $widgetId): bool
    {
        if (!$this->registryService->widgetExists($widgetId)) {
            return false;
        }

        $config = $this->getOrCreateConfiguration($user);
        $config->addWidget($widgetId);

        $this->clearCache($user);

        return true;
    }

    /**
     * Remover widget da configuração do usuário
     */
    public function removeWidget(User $user, string $widgetId): bool
    {
        $config = $this->getOrCreateConfiguration($user);
        $config->removeWidget($widgetId);

        $this->clearCache($user);

        return true;
    }

    /**
     * Atualizar ordem dos widgets
     */
    public function updateWidgetOrder(User $user, array $order): bool
    {
        // Validar que todos os widgets na ordem existem
        foreach ($order as $widgetId) {
            if (!$this->registryService->widgetExists($widgetId)) {
                return false;
            }
        }

        $config = $this->getOrCreateConfiguration($user);
        $config->updateWidgetOrder($order);

        $this->clearCache($user);

        return true;
    }

    /**
     * Atualizar configurações de um widget
     */
    public function updateWidgetSettings(User $user, string $widgetId, array $settings): bool
    {
        if (!$this->registryService->widgetExists($widgetId)) {
            return false;
        }

        $config = $this->getOrCreateConfiguration($user);
        $config->updateWidgetSettings($widgetId, $settings);

        $this->clearCache($user);

        return true;
    }

    /**
     * Resetar para configuração padrão
     */
    public function resetToDefault(User $user): void
    {
        $config = $this->getOrCreateConfiguration($user);
        $defaultWidgets = $this->getDefaultWidgets($user);
        $config->resetToDefault($defaultWidgets);

        $this->clearCache($user);
    }

    /**
     * Obter todos os widgets disponíveis para um usuário
     */
    public function getAllAvailableWidgets(User $user): array
    {
        return $this->registryService->getAvailableWidgetsForUser($user);
    }

    /**
     * Obter configuração atual do usuário
     */
    public function getConfiguration(User $user): array
    {
        $config = $this->getOrCreateConfiguration($user);

        return [
            'visible_widgets' => $config->visible_widgets,
            'widget_order' => $config->widget_order,
            'widget_settings' => $config->widget_settings,
        ];
    }

    /**
     * Limpar cache do usuário
     */
    private function clearCache(User $user): void
    {
        Cache::forget("dashboard_config_{$user->id}");
        Cache::forget("dashboard_widgets_{$user->id}");
    }

    /**
     * Validar configuração
     */
    public function validateConfiguration(User $user, array $data): array
    {
        $errors = [];

        // Validar visible_widgets
        if (isset($data['visible_widgets'])) {
            foreach ($data['visible_widgets'] as $widgetId) {
                if (!$this->registryService->widgetExists($widgetId)) {
                    $errors[] = "Widget '{$widgetId}' não existe";
                }
            }
        }

        // Validar widget_order
        if (isset($data['widget_order'])) {
            foreach ($data['widget_order'] as $widgetId) {
                if (!$this->registryService->widgetExists($widgetId)) {
                    $errors[] = "Widget '{$widgetId}' na ordem não existe";
                }
            }
        }

        return $errors;
    }
}
