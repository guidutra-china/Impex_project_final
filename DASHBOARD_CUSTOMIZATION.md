# Sistema de Widgets Personalizáveis do Dashboard

## Visão Geral

O sistema de widgets personalizáveis permite que cada usuário configure quais widgets deseja visualizar em seu dashboard, na ordem que preferir, com suporte a permissões e configurações individuais por widget.

## Arquitetura

### Modelos

#### `DashboardConfiguration`
Armazena as preferências de dashboard de cada usuário:
- `user_id`: Referência ao usuário (único)
- `visible_widgets`: Array JSON com IDs dos widgets visíveis
- `widget_order`: Array JSON com a ordem dos widgets
- `widget_settings`: Array JSON com configurações por widget

#### `AvailableWidget`
Registro de todos os widgets disponíveis no sistema:
- `widget_id`: Identificador único do widget
- `title`: Título exibido ao usuário
- `description`: Descrição do widget
- `class`: Classe PHP do widget
- `icon`: Ícone do widget (Heroicon)
- `category`: Categoria do widget (general, financial, etc)
- `is_available`: Se o widget está disponível
- `default_visible`: Se deve aparecer por padrão para novos usuários
- `requires_permission`: Permissão necessária para visualizar

### Serviços

#### `DashboardConfigurationService`
Gerencia as configurações de dashboard do usuário:

```php
// Obter ou criar configuração do usuário
$config = $service->getUserConfiguration($user);

// Atualizar widgets visíveis
$service->updateVisibleWidgets($user, ['calendar', 'rfq_stats']);

// Atualizar ordem dos widgets
$service->updateWidgetOrder($user, ['rfq_stats', 'calendar']);

// Atualizar configurações de um widget
$service->updateWidgetSettings($user, 'calendar', ['show_weekends' => false]);

// Resetar para configuração padrão
$service->resetToDefault($user);

// Obter configuração padrão
$defaultConfig = $service->getDefaultConfiguration();
```

#### `WidgetRegistryService`
Gerencia o registro de widgets disponíveis:

```php
// Registrar um novo widget
$service->registerWidget('my_widget', 'App\Filament\Widgets\MyWidget', [
    'title' => 'Meu Widget',
    'description' => 'Descrição do widget',
    'icon' => 'heroicon-o-chart-bar',
]);

// Obter widgets disponíveis para um usuário
$widgets = $service->getAvailableWidgets($user);

// Obter widget por ID
$widget = $service->getWidgetById('calendar');

// Seed dos widgets padrão
$service->seedDefaultWidgets();
```

### Interface Filament

#### `WidgetSelectorPage`
Página interativa para personalização do dashboard:
- Exibe todos os widgets disponíveis com checkboxes
- Permite drag-and-drop para reordenar widgets
- Botão para resetar configuração padrão
- Notificações de sucesso

**Rota**: `/admin/widget-selector`

#### `DashboardConfigurationResource`
Recurso administrativo para gerenciar configurações:
- Listar todas as configurações de usuários
- Editar configurações
- Resetar para padrão
- Deletar configurações

### Dashboard Dinâmico

O arquivo `Dashboard.php` foi modificado para:
1. Carregar a configuração do usuário ao renderizar
2. Mapear IDs de widgets para suas classes
3. Respeitar a ordem configurada pelo usuário
4. Fallback para widgets padrão se não houver configuração

## Widgets Disponíveis

Os seguintes widgets estão registrados por padrão:

| ID | Título | Classe | Descrição |
|----|--------|--------|-----------|
| `calendar` | Calendário | `CalendarWidget` | Visualize eventos e prazos |
| `rfq_stats` | Estatísticas de RFQ | `RfqStatsWidget` | Acompanhe solicitações |
| `purchase_order_stats` | Estatísticas de Pedidos | `PurchaseOrderStatsWidget` | Monitore pedidos |
| `financial_overview` | Visão Financeira | `FinancialOverviewWidget` | Resumo financeiro |

## Fluxo de Uso

### Para Usuários Finais

1. Acessar `/admin/widget-selector`
2. Selecionar/desselecionar widgets desejados
3. Arrastar widgets para reordenar
4. Clicar "Salvar Configuração"
5. Dashboard será atualizado com as preferências

### Para Administradores

1. Acessar `/admin/dashboard-configurations`
2. Visualizar configurações de todos os usuários
3. Editar configurações de um usuário específico
4. Resetar para padrão se necessário

### Para Desenvolvedores

#### Adicionar um novo widget

1. Criar a classe do widget em `app/Filament/Widgets/`
2. Registrar no seeder `AvailableWidgetSeeder`:

```php
[
    'widget_id' => 'my_widget',
    'title' => 'Meu Widget',
    'description' => 'Descrição',
    'class' => 'App\Filament\Widgets\MyWidget',
    'icon' => 'heroicon-o-chart-bar',
    'is_available' => true,
    'required_permission' => null,
],
```

3. Adicionar ao mapa em `Dashboard.php`:

```php
protected array $widgetClassMap = [
    // ... widgets existentes
    'my_widget' => MyWidget::class,
];
```

4. Executar seed para registrar no banco:

```bash
php artisan db:seed --class=AvailableWidgetSeeder
```

## Testes

Os testes incluem:

- `DashboardConfigurationServiceTest`: Testes do serviço de configuração
- `WidgetRegistryServiceTest`: Testes do registro de widgets
- `DashboardConfigurationTest`: Testes do modelo
- `AvailableWidgetTest`: Testes do modelo de widgets

Executar testes:

```bash
php artisan test tests/Unit/Services/DashboardConfigurationServiceTest.php
php artisan test tests/Unit/Services/WidgetRegistryServiceTest.php
php artisan test tests/Unit/Models/DashboardConfigurationTest.php
php artisan test tests/Unit/Models/AvailableWidgetTest.php
```

## Permissões

O sistema suporta permissões por widget através do campo `requires_permission` em `AvailableWidget`. O serviço `WidgetRegistryService` filtra automaticamente widgets que o usuário não tem permissão de visualizar.

## Migração de Dados

Para usuários existentes, a configuração padrão é criada automaticamente na primeira vez que acessam o dashboard. A configuração padrão inclui todos os widgets com `default_visible = true`.

## Performance

- Configurações são cacheadas por usuário
- Queries são otimizadas com índices nas tabelas
- Widgets são carregados apenas se visíveis
- Sem N+1 queries

## Segurança

- Cada usuário só pode ver/editar sua própria configuração
- Permissões são validadas no serviço
- Widgets não registrados não podem ser adicionados
- Validação de entrada em todas as operações
