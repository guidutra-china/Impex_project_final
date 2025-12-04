# Guia de Implementação - Sistema de Widgets Personalizáveis

## Status da Implementação

✅ **COMPLETO** - Sistema de widgets personalizáveis totalmente implementado

## O que foi implementado

### 1. Modelos de Dados
- **DashboardConfiguration**: Armazena preferências de dashboard por usuário
- **AvailableWidget**: Registro de widgets disponíveis no sistema

### 2. Serviços
- **DashboardConfigurationService**: Gerencia configurações de dashboard
- **WidgetRegistryService**: Gerencia registro e disponibilidade de widgets

### 3. Interface Filament
- **WidgetSelectorPage**: Página interativa para personalização (com drag-and-drop)
- **DashboardConfigurationResource**: Resource administrativo

### 4. Banco de Dados
- Migrations para ambas as tabelas
- Seeder para widgets padrão
- Índices para otimização

### 5. Dashboard Dinâmico
- Dashboard.php atualizado para carregar widgets baseado em preferências do usuário
- Fallback para widgets padrão se não houver configuração

### 6. Testes
- Testes unitários para serviços e modelos
- Testes de integração para fluxo completo

## Como Usar

### Para Usuários Finais

1. **Acessar Personalização**
   ```
   Ir para: /admin/widget-selector
   ```

2. **Selecionar Widgets**
   - Marcar/desmarcar checkboxes dos widgets desejados
   - Apenas widgets marcados aparecerão no dashboard

3. **Reordenar Widgets**
   - Arrastar widgets na seção "Ordem dos Widgets"
   - Ordem será respeitada no dashboard

4. **Salvar**
   - Clicar "Salvar Configuração"
   - Notificação de sucesso será exibida

5. **Resetar (Opcional)**
   - Clicar "Resetar para Padrão" para voltar à configuração original

### Para Administradores

1. **Gerenciar Configurações**
   ```
   Ir para: /admin/dashboard-configurations
   ```

2. **Operações Disponíveis**
   - Listar todas as configurações de usuários
   - Editar configuração de um usuário
   - Resetar para padrão
   - Deletar configuração

### Para Desenvolvedores

#### Adicionar um Novo Widget

1. **Criar a classe do widget**
   ```php
   // app/Filament/Widgets/MyNewWidget.php
   namespace App\Filament\Widgets;
   
   use Filament\Widgets\Widget;
   
   class MyNewWidget extends Widget
   {
       protected static ?string $heading = 'Meu Widget';
       protected static string $view = 'filament.widgets.my-new-widget';
   }
   ```

2. **Criar a view**
   ```blade
   <!-- resources/views/filament/widgets/my-new-widget.blade.php -->
   <x-filament-widgets::widget>
       <div class="p-6">
           <!-- Conteúdo do widget -->
       </div>
   </x-filament-widgets::widget>
   ```

3. **Registrar no seeder**
   ```php
   // database/seeders/AvailableWidgetSeeder.php
   [
       'widget_id' => 'my_new_widget',
       'title' => 'Meu Novo Widget',
       'description' => 'Descrição do widget',
       'class' => 'App\Filament\Widgets\MyNewWidget',
       'icon' => 'heroicon-o-chart-bar',
       'is_available' => true,
       'default_visible' => false,
       'required_permission' => null,
   ],
   ```

4. **Adicionar ao mapa do Dashboard**
   ```php
   // app/Filament/Pages/Dashboard.php
   protected array $widgetClassMap = [
       // ... widgets existentes
       'my_new_widget' => MyNewWidget::class,
   ];
   ```

5. **Executar seed**
   ```bash
   php artisan db:seed --class=AvailableWidgetSeeder
   ```

#### Adicionar Permissões a um Widget

1. **Definir permissão no seeder**
   ```php
   'required_permission' => 'view_financial_data',
   ```

2. **O serviço filtrará automaticamente** widgets que o usuário não tem permissão

#### Adicionar Configurações por Widget

1. **Usuário salva configurações**
   ```php
   $service->updateWidgetSettings($user, 'calendar', [
       'show_weekends' => false,
       'theme' => 'dark',
   ]);
   ```

2. **Widget acessa configurações**
   ```php
   $config = DashboardConfiguration::where('user_id', auth()->id())->first();
   $settings = $config->widget_settings['calendar'] ?? [];
   ```

## Estrutura de Arquivos

```
app/
├── Filament/
│   ├── Pages/
│   │   ├── Dashboard.php (✅ Modificado)
│   │   └── WidgetSelectorPage.php (✅ Novo)
│   └── Resources/
│       └── DashboardConfigurationResource.php (✅ Novo)
│           └── Pages/
│               ├── ListDashboardConfigurations.php (✅ Novo)
│               └── EditDashboardConfiguration.php (✅ Novo)
├── Models/
│   ├── DashboardConfiguration.php (✅ Novo)
│   └── AvailableWidget.php (✅ Novo)
└── Services/
    ├── DashboardConfigurationService.php (✅ Novo)
    └── WidgetRegistryService.php (✅ Novo)

database/
├── migrations/
│   ├── 2025_12_03_221322_create_dashboard_configurations_table.php (✅ Novo)
│   └── 2025_12_03_221323_create_available_widgets_table.php (✅ Novo)
└── seeders/
    ├── AvailableWidgetSeeder.php (✅ Novo)
    └── DatabaseSeeder.php (✅ Modificado)

resources/views/filament/pages/
└── widget-selector-page.blade.php (✅ Novo)

tests/
├── Unit/
│   ├── Models/
│   │   ├── DashboardConfigurationTest.php (✅ Novo)
│   │   └── AvailableWidgetTest.php (✅ Novo)
│   └── Services/
│       ├── DashboardConfigurationServiceTest.php (✅ Novo)
│       └── WidgetRegistryServiceTest.php (✅ Novo)
└── Feature/Dashboard/
    └── CustomizableDashboardTest.php (✅ Novo)

DASHBOARD_CUSTOMIZATION.md (✅ Novo)
DASHBOARD_IMPLEMENTATION.md (✅ Novo)
```

## Fluxo de Dados

```
┌─────────────────────────────────────────────────────────────┐
│                    Usuário Acessa Dashboard                 │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
        ┌──────────────────────────────────────┐
        │   Dashboard.php (getWidgets())       │
        │   - Obtém user_id do Auth            │
        └──────────────────────┬───────────────┘
                               │
                               ▼
        ┌──────────────────────────────────────┐
        │  DashboardConfigurationService       │
        │  - getUserConfiguration($user)       │
        │  - Cria default se não existir       │
        └──────────────────────┬───────────────┘
                               │
                               ▼
        ┌──────────────────────────────────────┐
        │   DashboardConfiguration Model       │
        │   - Lê visible_widgets               │
        │   - Lê widget_order                  │
        └──────────────────────┬───────────────┘
                               │
                               ▼
        ┌──────────────────────────────────────┐
        │   Dashboard.php (mapeia IDs)         │
        │   - Converte IDs para classes        │
        │   - Respeita ordem                   │
        └──────────────────────┬───────────────┘
                               │
                               ▼
        ┌──────────────────────────────────────┐
        │   Widgets Renderizados               │
        │   - Na ordem configurada             │
        │   - Apenas visíveis                  │
        └──────────────────────────────────────┘
```

## Fluxo de Personalização

```
┌─────────────────────────────────────────────────────────────┐
│        Usuário Acessa /admin/widget-selector                │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
        ┌──────────────────────────────────────┐
        │   WidgetSelectorPage (mount)         │
        │   - Carrega AvailableWidgets         │
        │   - Carrega configuração do usuário  │
        └──────────────────────┬───────────────┘
                               │
                               ▼
        ┌──────────────────────────────────────┐
        │   widget-selector-page.blade.php     │
        │   - Exibe checkboxes                 │
        │   - Permite drag-and-drop            │
        └──────────────────────┬───────────────┘
                               │
                    ┌──────────┴──────────┐
                    │                     │
                    ▼                     ▼
        ┌──────────────────────┐ ┌──────────────────────┐
        │   Usuário Seleciona  │ │  Usuário Reordena    │
        │   Widgets            │ │  Widgets (Drag-Drop) │
        └──────────────────────┘ └──────────────────────┘
                    │                     │
                    └──────────┬──────────┘
                               │
                               ▼
        ┌──────────────────────────────────────┐
        │   saveConfiguration() (Livewire)     │
        │   - updateVisibleWidgets()           │
        │   - updateWidgetOrder()              │
        └──────────────────────┬───────────────┘
                               │
                               ▼
        ┌──────────────────────────────────────┐
        │   DashboardConfiguration Atualizada  │
        │   - Salva no banco                   │
        └──────────────────────┬───────────────┘
                               │
                               ▼
        ┌──────────────────────────────────────┐
        │   Notificação de Sucesso             │
        │   - Dashboard será atualizado        │
        └──────────────────────────────────────┘
```

## Executando Testes

```bash
# Testes unitários
php artisan test tests/Unit/Services/DashboardConfigurationServiceTest.php
php artisan test tests/Unit/Services/WidgetRegistryServiceTest.php
php artisan test tests/Unit/Models/DashboardConfigurationTest.php
php artisan test tests/Unit/Models/AvailableWidgetTest.php

# Testes de integração
php artisan test tests/Feature/Dashboard/CustomizableDashboardTest.php

# Todos os testes
php artisan test
```

## Troubleshooting

### Widgets não aparecem no seletor
- Verificar se `is_available = true` em `AvailableWidget`
- Executar seed: `php artisan db:seed --class=AvailableWidgetSeeder`

### Configuração não é salva
- Verificar se `DashboardConfiguration` foi criada para o usuário
- Verificar logs: `storage/logs/laravel.log`

### Permissões não funcionam
- Verificar se `requires_permission` está definido corretamente
- Verificar se usuário tem a permissão necessária

### Drag-and-drop não funciona
- Verificar se Livewire está instalado e configurado
- Verificar console do navegador para erros JavaScript

## Performance

- Configurações são cacheadas por usuário
- Queries otimizadas com índices
- Sem N+1 queries
- Widgets carregados apenas se visíveis

## Segurança

- Cada usuário só pode editar sua própria configuração
- Permissões validadas no serviço
- Widgets não registrados não podem ser adicionados
- Validação de entrada em todas as operações

## Próximos Passos (Opcional)

1. **Cache de Configurações**
   - Implementar cache Redis para configurações
   - Invalidar cache ao atualizar

2. **Presets de Widgets**
   - Permitir usuários salvarem múltiplos presets
   - Compartilhar presets entre usuários

3. **Analytics**
   - Rastrear quais widgets são mais usados
   - Sugestões de widgets baseadas em uso

4. **Temas por Widget**
   - Permitir customização de cores/tamanho
   - Salvar preferências de tema

## Suporte

Para dúvidas ou problemas, consulte:
- `DASHBOARD_CUSTOMIZATION.md` - Documentação técnica
- Testes em `tests/` - Exemplos de uso
- Código em `app/Services/` - Implementação detalhada
