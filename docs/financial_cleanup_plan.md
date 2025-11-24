# Plano de Limpeza e Implementação do Módulo Financeiro

**Data:** 24 de Novembro de 2025

## 1. Arquivos e Tabelas a Remover

### 1.1. Migrations a Remover

| Migration | Tabela | Motivo |
|-----------|--------|--------|
| `2025_11_22_110000_create_purchase_invoices_table.php` | `purchase_invoices` | Não usaremos Purchase Invoices - obrigação vem da PO |
| `2025_11_22_110001_create_purchase_invoice_items_table.php` | `purchase_invoice_items` | Dependente de purchase_invoices |
| `2025_11_18_000005_create_supplier_payments_table.php` | `supplier_payments` | Será substituído por `financial_payments` |
| `2025_11_18_000006_create_supplier_payment_allocations_table.php` | `supplier_payment_allocations` | Será substituído por `financial_payment_allocations` |
| `2025_11_18_000007_create_customer_receipts_table.php` | `customer_receipts` | Será substituído por `financial_payments` |
| `2025_11_18_000008_create_customer_receipt_allocations_table.php` | `customer_receipt_allocations` | Será substituído por `financial_payment_allocations` |

### 1.2. Models a Remover

| Model | Arquivo |
|-------|---------|
| `PurchaseInvoice` | `/app/Models/PurchaseInvoice.php` |
| `PurchaseInvoiceItem` | `/app/Models/PurchaseInvoiceItem.php` |
| `SupplierPayment` | `/app/Models/SupplierPayment.php` |
| `SupplierPaymentAllocation` | `/app/Models/SupplierPaymentAllocation.php` |
| `CustomerReceipt` | `/app/Models/CustomerReceipt.php` |
| `CustomerReceiptAllocation` | `/app/Models/CustomerReceiptAllocation.php` |

### 1.3. Filament Resources a Remover (se existirem)

- `PurchaseInvoiceResource`
- `SupplierPaymentResource`
- `CustomerReceiptResource`

---

## 2. Novas Migrations a Criar

### 2.1. Migration: Drop Old Tables

```
2025_11_24_000001_drop_old_financial_tables.php
```

Esta migration removerá todas as tabelas antigas listadas acima.

### 2.2. Migration: Financial Categories

```
2025_11_24_000002_create_financial_categories_table.php
```

Tabela para categorizar custos e receitas (DRE).

### 2.3. Migration: Financial Transactions

```
2025_11_24_000003_create_financial_transactions_table.php
```

Tabela central de contas a pagar e a receber.

### 2.4. Migration: Financial Payments

```
2025_11_24_000004_create_financial_payments_table.php
```

Tabela de pagamentos e recebimentos reais.

### 2.5. Migration: Financial Payment Allocations

```
2025_11_24_000005_create_financial_payment_allocations_table.php
```

Tabela pivot para alocar pagamentos a transações (M-para-N).

### 2.6. Migration: Recurring Transactions

```
2025_11_24_000006_create_recurring_transactions_table.php
```

Tabela para custos/receitas recorrentes.

---

## 3. Novos Models a Criar

| Model | Relacionamentos Principais |
|-------|---------------------------|
| `FinancialCategory` | `hasMany(FinancialTransaction)`, `belongsTo(self, 'parent_id')` |
| `FinancialTransaction` | `morphTo(transactable)`, `belongsTo(FinancialCategory)`, `hasMany(FinancialPaymentAllocation)` |
| `FinancialPayment` | `belongsTo(BankAccount)`, `hasMany(FinancialPaymentAllocation)` |
| `FinancialPaymentAllocation` | `belongsTo(FinancialPayment)`, `belongsTo(FinancialTransaction)` |
| `RecurringTransaction` | `belongsTo(FinancialCategory)` |

---

## 4. Observers a Criar

### 4.1. PurchaseOrderObserver

**Evento:** `updated`  
**Condição:** Status mudou para 'approved'  
**Ação:** Criar `FinancialTransaction` do tipo 'payable'

### 4.2. SalesInvoiceObserver

**Evento:** `updated`  
**Condição:** Status mudou para 'sent'  
**Ação:** Criar `FinancialTransaction(s)` do tipo 'receivable' baseado no Payment Term

---

## 5. Commands a Criar

### 5.1. GenerateRecurringTransactionsCommand

```bash
php artisan finance:generate-recurring
```

**Frequência:** Diária (via Schedule)  
**Função:** Verificar `recurring_transactions` e criar `financial_transactions` conforme necessário

---

## 6. Filament Resources a Criar

### 6.1. FinancialCategoryResource

- CRUD de categorias financeiras
- Árvore hierárquica (parent/child)

### 6.2. FinancialTransactionResource

- Listagem de contas a pagar/receber
- Filtros: tipo, status, vencimento, categoria
- Ações: marcar como paga, criar pagamento

### 6.3. FinancialPaymentResource

- Registro de pagamentos/recebimentos
- Formulário de alocação múltipla
- Cálculo automático de variação cambial

### 6.4. RecurringTransactionResource

- CRUD de transações recorrentes
- Preview das próximas gerações

### 6.5. FinancialReportsResource (Widget/Page)

- Dashboard financeiro
- Fluxo de caixa
- DRE
- Contas a pagar/receber por vencimento

---

## 7. Ordem de Execução

1. ✅ **Backup do banco de dados** (segurança)
2. ✅ Remover Filament Resources antigos
3. ✅ Remover Models antigos
4. ✅ Criar migration para drop das tabelas antigas
5. ✅ Criar migrations das novas tabelas
6. ✅ Rodar `php artisan migrate`
7. ✅ Criar novos Models
8. ✅ Criar Observers
9. ✅ Criar Commands
10. ✅ Criar Filament Resources
11. ✅ Criar Seeders para categorias padrão
12. ✅ Testar fluxo completo

---

## 8. Categorias Financeiras Padrão (Seeder)

### Despesas

```
- Custos Fixos
  - Aluguel
  - Salários
  - Impostos Fixos
  - Seguros
  - Depreciação
  
- Custos Variáveis
  - Matéria-Prima (via PO)
  - Frete (via PO)
  - Comissões de Vendas
  - Embalagens
  
- Despesas Operacionais
  - Marketing
  - Viagens
  - Telefone/Internet
  - Material de Escritório
  - Manutenção
```

### Receitas

```
- Receita de Vendas
  - Vendas Nacionais
  - Vendas Exportação
  
- Outras Receitas
  - Receitas Financeiras
  - Descontos Obtidos
```

### Variação Cambial

```
- Ganhos Cambiais
- Perdas Cambiais
```

---

## 9. Próximos Passos

Aguardando aprovação para:

1. Iniciar a remoção dos arquivos antigos
2. Criar as novas migrations
3. Implementar os models e lógica

**Posso começar?** 🚀
