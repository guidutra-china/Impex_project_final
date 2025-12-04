<?php

namespace App\Filament\Pages;

use App\Models\AvailableWidget;
use App\Services\DashboardConfigurationService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class WidgetSelectorPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-squares-2x2';
    
    protected static UnitEnum|string|null $navigationGroup = 'Dashboard';
    
    protected static ?string $navigationLabel = 'Personalizar Dashboard';
    
    protected static ?string $title = 'Personalizar Dashboard';
    
    protected static bool $shouldRegisterNavigation = true;
    
    protected static string $view = 'filament.pages.widget-selector-page';
    
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
        return true;
    }
    
    public ?array $data = [];
    
    protected ?DashboardConfigurationService $dashboardService = null;
    
    public function mount(): void
    {
        $this->dashboardService = app(DashboardConfigurationService::class);
        $user = Auth::user();
        
        $config = $this->dashboardService->getOrCreateConfiguration($user);
        
        $this->form->fill([
            'selected_widgets' => $config->visible_widgets ?? [],
        ]);
    }
    
    public function form(Form $form): Form
    {
        $availableWidgets = AvailableWidget::where('is_available', true)
            ->get()
            ->mapWithKeys(fn($w) => [
                $w->widget_id => $w->title . ' - ' . $w->description
            ])
            ->toArray();
        
        return $form
            ->schema([
                Section::make('Widgets Disponíveis')
                    ->description('Selecione os widgets que deseja exibir no seu dashboard')
                    ->icon('heroicon-o-squares-2x2')
                    ->schema([
                        CheckboxList::make('selected_widgets')
                            ->label('')
                            ->options($availableWidgets)
                            ->columns(2)
                            ->gridDirection('row')
                            ->bulkToggleable()
                            ->required(false)
                            ->live(),
                    ])
                    ->collapsible(),
            ])
            ->statePath('data');
    }
    
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Salvar Configuração')
                ->action('saveConfiguration')
                ->color('primary')
                ->icon('heroicon-o-check')
                ->size('lg'),
            
            Action::make('reset')
                ->label('Resetar para Padrão')
                ->action('resetToDefault')
                ->color('gray')
                ->icon('heroicon-o-arrow-path')
                ->size('lg')
                ->requiresConfirmation()
                ->modalHeading('Resetar Dashboard')
                ->modalDescription('Tem certeza que deseja resetar o dashboard para a configuração padrão?')
                ->modalSubmitActionLabel('Sim, resetar'),
        ];
    }
    
    public function saveConfiguration(): void
    {
        $user = Auth::user();
        
        if (!isset($this->dashboardService)) {
            $this->dashboardService = app(DashboardConfigurationService::class);
        }
        
        $config = $this->dashboardService->getOrCreateConfiguration($user);
        $config->visible_widgets = $this->data['selected_widgets'] ?? [];
        $config->save();
        
        Notification::make()
            ->title('Sucesso!')
            ->body('Configuração do dashboard atualizada com sucesso')
            ->success()
            ->send();
    }
    
    public function resetToDefault(): void
    {
        $user = Auth::user();
        
        if (!isset($this->dashboardService)) {
            $this->dashboardService = app(DashboardConfigurationService::class);
        }
        
        $this->dashboardService->resetToDefault($user);
        $this->mount();
        
        Notification::make()
            ->title('Sucesso!')
            ->body('Dashboard resetado para a configuração padrão')
            ->success()
            ->send();
    }
}
