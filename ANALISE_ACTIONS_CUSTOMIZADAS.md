# Análise de Actions Customizadas no Projeto

## Objetivo

Identificar e documentar todas as Actions customizadas que precisam ser refatoradas para usar Repositories na Tarefa 1.5.

## Actions Customizadas Encontradas

### 1. **EditOrder.php** - Action: `add_project_expense`

**Localização:** `app/Filament/Resources/Orders/Pages/EditOrder.php` (linhas 35-158)

**Tipo:** Custom Action com Formulário

**Funcionalidade:**
- Adiciona despesas de projeto a um RFQ
- Formulário com campos: categoria, moeda, taxa de câmbio, valor, datas, descrição
- Cria FinancialTransaction vinculada ao Order

**Lógica Atual:**
```php
FinancialTransaction::create([
    'project_id' => $order->id,
    'transactable_id' => $order->id,
    'transactable_type' => get_class($order),
    // ... mais campos
]);
```

**Refatoração Necessária:**
- Criar `FinancialTransactionRepository`
- Usar `$financialTransactionRepository->create()` em vez de `FinancialTransaction::create()`
- Validar dados via repository
- Adicionar logging de operações

**Prioridade:** 🔴 Alta (Operação crítica de dados)

---

### 2. **EditProformaInvoice.php** - Multiple Actions

**Localização:** `app/Filament/Resources/ProformaInvoice/Pages/EditProformaInvoice.php` (linhas 21-96)

**Actions Customizadas:**

#### a) `approve`
- Aprova uma Proforma Invoice
- Atualiza status para 'approved'
- Registra timestamp e usuário

#### b) `reject`
- Rejeita uma Proforma Invoice
- Requer motivo via formulário
- Atualiza status para 'rejected'

#### c) `mark_sent`
- Marca como enviada
- Atualiza status para 'sent'
- Registra timestamp

#### d) `mark_deposit_received`
- Marca depósito como recebido
- Formulário com método e referência de pagamento
- Atualiza múltiplos campos

**Lógica Atual:**
```php
$record->update([
    'status' => 'approved',
    'approved_at' => now(),
    'approved_by' => auth()->id(),
]);
```

**Refatoração Necessária:**
- Criar `ProformaInvoiceRepository`
- Usar `$proformaRepository->updateStatus($id, $status, $data)`
- Implementar métodos específicos para cada transição de estado
- Adicionar validação de regras de negócio

**Prioridade:** 🔴 Alta (Operações críticas de workflow)

---

### 3. **EditSupplierQuote.php** - Multiple Actions

**Localização:** `app/Filament/Resources/SupplierQuotes/Pages/EditSupplierQuote.php` (linhas 20-95)

**Actions Customizadas:**

#### a) `import_excel`
- Importa dados de Excel
- Usa `SupplierQuoteImportService`
- Valida e processa arquivo
- Limpa arquivo temporário

#### b) `recalculate`
- Recalcula todos os valores
- Chama `lockExchangeRate()` e `calculateCommission()`

**Lógica Atual:**
```php
$result = $importService->importFromExcel($this->record, $filePath);
$this->record->lockExchangeRate();
$this->record->calculateCommission();
```

**Refatoração Necessária:**
- Criar `SupplierQuoteRepository`
- Usar repository para atualizar dados pós-importação
- Implementar método `recalculateAll()` no repository
- Adicionar transações para garantir consistência

**Prioridade:** 🔴 Alta (Operações críticas de dados)

---

### 4. **RecurringTransactions/ViewRecurringTransaction.php** - Action: `generate_now`

**Localização:** `app/Filament/Resources/RecurringTransactions/Pages/ViewRecurringTransaction.php` (linhas 21-58)

**Tipo:** Custom Action com Lógica Complexa

**Funcionalidade:**
- Gera próxima transação recorrente
- Valida se recorrência está ativa
- Chama `generateTransaction()` no modelo
- Atualiza próxima data de vencimento

**Lógica Atual:**
```php
$transaction = $recurring->generateTransaction();
```

**Refatoração Necessária:**
- Criar `RecurringTransactionRepository`
- Implementar `generateNextTransaction()` no repository
- Adicionar validações de regras de negócio
- Registrar auditoria da operação

**Prioridade:** 🟡 Média (Operação importante mas não crítica)

---

### 5. **Currencies/ListCurrencies.php** - Action: `update_rates`

**Localização:** `app/Filament/Resources/Currencies/Pages/ListCurrencies.php`

**Tipo:** Custom Action para Atualização em Massa

**Funcionalidade:**
- Atualiza taxas de câmbio
- Pode ser acionada de forma manual

**Refatoração Necessária:**
- Criar `CurrencyRepository` ou `ExchangeRateRepository`
- Implementar método `updateExchangeRates()`
- Adicionar validação de dados

**Prioridade:** 🟡 Média

---

### 6. **CompanySettings/ManageCompanySettings.php** - Action: `save`

**Localização:** `app/Filament/Resources/CompanySettingsResource/Pages/ManageCompanySettings.php`

**Tipo:** Custom Save Action

**Funcionalidade:**
- Salva configurações da empresa
- Pode ter lógica customizada

**Refatoração Necessária:**
- Criar `CompanySettingRepository`
- Usar repository para persistir dados

**Prioridade:** 🟢 Baixa

---

### 7. **ExchangeRates/Table** - Action: `duplicate`

**Localização:** `app/Filament/Resources/ExchangeRates/Tables/ExchangeRatesTable.php`

**Tipo:** Table Action

**Funcionalidade:**
- Duplica uma taxa de câmbio

**Refatoração Necessária:**
- Criar `ExchangeRateRepository`
- Implementar método `duplicate($id)`

**Prioridade:** 🟢 Baixa

---

### 8. **EventResource** - Action: `complete`

**Localização:** `app/Filament/Resources/EventResource/EventResource.php`

**Tipo:** Custom Action

**Funcionalidade:**
- Marca evento como completo

**Refatoração Necessária:**
- Criar `EventRepository`
- Implementar método `markAsComplete($id)`

**Prioridade:** 🟢 Baixa

---

## Repositories Necessários

### 1. **FinancialTransactionRepository** 🔴 Alta Prioridade
- `create(array $data)`
- `update(int $id, array $data)`
- `findByProject(int $projectId)`
- `getByCategory(int $categoryId)`

### 2. **ProformaInvoiceRepository** 🔴 Alta Prioridade
- `updateStatus(int $id, string $status, array $data)`
- `approve(int $id, int $approvedBy)`
- `reject(int $id, string $reason)`
- `markAsSent(int $id)`
- `markDepositReceived(int $id, array $data)`

### 3. **SupplierQuoteRepository** 🔴 Alta Prioridade
- `create(array $data)`
- `update(int $id, array $data)`
- `recalculateAll(int $id)`
- `lockExchangeRate(int $id)`
- `calculateCommission(int $id)`

### 4. **RecurringTransactionRepository** 🟡 Média Prioridade
- `generateNextTransaction(int $id)`
- `updateNextDueDate(int $id)`
- `activate(int $id)`
- `deactivate(int $id)`

### 5. **CurrencyRepository** 🟡 Média Prioridade
- `updateExchangeRates()`
- `getConversionRate(int $fromId, int $toId)`

### 6. **CompanySettingRepository** 🟢 Baixa Prioridade
- `update(array $data)`
- `get(string $key)`

### 7. **ExchangeRateRepository** 🟢 Baixa Prioridade
- `duplicate(int $id)`
- `create(array $data)`

### 8. **EventRepository** 🟢 Baixa Prioridade
- `markAsComplete(int $id)`

---

## Plano de Implementação

### Fase 1: Repositories de Alta Prioridade
1. Criar `FinancialTransactionRepository`
2. Criar `ProformaInvoiceRepository`
3. Criar `SupplierQuoteRepository`
4. Refatorar Actions correspondentes

### Fase 2: Repositories de Média Prioridade
1. Criar `RecurringTransactionRepository`
2. Criar `CurrencyRepository`
3. Refatorar Actions correspondentes

### Fase 3: Repositories de Baixa Prioridade
1. Criar `CompanySettingRepository`
2. Criar `ExchangeRateRepository`
3. Criar `EventRepository`
4. Refatorar Actions correspondentes

---

## Padrão de Refatoração

### Antes (sem Repository)
```php
Action::make('approve')
    ->action(function ($record) {
        $record->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);
        
        $this->notify('success', 'Approved');
    }),
```

### Depois (com Repository)
```php
Action::make('approve')
    ->action(function ($record) {
        $this->handleApprove($record);
    }),

protected function handleApprove($record): void
{
    try {
        $this->proformaRepository->approve(
            $record->id,
            auth()->id()
        );
        
        Notification::make()
            ->success()
            ->title('Proforma Invoice approved')
            ->send();
    } catch (\Exception $e) {
        Notification::make()
            ->danger()
            ->title('Error')
            ->body($e->getMessage())
            ->send();
    }
}
```

---

## Métricas

| Métrica | Valor |
|---------|-------|
| Actions Customizadas Encontradas | 8 |
| Repositories Necessários | 8 |
| Alta Prioridade | 3 |
| Média Prioridade | 2 |
| Baixa Prioridade | 3 |
| Estimativa de Horas | 20-25 |

---

## Próximos Passos

1. ✅ Análise completa (este documento)
2. ⏳ Criar Repositories de Alta Prioridade
3. ⏳ Refatorar Actions de Alta Prioridade
4. ⏳ Testar e validar
5. ⏳ Criar Repositories de Média Prioridade
6. ⏳ Refatorar Actions de Média Prioridade
7. ⏳ Criar Repositories de Baixa Prioridade
8. ⏳ Refatorar Actions de Baixa Prioridade
9. ⏳ Testes completos
10. ⏳ Documentação final

---

**Documento criado em:** 04 de Dezembro de 2025
**Versão:** 1.0
**Status:** Análise Completa
