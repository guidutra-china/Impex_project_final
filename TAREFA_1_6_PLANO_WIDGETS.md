# Tarefa 1.6: Atualização de Filament Widgets para Usar Repositories

## Objetivo

Refatorar todos os Filament Widgets para injetar e utilizar os Repositories, garantindo que todas as operações de dados sejam centralizadas e reutilizáveis.

## Status: 📋 PLANEJADO

---

## Widgets Encontrados

### 1. **ProjectExpensesWidget.php** 🔴 Alta Prioridade

**Localização:** `app/Filament/Widgets/ProjectExpensesWidget.php`

**Tipo:** TableWidget

**Funcionalidade:**
- Exibe transações financeiras (despesas) de um projeto
- Filtra por tipo 'payable'
- Mostra totais de despesas e margem real
- Ações: View, Delete

**Dados Atuais:**
```php
FinancialTransaction::query()
    ->where('project_id', $this->record->id)
    ->where('type', 'payable')
    ->with(['category', 'currency', 'creator'])
```

**Refatoração Necessária:**
- Usar `FinancialTransactionRepository::findByProject()`
- Usar `FinancialTransactionRepository::findByType()`
- Implementar método `getProjectExpenses()` no repository

**Prioridade:** 🔴 Alta (Usado em EditOrder)

---

### 2. **FinancialOverviewWidget.php** 🔴 Alta Prioridade

**Localização:** `app/Filament/Widgets/FinancialOverviewWidget.php`

**Tipo:** StatsOverviewWidget

**Funcionalidade:**
- Exibe estatísticas financeiras gerais
- Contas a receber (Sales Invoices)
- Contas a pagar (Purchase Orders)
- Fluxo de caixa projetado
- Vendas do mês

**Dados Atuais:**
```php
SalesInvoice::query()->whereIn('status', ['draft', 'sent', 'overdue'])->count()
PurchaseOrder::query()->whereIn('status', ['approved', 'sent', 'confirmed'])->count()
SalesInvoice::query()->whereYear('invoice_date', now()->year)->whereMonth('invoice_date', now()->month)->sum()
```

**Refatoração Necessária:**
- Criar `SalesInvoiceRepository`
- Criar `PurchaseOrderRepository`
- Implementar métodos para cálculos financeiros
- Usar repositories para todas as queries

**Prioridade:** 🔴 Alta (Dashboard principal)

---

### 3. **RelatedDocumentsWidget.php** 🟡 Média Prioridade

**Localização:** `app/Filament/Widgets/RelatedDocumentsWidget.php`

**Tipo:** TableWidget

**Funcionalidade:**
- Exibe documentos relacionados a um registro
- Filtra por tipo de entidade (transactable)

**Refatoração Necessária:**
- Criar `DocumentRepository`
- Usar repository para buscar documentos

**Prioridade:** 🟡 Média

---

### 4. **RfqStatsWidget.php** 🟡 Média Prioridade

**Localização:** `app/Filament/Widgets/RfqStatsWidget.php`

**Tipo:** StatsOverviewWidget

**Funcionalidade:**
- Exibe estatísticas de RFQs
- Contagem por status
- Totais de valores

**Refatoração Necessária:**
- Criar `RFQRepository`
- Usar repository para cálculos

**Prioridade:** 🟡 Média

---

### 5. **PurchaseOrderStatsWidget.php** 🟡 Média Prioridade

**Localização:** `app/Filament/Widgets/PurchaseOrderStatsWidget.php`

**Tipo:** StatsOverviewWidget

**Funcionalidade:**
- Exibe estatísticas de Purchase Orders
- Contagem por status
- Totais de valores

**Refatoração Necessária:**
- Usar `PurchaseOrderRepository`
- Implementar métodos para cálculos

**Prioridade:** 🟡 Média

---

### 6. **CalendarWidget.php** 🟢 Baixa Prioridade

**Localização:** `app/Filament/Widgets/CalendarWidget.php`

**Tipo:** Custom Widget

**Funcionalidade:**
- Exibe calendário com eventos

**Refatoração Necessária:**
- Usar `EventRepository`

**Prioridade:** 🟢 Baixa

---

## Repositories Necessários

### 1. **FinancialTransactionRepository** ✅ Já Existe
- Métodos necessários já implementados
- Apenas ajustar queries nos widgets

### 2. **SalesInvoiceRepository** 🆕 Novo
- `findByStatus(string $status)`
- `getTotalByStatus(string $status)`
- `findByDateRange()`
- `countByStatus(string $status)`
- `getThisMonthSales()`
- `getLastMonthSales()`

### 3. **PurchaseOrderRepository** 🆕 Novo
- `findByStatus(string $status)`
- `getTotalByStatus(string $status)`
- `countByStatus(string $status)`

### 4. **DocumentRepository** 🆕 Novo
- `findByTransactable(string $type, int $id)`
- `findByType(string $type)`

### 5. **RFQRepository** 🆕 Novo
- `countByStatus(string $status)`
- `getTotalByStatus(string $status)`

### 6. **EventRepository** 🆕 Novo
- `findByDateRange()`
- `findByType(string $type)`

---

## Padrão de Refatoração para Widgets

### Antes (sem Repository)

```php
class ProjectExpensesWidget extends BaseWidget
{
    public function table(Table $table): Table
    {
        return $table->query(
            FinancialTransaction::query()
                ->where('project_id', $this->record->id)
                ->where('type', 'payable')
                ->with(['category', 'currency', 'creator'])
        );
    }
}
```

### Depois (com Repository)

```php
class ProjectExpensesWidget extends BaseWidget
{
    protected FinancialTransactionRepository $repository;

    public function __construct()
    {
        parent::__construct();
        $this->repository = app(FinancialTransactionRepository::class);
    }

    public function table(Table $table): Table
    {
        return $table->query(
            $this->repository->getProjectExpensesQuery($this->record->id)
        );
    }
}
```

---

## Fases de Implementação

### Fase 1: Repositories Essenciais

1. **SalesInvoiceRepository**
   - Métodos para cálculos financeiros
   - Métodos para filtrar por status, período

2. **PurchaseOrderRepository**
   - Métodos para cálculos financeiros
   - Métodos para filtrar por status

### Fase 2: Refatoração de Widgets de Alta Prioridade

1. **ProjectExpensesWidget**
   - Usar `FinancialTransactionRepository`

2. **FinancialOverviewWidget**
   - Usar `SalesInvoiceRepository`
   - Usar `PurchaseOrderRepository`

### Fase 3: Repositories Adicionais

1. **DocumentRepository**
2. **RFQRepository**
3. **EventRepository**

### Fase 4: Refatoração de Widgets de Média/Baixa Prioridade

1. **RelatedDocumentsWidget**
2. **RfqStatsWidget**
3. **PurchaseOrderStatsWidget**
4. **CalendarWidget**

---

## Checklist de Implementação

### Para Cada Widget

- [ ] Adicionar injeção de Repository
- [ ] Refatorar queries para usar repository
- [ ] Adicionar métodos helper no repository
- [ ] Preservar funcionalidades existentes
- [ ] Testar com dados reais
- [ ] Documentar padrão utilizado

### Validação Geral

- [ ] Todos os Widgets usam Repositories
- [ ] Padrão consistente em todos os Widgets
- [ ] Testes passando (84+ testes)
- [ ] Funcionalidades preservadas
- [ ] Performance mantida ou melhorada
- [ ] Código documentado em português

---

## Exemplo de Refatoração Completa

### ProjectExpensesWidget

**Antes:**
```php
public function table(Table $table): Table
{
    if (!$this->record instanceof Order) {
        return $table->query(FinancialTransaction::query()->whereRaw('1 = 0'));
    }

    $totalExpenses = $this->record->total_project_expenses_dollars ?? 0;
    
    return $table
        ->query(
            FinancialTransaction::query()
                ->where('project_id', $this->record->id)
                ->where('type', 'payable')
                ->with(['category', 'currency', 'creator'])
                ->orderBy('transaction_date', 'desc')
        );
}
```

**Depois:**
```php
protected FinancialTransactionRepository $repository;

public function __construct()
{
    parent::__construct();
    $this->repository = app(FinancialTransactionRepository::class);
}

public function table(Table $table): Table
{
    if (!$this->record instanceof Order) {
        return $table->query(FinancialTransaction::query()->whereRaw('1 = 0'));
    }

    $totalExpenses = $this->record->total_project_expenses_dollars ?? 0;
    
    return $table
        ->query(
            $this->repository->getProjectExpensesQuery($this->record->id)
        );
}

// No Repository:
public function getProjectExpensesQuery(int $projectId): Builder
{
    return $this->model
        ->where('project_id', $projectId)
        ->where('type', 'payable')
        ->with(['category', 'currency', 'creator'])
        ->orderBy('transaction_date', 'desc');
}
```

---

## Métricas de Sucesso

| Métrica | Meta | Status |
|---------|------|--------|
| Widgets Refatorados | 6/6 | ⏳ Pendente |
| Repositories Criados | 6 | ⏳ Pendente |
| Queries via Repository | 100% | ⏳ Pendente |
| Testes Passando | 84+ | ⏳ Pendente |
| Cobertura de Testes | >80% | ⏳ Pendente |
| Documentação | 100% | ⏳ Pendente |

---

## Recomendações Profissionais

### 1. **Criar Métodos Helper no Repository**

Para queries complexas, criar métodos específicos:

```php
// Em FinancialTransactionRepository
public function getProjectExpensesQuery(int $projectId): Builder
{
    return $this->model
        ->where('project_id', $projectId)
        ->where('type', 'payable')
        ->with(['category', 'currency', 'creator'])
        ->orderBy('transaction_date', 'desc');
}
```

### 2. **Implementar Caching para Widgets de Dashboard**

Para melhorar performance:

```php
public function getFinancialStats(): array
{
    return Cache::remember('financial_stats', 3600, function () {
        return [
            'receivable' => $this->getTotalReceivable(),
            'payable' => $this->getTotalPayable(),
            'cashflow' => $this->getCashFlow(),
        ];
    });
}
```

### 3. **Adicionar Logging para Debugging**

```php
\Log::debug('Widget query executed', [
    'widget' => static::class,
    'project_id' => $projectId,
    'timestamp' => now(),
]);
```

### 4. **Criar Testes para Widgets**

```php
test('ProjectExpensesWidget displays expenses correctly', function () {
    $order = Order::factory()->create();
    $expenses = FinancialTransaction::factory(3)->create([
        'project_id' => $order->id,
        'type' => 'payable',
    ]);
    
    $widget = new ProjectExpensesWidget();
    $widget->record = $order;
    
    // Assert widget displays expenses
});
```

---

## Próximos Passos

1. **Criar SalesInvoiceRepository**
2. **Criar PurchaseOrderRepository**
3. **Refatorar ProjectExpensesWidget**
4. **Refatorar FinancialOverviewWidget**
5. **Criar DocumentRepository**
6. **Criar RFQRepository**
7. **Refatorar RelatedDocumentsWidget**
8. **Refatorar RfqStatsWidget**
9. **Refatorar PurchaseOrderStatsWidget**
10. **Criar EventRepository**
11. **Refatorar CalendarWidget**
12. **Testes Completos**

---

## Estimativa de Esforço

- **Análise**: 1-2 horas
- **Implementação de Repositories**: 6-8 horas
- **Refatoração de Widgets**: 4-6 horas
- **Testes**: 3-4 horas
- **Documentação**: 1-2 horas
- **Total**: 15-22 horas

---

**Documento criado em:** 04 de Dezembro de 2025
**Versão:** 1.0
**Status:** Pronto para Implementação
