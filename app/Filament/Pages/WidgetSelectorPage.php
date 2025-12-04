<?php

namespace App\Filament\Pages;

use App\Models\AvailableWidget;
use App\Models\DashboardConfiguration;
use App\Services\DashboardConfigurationService;
use App\Services\WidgetRegistryService;
use BackedEnum;
use Filament\Forms;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class WidgetSelectorPage extends Page
    {
        protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-squares-2x2';

        protected string $view = 'filament.pages.widget-selector-page';

        protected static UnitEnum|string|null $navigationGroup = 'Dashboard';

    protected static ?string $navigationLabel = 'Personalizar Dashboard';

    protected static ?string $title = 'Personalizar Dashboard';

    public array $availableWidgets = [];

    public array $selectedWidgets = [];

    public array $widgetOrder = [];

    protected ?DashboardConfigurationService $dashboardService = null;

    protected ?WidgetRegistryService $widgetService = null;

    public function mount(): void
    {
        $this->dashboardService = app(DashboardConfigurationService::class);
        $this->widgetService = app(WidgetRegistryService::class);

        $user = Auth::user();

        // Carregar widgets disponíveis
        $this->availableWidgets = AvailableWidget::where('is_available', true)
            ->get()
            ->map(fn($w) => [
                'id' => $w->widget_id,
                'title' => $w->title,
                'description' => $w->description,
                'icon' => $w->icon,
            ])
            ->toArray();

        // Carregar configuração do usuário
        $config = $this->dashboardService->getOrCreateConfiguration($user);

        $this->selectedWidgets = $config->visible_widgets ?? [];
        $this->widgetOrder = $config->widget_order ?? [];
    }

    public function saveConfiguration(): void
    {
        $user = Auth::user();

        // Lazy initialize if not already done
        if (!isset($this->dashboardService)) {
            $this->dashboardService = app(DashboardConfigurationService::class);
        }

        // Obter configuração atual
        $config = $this->dashboardService->getOrCreateConfiguration($user);
        
        // Atualizar widgets visíveis e ordem
        $config->visible_widgets = $this->selectedWidgets;
        $config->widget_order = $this->widgetOrder;
        $config->save();

        \Filament\Notifications\Notification::make()
            ->title('Sucesso')
            ->body('Configuração do dashboard atualizada com sucesso')
            ->success()
            ->send();
    }

    public function resetToDefault(): void
    {
        $user = Auth::user();
        $this->dashboardService->resetToDefault($user);

        $this->mount();

        \Filament\Notifications\Notification::make()
            ->title('Sucesso')
            ->body('Dashboard resetado para a configuração padrão')
            ->success()
            ->send();
    }
}
