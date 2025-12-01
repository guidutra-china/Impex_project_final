# Dashboard Widgets - Documentação

## 📊 Visão Geral

O dashboard do sistema IMPEX agora conta com **3 widgets principais** que fornecem métricas executivas em tempo real, respeitando as permissões de ownership de clientes.

---

## 🔐 Controle de Acesso

### **Ownership Automático**

Todos os widgets respeitam automaticamente o sistema de ownership:

- **Usuários com `can_see_all = true`**: Veem métricas de **todos os clientes**
- **Usuários com `can_see_all = false`**: Veem métricas apenas dos **seus clientes** (onde `client.user_id = user.id`)

### **Como Funciona**

Os widgets utilizam as queries dos models que já possuem o `ClientOwnershipScope` aplicado globalmente:

```php
// Exemplo: Order model
protected static function booted(): void
{
    static::addGlobalScope(new ClientOwnershipScope());
}
```

Isso significa que **não é necessário código adicional** nos widgets para filtrar por ownership - o filtro é aplicado automaticamente em todas as queries.

---

## 📈 Widget 1: RFQ Stats Widget

**Arquivo:** `app/Filament/Widgets/RfqStatsWidget.php`

### **Métricas Exibidas**

#### **1. RFQs Ativas**
- **Descrição:** Total de RFQs nos status: Draft, Pendente, Enviadas, Cotadas
- **Cor:** Azul (info)
- **Gráfico:** Linha mostrando RFQs criadas nos últimos 7 dias
- **Ícone:** Documento de texto

#### **2. Cotações Recebidas**
- **Descrição:** Total de cotações recebidas de fornecedores + tempo médio de resposta
- **Cor:** Verde (se tempo < 3 dias) ou Amarelo (se tempo >= 3 dias)
- **Ícone:** Caixa de entrada
- **Cálculo:** Média de dias entre `created_at` e `updated_at` das cotações

#### **3. Taxa de Conversão**
- **Descrição:** Percentual de RFQs ganhas vs total de RFQs
- **Cor:** 
  - Verde: >= 30%
  - Amarelo: >= 15% e < 30%
  - Vermelho: < 15%
- **Fórmula:** `(RFQs com status 'won' / Total de RFQs) * 100`
- **Ícone:** Círculo de check

#### **4. RFQs Este Mês**
- **Descrição:** Total de RFQs criadas no mês atual + comparação com mês anterior
- **Cor:** Verde (crescimento) ou Vermelho (queda)
- **Ícone:** Seta para cima/baixo
- **Cálculo:** `((Este mês - Mês anterior) / Mês anterior) * 100`

### **Código Relevante**

```php
// Verifica se usuário pode ver tudo
$canSeeAll = $user->roles()->where('can_see_all', true)->exists();

// Query automática com ownership
$query = Order::query(); // ClientOwnershipScope já aplicado!

// RFQs ativas
$activeRfqs = (clone $query)
    ->whereIn('status', ['draft', 'pending', 'sent', 'quoted'])
    ->count();
```

---

## 🛒 Widget 2: Purchase Order Stats Widget

**Arquivo:** `app/Filament/Widgets/PurchaseOrderStatsWidget.php`

### **Métricas Exibidas**

#### **1. POs Pendentes**
- **Descrição:** POs em Draft + Aguardando Aprovação
- **Cor:** Amarelo (warning)
- **Ícone:** Relógio
- **Status incluídos:** `draft`, `pending_approval`

#### **2. POs Ativas**
- **Descrição:** POs aprovadas, enviadas, confirmadas
- **Cor:** Azul (info)
- **Gráfico:** Linha mostrando POs criadas nos últimos 7 dias
- **Ícone:** Seta circular (ciclo)
- **Status incluídos:** `approved`, `sent`, `confirmed`, `partially_received`

#### **3. Em Produção**
- **Descrição:** POs com produtos sendo fabricados
- **Cor:** Roxo (primary)
- **Ícone:** Chave inglesa
- **Status incluído:** `in_production`

#### **4. POs Atrasadas**
- **Descrição:** POs cuja data de entrega passou e ainda não foram recebidas
- **Cor:** Vermelho (se > 0) ou Verde (se = 0)
- **Ícone:** Triângulo de exclamação
- **Lógica:** `expected_delivery_date < now()` AND `actual_delivery_date IS NULL` AND status IN (`sent`, `confirmed`, `in_production`)

#### **5. Valor em Aberto**
- **Descrição:** Valor total de POs ativas em moeda base (R$)
- **Cor:** Verde (success)
- **Ícone:** Cifrão
- **Cálculo:** Soma de `total_base_currency` de POs ativas (convertido de centavos)

#### **6. POs Este Mês**
- **Descrição:** Total de POs criadas no mês atual
- **Cor:** Cinza
- **Ícone:** Carrinho de compras
- **Nota:** Mostra se são "Todas as POs" ou "Seus clientes"

### **Código Relevante**

```php
// Contagem por status
$statusCounts = (clone $query)
    ->select('status', DB::raw('count(*) as count'))
    ->groupBy('status')
    ->pluck('count', 'status')
    ->toArray();

// POs atrasadas
$overduePOs = (clone $query)
    ->where('expected_delivery_date', '<', now())
    ->whereNull('actual_delivery_date')
    ->whereIn('status', ['sent', 'confirmed', 'in_production'])
    ->count();

// Valor total (em centavos no banco, converter para reais)
$totalValueActive = (clone $query)
    ->whereIn('status', ['approved', 'sent', 'confirmed', 'in_production', 'partially_received'])
    ->sum(DB::raw('COALESCE(total_base_currency, 0)'));

$totalValueActive = $totalValueActive / 100; // Centavos → Reais
```

---

## 💰 Widget 3: Financial Overview Widget

**Arquivo:** `app/Filament/Widgets/FinancialOverviewWidget.php`

### **Métricas Exibidas**

#### **1. Contas a Receber**
- **Descrição:** Valor total de invoices pendentes de pagamento
- **Cor:** Verde (success)
- **Gráfico:** Linha mostrando invoices criadas nos últimos 7 dias
- **Ícone:** Seta para cima
- **Status incluídos:** `draft`, `sent`, `overdue`
- **Formato:** R$ 123.456,78

#### **2. Contas a Pagar**
- **Descrição:** Valor total de POs ativas (ainda não pagas/completadas)
- **Cor:** Vermelho (danger)
- **Ícone:** Seta para baixo
- **Status incluídos:** `approved`, `sent`, `confirmed`, `in_production`, `partially_received`
- **Formato:** R$ 123.456,78

#### **3. Fluxo de Caixa Projetado**
- **Descrição:** Diferença entre contas a receber e contas a pagar
- **Cor:** Verde (se >= 0) ou Amarelo (se < 0)
- **Ícone:** Check (positivo) ou Triângulo (negativo)
- **Fórmula:** `Contas a Receber - Contas a Pagar`
- **Formato:** R$ 123.456,78

#### **4. Invoices Vencidas**
- **Descrição:** Quantidade e valor de invoices vencidas
- **Cor:** Vermelho (se > 0) ou Verde (se = 0)
- **Ícone:** Círculo de exclamação
- **Status incluído:** `overdue`
- **Formato:** Quantidade + R$ valor

#### **5. Vencem em 30 Dias**
- **Descrição:** Invoices que vencerão nos próximos 30 dias
- **Cor:** Amarelo (se > 0) ou Cinza (se = 0)
- **Ícone:** Calendário
- **Lógica:** `due_date BETWEEN now() AND now() + 30 days` AND status = `sent`

#### **6. Vendas Este Mês**
- **Descrição:** Valor total de vendas no mês atual + comparação com mês anterior
- **Cor:** Verde (crescimento) ou Vermelho (queda)
- **Ícone:** Seta para cima/baixo
- **Cálculo:** Soma de `total_base_currency` de invoices do mês
- **Formato:** R$ 123.456,78 + % de variação

### **Código Relevante**

```php
// Contas a receber
$totalToReceive = SalesInvoice::query()
    ->whereIn('status', ['draft', 'sent', 'overdue'])
    ->sum(DB::raw('COALESCE(total_base_currency, 0)'));

$totalToReceive = $totalToReceive / 100; // Centavos → Reais

// Contas a pagar
$totalToPay = PurchaseOrder::query()
    ->whereIn('status', ['approved', 'sent', 'confirmed', 'in_production', 'partially_received'])
    ->sum(DB::raw('COALESCE(total_base_currency, 0)'));

$totalToPay = $totalToPay / 100; // Centavos → Reais

// Fluxo de caixa
$cashFlow = $totalToReceive - $totalToPay;

// Vendas do mês com trend
$thisMonthSales = SalesInvoice::query()
    ->whereYear('invoice_date', now()->year)
    ->whereMonth('invoice_date', now()->month)
    ->sum(DB::raw('COALESCE(total_base_currency, 0)')) / 100;

$salesTrend = $lastMonthSales > 0 
    ? round((($thisMonthSales - $lastMonthSales) / $lastMonthSales) * 100, 1)
    : 0;
```

---

## 🎨 Cores e Ícones

### **Cores Utilizadas**

| Cor | Código Filament | Uso |
|-----|----------------|-----|
| Verde | `success` | Métricas positivas, valores a receber, crescimento |
| Vermelho | `danger` | Alertas, valores a pagar, problemas |
| Amarelo | `warning` | Avisos, pendências, atenção necessária |
| Azul | `info` | Informações neutras, métricas ativas |
| Roxo | `primary` | Processos em andamento |
| Cinza | `gray` | Informações secundárias |

### **Ícones Heroicons**

Todos os ícones utilizam o prefixo `heroicon-o-` (outline):

- `document-text`: Documentos/RFQs
- `inbox-arrow-down`: Recebimentos
- `check-circle`: Sucesso/Aprovação
- `arrow-trending-up/down`: Tendências
- `clock`: Tempo/Pendências
- `arrow-path`: Ciclos/Processos
- `wrench-screwdriver`: Produção
- `exclamation-triangle`: Alertas
- `currency-dollar`: Valores monetários
- `shopping-cart`: Compras
- `calendar`: Datas/Prazos

---

## 📊 Gráficos (Charts)

### **Implementação**

Os widgets utilizam mini-gráficos de linha (sparkline) mostrando os últimos 7 dias:

```php
protected function getLastSevenDaysChart(): array
{
    $data = [];
    
    for ($i = 6; $i >= 0; $i--) {
        $date = now()->subDays($i)->toDateString();
        $count = Order::whereDate('created_at', $date)->count();
        $data[] = $count;
    }
    
    return $data;
}
```

### **Onde São Usados**

- **RFQ Stats Widget**: Stat "RFQs Ativas"
- **Purchase Order Stats Widget**: Stat "POs Ativas"
- **Financial Overview Widget**: Stat "Contas a Receber"

---

## 🔧 Configuração

### **Ordem de Exibição**

Os widgets são ordenados pela propriedade `$sort`:

```php
protected static ?int $sort = 1; // RFQ Stats
protected static ?int $sort = 2; // Purchase Order Stats
protected static ?int $sort = 3; // Financial Overview
```

### **Registro no Panel**

Os widgets são registrados em `app/Providers/Filament/AdminPanelProvider.php`:

```php
->widgets([
    AccountWidget::class,
    FilamentInfoWidget::class,
    RfqStatsWidget::class,
    PurchaseOrderStatsWidget::class,
    FinancialOverviewWidget::class,
])
```

---

## 🚀 Como Testar

### **1. Testar como Super Admin**

```bash
# Login como super_admin
# Deve ver TODAS as métricas (todos os clientes)
```

### **2. Testar como Usuário Regular**

```bash
# Login como usuário com can_see_all = false
# Deve ver apenas métricas dos clientes atribuídos a ele
```

### **3. Verificar Ownership**

```bash
php artisan tinker
```

```php
// Criar usuário de teste
$user = User::find(2); // ID do usuário

// Verificar se pode ver tudo
$canSeeAll = $user->roles()->where('can_see_all', true)->exists();
echo $canSeeAll ? "Pode ver tudo" : "Vê apenas seus clientes";

// Ver clientes atribuídos
$clients = Client::where('user_id', $user->id)->get();
echo "Clientes atribuídos: " . $clients->count();

// Ver RFQs visíveis
auth()->login($user);
$rfqs = Order::count();
echo "RFQs visíveis: {$rfqs}";
exit
```

---

## 📝 Notas Importantes

### **Valores Monetários**

⚠️ **IMPORTANTE:** Todos os valores monetários são armazenados em **centavos** no banco de dados.

```php
// Sempre dividir por 100 ao exibir
$totalValueActive = $query->sum('total_base_currency') / 100;

// Sempre multiplicar por 100 ao salvar
$model->total = $value * 100;
```

### **Moeda Base**

Os widgets utilizam `total_base_currency` que é o valor convertido para a moeda base do sistema (R$).

### **Performance**

- Queries otimizadas com `select()` e `groupBy()`
- Uso de `COALESCE()` para evitar NULL
- Clone de queries para evitar mutação
- Cache pode ser implementado futuramente se necessário

---

## 🎯 Próximas Melhorias

### **Funcionalidades Futuras**

1. **Cache de métricas** (refresh a cada 5 minutos)
2. **Filtros de período** (últimos 7/30/90 dias)
3. **Exportação de dados** (CSV/Excel)
4. **Gráficos avançados** (Chart.js completo)
5. **Comparação de períodos** (YoY, MoM)
6. **Drill-down** (clicar no widget para ver detalhes)
7. **Notificações** (alertas quando métricas críticas)

### **Widgets Adicionais Planejados**

- Top 5 Clientes (por valor de vendas)
- Top 5 Produtos (por quantidade vendida)
- Gráfico de Vendas (últimos 12 meses)
- Alertas de Estoque (produtos com estoque baixo)
- Performance de Fornecedores (on-time delivery)

---

## 📚 Referências

- [Filament Widgets Documentation](https://filamentphp.com/docs/3.x/widgets/stats-overview)
- [Heroicons](https://heroicons.com/)
- [Laravel Query Builder](https://laravel.com/docs/10.x/queries)
- [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission)

---

## ✅ Checklist de Implementação

- [x] Widget de RFQs Ativas
- [x] Widget de Purchase Orders por Status
- [x] Widget Financeiro
- [x] Ownership automático via ClientOwnershipScope
- [x] Gráficos de tendência (últimos 7 dias)
- [x] Formatação de valores monetários
- [x] Cores e ícones consistentes
- [x] Registro no AdminPanelProvider
- [x] Documentação completa

---

**Criado em:** 01/12/2025  
**Versão:** 1.0  
**Status:** ✅ Implementado
