<?php

namespace App\Filament\Pages;

use App\Models\AvailableWidget;
use App\Services\DashboardConfigurationService;
use App\Services\WidgetRegistryService;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class WidgetSelectorPage extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static UnitEnum|string|null $navigationGroup = 'Dashboard';

    protected static ?string $navigationLabel = 'Personalizar Dashboard';

    protected static ?string $title = 'Personalizar Dashboard';

    protected static bool $shouldRegisterNavigation = true;

    protected string $view = 'filament.pages.widget-selector-page';

    /**
     * Controle de acesso à página
     * 
     * Permite acesso a:
     * - Super admins (sempre)
     * - Usuários com permissão específica (quando configurada)
     * - Todos os usuários autenticados (fallback)
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();
        
        if (!$user) {
            return false;
        }
        
        // Super admins sempre têm acesso
        if (method_exists($user, 'hasRole') && $user->hasRole('super_admin')) {
            return true;
        }
        
        // Verificar permissão específica (se existir)
        if (method_exists($user, 'can') && $user->can('page_WidgetSelectorPage')) {
            return true;
        }
        
        // Fallback: permitir acesso a todos os usuários autenticados
        // TODO: Restringir a roles específicas quando necessário
        return true;
    }

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

        // Lazy initialize if not already done
        if (!isset($this->dashboardService)) {
            $this->dashboardService = app(DashboardConfigurationService::class);
        }

        $this->dashboardService->resetToDefault($user);

        $this->mount();

        \Filament\Notifications\Notification::make()
            ->title('Sucesso')
            ->body('Dashboard resetado para a configuração padrão')
            ->success()
            ->send();
    }
}
