# Guia de Gerenciamento de Widgets

## 📊 O que são Widgets?

Widgets são componentes visuais que aparecem no Dashboard do seu sistema. Eles exibem informações resumidas e estatísticas importantes como:

- Estatísticas de Pedidos de Compra
- Visão Geral Financeira
- Despesas de Projetos
- Utilização de Contêineres
- Calendário
- E mais...

---

## 🎯 Como Habilitar/Desabilitar Widgets

### Opção 1: Interface Gráfica (Recomendado)

**Passo 1:** Acesse o painel administrativo
- URL: `http://seu-dominio/panel`

**Passo 2:** Procure por "Personalizar Dashboard"
- Navegação: **Dashboard** → **Personalizar Dashboard**
- Ou acesse diretamente: `/panel/widget-selector`

**Passo 3:** Na página de personalização:
- ✅ **Marque** os widgets que deseja **habilitar**
- ❌ **Desmarque** os widgets que deseja **desabilitar**
- 🔄 **Arraste** os widgets para **reordenar** a exibição

**Passo 4:** Salve as mudanças
- Clique no botão **"Salvar Configuração"**
- Ou clique em **"Resetar para Padrão"** para voltar à configuração original

---

## 🔧 Como Habilitar/Desabilitar Widgets via Banco de Dados

Se preferir fazer alterações diretas no banco de dados:

### 1. Tabela `available_widgets`

Esta tabela controla quais widgets estão disponíveis no sistema:

```sql
-- Ver todos os widgets disponíveis
SELECT * FROM available_widgets;

-- Desabilitar um widget (não aparecerá na lista de seleção)
UPDATE available_widgets 
SET is_available = false 
WHERE widget_id = 'financial-overview';

-- Habilitar um widget
UPDATE available_widgets 
SET is_available = true 
WHERE widget_id = 'financial-overview';

-- Ver widgets que aparecem por padrão
SELECT * FROM available_widgets WHERE default_visible = true;
```

### 2. Tabela `dashboard_configurations`

Esta tabela armazena a configuração de cada usuário:

```sql
-- Ver configuração do usuário (ID 1)
SELECT * FROM dashboard_configurations WHERE user_id = 1;

-- Resetar configuração de um usuário
DELETE FROM dashboard_configurations WHERE user_id = 1;
-- Após deletar, o sistema usará os widgets padrão na próxima visualização

-- Ver widgets visíveis para um usuário
SELECT visible_widgets FROM dashboard_configurations WHERE user_id = 1;
```

---

## 📋 Widgets Disponíveis

| Widget ID | Título | Descrição | Categoria |
|-----------|--------|-----------|-----------|
| `purchase-order-stats` | Estatísticas de Pedidos | Resumo de pedidos de compra | Compras |
| `financial-overview` | Visão Geral Financeira | Resumo de receitas e despesas | Financeiro |
| `project-expenses` | Despesas de Projetos | Gastos por projeto | Projetos |
| `rfq-stats` | Estatísticas de RFQ | Solicitações de cotação | Vendas |
| `container-utilization` | Utilização de Contêineres | Eficiência de contêineres | Logística |
| `calendar` | Calendário | Calendário de eventos | Geral |
| `related-documents` | Documentos Relacionados | Documentos recentes | Documentos |
| `generated-documents-stats` | Documentos Gerados | Estatísticas de documentos | Documentos |

---

## 🔐 Permissões de Widgets

Alguns widgets podem exigir permissões específicas:

```php
// Verificar se um widget requer permissão
$widget = AvailableWidget::getById('financial-overview');
$requiredPermission = $widget->requiresPermission();

// Exemplo: 'view-financial-reports'
```

Se um usuário não tiver a permissão necessária, o widget não será exibido mesmo que esteja habilitado.

---

## 🛠️ Estrutura Técnica

### Arquivos Principais

1. **`app/Filament/Pages/WidgetSelectorPage.php`**
   - Página de personalização do dashboard
   - Gerencia seleção e reordenação de widgets

2. **`app/Models/AvailableWidget.php`**
   - Modelo que controla widgets disponíveis
   - Define quais widgets podem ser usados

3. **`app/Models/DashboardConfiguration.php`**
   - Armazena configuração por usuário
   - Guarda widgets visíveis e ordem

4. **`app/Services/WidgetRegistryService.php`**
   - Registra widgets no sistema
   - Gerencia disponibilidade de widgets

5. **`app/Services/DashboardConfigurationService.php`**
   - Gerencia configurações de dashboard
   - Carrega/salva preferências do usuário

### Widgets Customizados

Todos os widgets estão em: `app/Filament/Widgets/`

Exemplo de widget:
```php
<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;

class FinancialOverviewWidget extends ChartWidget
{
    protected static ?string $heading = 'Visão Geral Financeira';
    
    protected function getType(): string
    {
        return 'line';
    }
    
    protected function getData(): array
    {
        return [
            'datasets' => [...],
            'labels' => [...],
        ];
    }
}
```

---

## 📝 Configuração Padrão

### Widgets Habilitados por Padrão

Quando um novo usuário acessa o dashboard, estes widgets aparecem:

- ✅ Estatísticas de Pedidos de Compra
- ✅ Visão Geral Financeira
- ✅ Calendário
- ✅ Documentos Relacionados

### Como Mudar Padrão

1. **Via Banco de Dados:**
   ```sql
   UPDATE available_widgets 
   SET default_visible = true 
   WHERE widget_id = 'container-utilization';
   ```

2. **Via Código:**
   ```php
   AvailableWidget::where('widget_id', 'container-utilization')
       ->update(['default_visible' => true]);
   ```

---

## 🚀 Adicionar Novo Widget

Para adicionar um novo widget ao sistema:

### 1. Criar a Classe do Widget

```php
<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class MyCustomWidget extends Widget
{
    protected static ?string $heading = 'Meu Widget Customizado';
    
    protected static ?int $sort = 3;
    
    protected static string $view = 'filament.widgets.my-custom-widget';
}
```

### 2. Registrar no Banco de Dados

```php
AvailableWidget::create([
    'widget_id' => 'my-custom-widget',
    'title' => 'Meu Widget Customizado',
    'description' => 'Descrição do widget',
    'class' => 'App\Filament\Widgets\MyCustomWidget',
    'icon' => 'heroicon-o-star',
    'category' => 'Custom',
    'is_available' => true,
    'default_visible' => false,
    'requires_permission' => null,
]);
```

### 3. Registrar no Dashboard

Adicione o widget à classe do Dashboard:

```php
// app/Filament/Pages/Dashboard.php
protected function getWidgets(): array
{
    return [
        MyCustomWidget::class,
        // ... outros widgets
    ];
}
```

---

## ❓ Perguntas Frequentes

**P: Como resetar o dashboard para a configuração padrão?**
R: Na página "Personalizar Dashboard", clique em "Resetar para Padrão".

**P: Um widget desapareceu. O que fazer?**
R: Verifique se:
1. O widget está habilitado em `available_widgets` (is_available = true)
2. Você tem permissão para visualizá-lo
3. Ele está selecionado em sua configuração pessoal

**P: Posso ter diferentes widgets para diferentes usuários?**
R: Sim! Cada usuário tem sua própria configuração de dashboard. Cada um pode habilitar/desabilitar widgets independentemente.

**P: Como esconder um widget de todos os usuários?**
R: Atualize `is_available = false` na tabela `available_widgets`.

**P: Widgets requerem permissões especiais?**
R: Alguns sim. Verifique a coluna `requires_permission` em `available_widgets`.

---

## 📞 Suporte

Se tiver dúvidas sobre widgets ou precisar de ajuda para customizá-los, consulte:

- Documentação do Filament: https://filamentphp.com/docs/3.x/widgets
- Código dos widgets: `app/Filament/Widgets/`
- Models: `app/Models/AvailableWidget.php` e `app/Models/DashboardConfiguration.php`
