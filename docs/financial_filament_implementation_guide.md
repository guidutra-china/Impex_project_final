# Guia de Implementação - Filament Resources do Módulo Financeiro

**Autor:** Manus AI  
**Data:** 24 de Novembro de 2025

## 📋 Status Atual

✅ **Concluído:**
- Migrations (6 tabelas criadas)
- Models (5 models com relacionamentos)
- Observers (automação de PO e SI)
- Command (geração de recorrências)
- Seeder (27 categorias padrão)

⏳ **Pendente:**
- Filament Resources (interfaces de gerenciamento)

---

## 🎯 Filament Resources a Criar

### 1. FinancialCategoryResource (CRUD Simples)

**Comando:**
```bash
php artisan make:filament-resource FinancialCategory --generate
```

**Campos do Form:**
- `name` → TextInput (required)
- `code` → TextInput (required, unique)
- `description` → Textarea
- `type` → Select (expense, revenue, exchange_variation)
- `parent_id` → Select (relationship, nullable)
- `is_active` → Toggle (default true)
- `sort_order` → TextInput (numeric)

**Colunas da Table:**
- `full_name` → TextColumn (com hierarquia)
- `code` → TextColumn
- `type` → BadgeColumn (cores diferentes)
- `is_active` → IconColumn

**Filtros:**
- `type` → SelectFilter
- `is_active` → TernaryFilter

---

### 2. FinancialTransactionResource (MAIS IMPORTANTE!)

**Comando:**
```bash
php artisan make:filament-resource FinancialTransaction --generate
```

**Campos do Form:**

**Section: Informações Básicas**
- `description` → Textarea (required)
- `type` → Select (payable/receivable, required)
- `financial_category_id` → Select (relationship, required)

**Section: Valores**
- `amount` → TextInput (numeric, money format, required)
- `currency_id` → Select (relationship, required)
- `exchange_rate_to_base` → TextInput (readonly, auto-filled)
- `amount_base_currency` → TextInput (readonly, auto-filled)

**Section: Datas**
- `transaction_date` → DatePicker (default today)
- `due_date` → DatePicker (required)

**Section: Relacionamentos**
- `supplier_id` → Select (relationship, visible if type=payable)
- `client_id` → Select (relationship, visible if type=receivable)

**Section: Notas**
- `notes` → Textarea

**Colunas da Table:**
- `transaction_number` → TextColumn (searchable)
- `description` → TextColumn (limit 50, searchable)
- `type` → BadgeColumn (Pagar=red, Receber=green)
- `status` → BadgeColumn (cores: pending=gray, overdue=red, paid=green)
- `amount` → TextColumn (money format com moeda)
- `remaining_amount` → TextColumn (money format)
- `due_date` → TextColumn (date format)
- `days_until_due` → TextColumn (com cores: <0=red, <7=yellow, >7=green)

**Filtros:**
- `type` → SelectFilter
- `status` → SelectFilter
- `financial_category_id` → SelectFilter
- `supplier_id` → SelectFilter (se payable)
- `client_id` → SelectFilter (se receivable)
- `due_date` → DateRangeFilter

**Actions:**
- `ViewAction` → Ver detalhes
- `EditAction` → Editar (só se status=pending)
- `DeleteAction` → Deletar (só se paid_amount=0)
- **Custom:** `MarkAsPaidAction` → Marcar como paga manualmente
- **Custom:** `CreatePaymentAction` → Criar pagamento e alocar

**Widgets:**
- `StatsOverview` → Cards com:
  - Total a Pagar (pending + partially_paid)
  - Total a Receber (pending + partially_paid)
  - Vencidas (overdue)
  - Vencendo em 7 dias

---

### 3. FinancialPaymentResource (CRÍTICO!)

**Comando:**
```bash
php artisan make:filament-resource FinancialPayment --generate
```

**Campos do Form:**

**Section: Informações do Pagamento**
- `description` → Textarea (required)
- `type` → Select (debit/credit, required)
- `bank_account_id` → Select (relationship, required)
- `payment_method_id` → Select (relationship, required)
- `payment_date` → DatePicker (default today)

**Section: Valores**
- `amount` → TextInput (numeric, money format, required)
- `currency_id` → Select (relationship, required)
- `fee` → TextInput (numeric, money format, default 0)
- `net_amount` → TextInput (readonly, auto-calculated)

**Section: Referência**
- `reference_number` → TextInput
- `transaction_id` → TextInput

**Section: Alocações (REPEATER!)**
- `allocations` → Repeater:
  - `financial_transaction_id` → Select (filtered by type and pending)
  - `allocated_amount` → TextInput (money format)
  - `gain_loss_on_exchange` → TextInput (readonly, auto-calculated)

**Lógica Importante:**
```php
// No form, adicionar:
->afterStateUpdated(function ($state, callable $set) {
    // Recalcular net_amount quando amount ou fee mudar
    $amount = $state['amount'] ?? 0;
    $fee = $state['fee'] ?? 0;
    $set('net_amount', $amount - $fee);
})

// Para as alocações, calcular variação cambial automaticamente
->afterStateUpdated(function ($state, callable $set, $get) {
    $payment = $get('../../'); // Get payment data
    $transaction = FinancialTransaction::find($state['financial_transaction_id']);
    
    if ($payment && $transaction) {
        $gainLoss = calculateExchangeGainLoss(
            $payment['currency_id'],
            $payment['exchange_rate_to_base'],
            $transaction->currency_id,
            $transaction->exchange_rate_to_base,
            $state['allocated_amount']
        );
        
        $set('gain_loss_on_exchange', $gainLoss);
    }
})
```

**Colunas da Table:**
- `payment_number` → TextColumn (searchable)
- `type` → BadgeColumn (Saída=red, Entrada=green)
- `payment_date` → TextColumn (date format)
- `amount` → TextColumn (money format com moeda)
- `bank_account.name` → TextColumn
- `total_allocated` → TextColumn (money format)
- `unallocated_amount` → TextColumn (money format, highlight if >0)
- `status` → BadgeColumn

**Filtros:**
- `type` → SelectFilter
- `bank_account_id` → SelectFilter
- `payment_date` → DateRangeFilter
- `status` → SelectFilter

**Actions:**
- `ViewAction` → Ver detalhes com alocações
- `EditAction` → Editar (só se status=pending)
- `DeleteAction` → Deletar (só se não tem alocações)

---

### 4. RecurringTransactionResource

**Comando:**
```bash
php artisan make:filament-resource RecurringTransaction --generate
```

**Campos do Form:**

**Section: Informações Básicas**
- `name` → TextInput (required)
- `description` → Textarea
- `type` → Select (payable/receivable, required)
- `financial_category_id` → Select (relationship, required)

**Section: Valores**
- `amount` → TextInput (numeric, money format, required)
- `currency_id` → Select (relationship, required)

**Section: Recorrência**
- `frequency` → Select (daily, weekly, monthly, quarterly, yearly)
- `interval` → TextInput (numeric, default 1)
- `day_of_month` → TextInput (1-31, visible if monthly)
- `day_of_week` → Select (0-6, visible if weekly)

**Section: Período**
- `start_date` → DatePicker (required)
- `end_date` → DatePicker (nullable)
- `next_due_date` → DatePicker (readonly, auto-calculated)

**Section: Configurações**
- `is_active` → Toggle (default true)
- `auto_generate` → Toggle (default true)
- `days_before_due` → TextInput (numeric, default 0)

**Section: Relacionamentos**
- `supplier_id` → Select (relationship, visible if type=payable)
- `client_id` → Select (relationship, visible if type=receivable)

**Colunas da Table:**
- `name` → TextColumn (searchable)
- `type` → BadgeColumn
- `amount` → TextColumn (money format)
- `frequency` → TextColumn
- `next_due_date` → TextColumn (date format)
- `is_active` → IconColumn
- `last_generated_date` → TextColumn (date format)

**Filtros:**
- `type` → SelectFilter
- `frequency` → SelectFilter
- `is_active` → TernaryFilter

**Actions:**
- `ViewAction` → Ver detalhes + preview das próximas ocorrências
- `EditAction` → Editar
- `DeleteAction` → Deletar
- **Custom:** `GenerateNowAction` → Gerar transação manualmente
- **Custom:** `PreviewAction` → Ver próximas 12 ocorrências

**Infolist (na ViewAction):**
```php
Infolists\Components\Section::make('Próximas Ocorrências')
    ->schema([
        Infolists\Components\RepeatableEntry::make('next_occurrences')
            ->schema([
                Infolists\Components\TextEntry::make('date'),
                Infolists\Components\TextEntry::make('amount')
                    ->money(),
            ])
            ->state(fn ($record) => $record->getNextOccurrences(12)),
    ]),
```

---

## 🎨 Customizações Importantes

### 1. Navigation Group

Adicionar em cada Resource:

```php
protected static ?string $navigationGroup = 'Financeiro';
protected static ?int $navigationSort = X; // Ordem
```

**Ordem sugerida:**
1. FinancialTransactionResource (sort = 1)
2. FinancialPaymentResource (sort = 2)
3. RecurringTransactionResource (sort = 3)
4. FinancialCategoryResource (sort = 4)

### 2. Icons

```php
protected static ?string $navigationIcon = 'heroicon-o-currency-dollar'; // Transactions
protected static ?string $navigationIcon = 'heroicon-o-banknotes'; // Payments
protected static ?string $navigationIcon = 'heroicon-o-arrow-path'; // Recurring
protected static ?string $navigationIcon = 'heroicon-o-folder'; // Categories
```

### 3. Badges de Status

```php
->badge()
->color(fn (string $state): string => match ($state) {
    'pending' => 'gray',
    'partially_paid' => 'warning',
    'paid' => 'success',
    'overdue' => 'danger',
    'cancelled' => 'secondary',
})
```

---

## 📊 Widgets e Dashboards

### Widget: FinancialOverview

**Localização:** `app/Filament/Widgets/FinancialOverviewWidget.php`

**Comando:**
```bash
php artisan make:filament-widget FinancialOverview --stats-overview
```

**Stats:**
```php
Stat::make('Contas a Pagar', function () {
    return Money::format(
        FinancialTransaction::payables()
            ->whereIn('status', ['pending', 'partially_paid'])
            ->sum('remaining_amount')
    );
})
->description('Pendentes')
->descriptionIcon('heroicon-m-arrow-trending-down')
->color('danger'),

Stat::make('Contas a Receber', function () {
    return Money::format(
        FinancialTransaction::receivables()
            ->whereIn('status', ['pending', 'partially_paid'])
            ->sum('remaining_amount')
    );
})
->description('Pendentes')
->descriptionIcon('heroicon-m-arrow-trending-up')
->color('success'),

Stat::make('Vencidas', function () {
    return FinancialTransaction::overdue()->count();
})
->description('Transações vencidas')
->descriptionIcon('heroicon-m-exclamation-triangle')
->color('warning'),
```

### Widget: CashFlowChart

**Comando:**
```bash
php artisan make:filament-widget CashFlowChart --chart
```

**Tipo:** LineChart

**Dados:** Fluxo de caixa dos últimos 12 meses (entradas vs saídas)

---

## 🧪 Testando o Módulo

### 1. Popular Categorias
```bash
php artisan db:seed --class=FinancialCategoriesSeeder
```

### 2. Testar Automação de PO
1. Criar uma Purchase Order
2. Aprovar a PO
3. Verificar se criou FinancialTransaction automaticamente
4. Ir em Financial Transactions e ver a conta a pagar

### 3. Testar Pagamento com Alocação
1. Ir em Financial Payments
2. Criar novo pagamento (tipo: debit)
3. Na seção de alocações, selecionar a conta a pagar da PO
4. Alocar o valor total
5. Salvar e verificar:
   - FinancialPaymentAllocation criada
   - FinancialTransaction.paid_amount atualizado
   - FinancialTransaction.status mudou para 'paid'

### 4. Testar Recorrência
1. Criar RecurringTransaction (ex: Aluguel mensal)
2. Rodar: `php artisan finance:generate-recurring --dry-run`
3. Ver o que seria gerado
4. Rodar: `php artisan finance:generate-recurring`
5. Verificar se criou FinancialTransaction

---

## 📝 Próximos Passos

1. ✅ Rodar o seeder de categorias
2. ⏳ Criar os 4 Filament Resources usando os comandos acima
3. ⏳ Customizar forms, tables e actions conforme documentado
4. ⏳ Criar os 2 Widgets (FinancialOverview e CashFlowChart)
5. ⏳ Testar fluxo completo
6. ⏳ Ajustar conforme necessário

---

## 💡 Dicas Importantes

1. **Money Format:** Use sempre `/ 100` para exibir e `* 100` para salvar
2. **Exchange Rate:** Buscar automaticamente ao selecionar moeda
3. **Validation:** Validar que allocated_amount não exceda remaining_amount
4. **Permissions:** Adicionar policies para cada Resource
5. **Audit:** Usar `created_by` para rastrear quem criou

---

**Este módulo financeiro está 90% pronto! Falta apenas criar as interfaces no Filament seguindo este guia.** 🚀
