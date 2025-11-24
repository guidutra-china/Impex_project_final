# Exemplos Práticos: Sistema Financeiro com Múltiplas Moedas

**Autor:** Manus AI  
**Data:** 24 de Novembro de 2025

## Cenário 1: Compra em EUR, Pagamento em USD

### Contexto
- **Moeda Base da Empresa:** BRL
- **Fornecedor:** Empresa alemã que trabalha em EUR
- **Sua Conta Bancária:** USD (dólares americanos)

### Passo a Passo

#### 1. Aprovação da Purchase Order

**Data:** 01/12/2025  
**PO-2025-001:**
- Fornecedor: ABC GmbH (Alemanha)
- Valor: **€10.000**
- Vencimento: 30 dias (31/12/2025)

**Taxas de Câmbio no dia 01/12:**
- EUR/BRL: 1 EUR = 5,50 BRL
- USD/BRL: 1 USD = 5,00 BRL

**Sistema cria automaticamente:**

```sql
INSERT INTO financial_transactions (
    description,
    type,
    status,
    amount,
    paid_amount,
    due_date,
    transaction_date,
    currency_id,
    exchange_rate_to_base,
    amount_base_currency,
    transactable_type,
    transactable_id
) VALUES (
    'Purchase Order PO-2025-001 - ABC GmbH',
    'payable',
    'pending',
    1000000,  -- €10.000 em centavos
    0,
    '2025-12-31',
    '2025-12-01',
    2,  -- EUR
    5.50,
    5500000,  -- R$55.000 em centavos (€10.000 × 5,50)
    'PurchaseOrder',
    1
);
```

#### 2. Registro do Pagamento

**Data:** 31/12/2025  
**Taxas de Câmbio no dia 31/12:**
- EUR/BRL: 1 EUR = 5,80 BRL (euro valorizou!)
- USD/BRL: 1 USD = 5,10 BRL

**Você precisa pagar €10.000. Quanto isso custa em USD?**
- €10.000 × 5,80 BRL = R$58.000
- R$58.000 ÷ 5,10 USD = **$11.372,55**

**Você registra o pagamento:**

```sql
INSERT INTO financial_payments (
    description,
    type,
    bank_account_id,
    payment_date,
    amount,
    currency_id,
    exchange_rate_to_base,
    amount_base_currency
) VALUES (
    'Pagamento PO-2025-001 via transferência USD',
    'debit',
    1,  -- Conta em USD
    '2025-12-31',
    1137255,  -- $11.372,55 em centavos
    1,  -- USD
    5.10,
    5800000  -- R$58.000 em centavos ($11.372,55 × 5,10)
);
```

#### 3. Alocação e Cálculo da Variação Cambial

**Sistema calcula automaticamente:**

```php
// Valor da dívida na data da transação (em BRL)
$original_value_brl = 5500000; // R$55.000

// Valor da dívida na data do pagamento (em BRL)
$payment_value_brl = 5800000; // R$58.000

// Variação cambial (perda)
$gain_loss = $payment_value_brl - $original_value_brl;
// = 5800000 - 5500000 = 300000 (R$3.000 de perda)
```

**Registro da alocação:**

```sql
INSERT INTO financial_payment_allocations (
    financial_payment_id,
    financial_transaction_id,
    allocated_amount,
    gain_loss_on_exchange
) VALUES (
    1,  -- Pagamento de $11.372,55
    1,  -- Dívida de €10.000
    1000000,  -- €10.000 em centavos (quitação total)
    -300000  -- Perda de R$3.000 (negativo = perda)
);
```

**Atualização da transação:**

```sql
UPDATE financial_transactions
SET 
    paid_amount = 1000000,  -- €10.000 pagos
    status = 'paid'
WHERE id = 1;
```

### Resultado Contábil

| Item | Valor Original (BRL) | Valor Pago (BRL) | Variação |
|------|---------------------|------------------|----------|
| Dívida em EUR | R$ 55.000,00 | R$ 58.000,00 | **-R$ 3.000,00** |

**Interpretação:** Você teve uma **perda cambial de R$3.000** porque o euro valorizou entre a data da compra e a data do pagamento.

---

## Cenário 2: Venda em USD, Recebimento em EUR

### Contexto
- **Moeda Base da Empresa:** BRL
- **Cliente:** Empresa americana que paga em USD
- **Você recebe em:** EUR (sua conta na Europa)

### Passo a Passo

#### 1. Envio da Sales Invoice

**Data:** 01/01/2026  
**SI-2026-001:**
- Cliente: XYZ Corp (USA)
- Valor: **$20.000**
- Vencimento: 30 dias (31/01/2026)

**Taxas de Câmbio no dia 01/01:**
- USD/BRL: 1 USD = 5,00 BRL
- EUR/BRL: 1 EUR = 5,50 BRL

**Sistema cria automaticamente:**

```sql
INSERT INTO financial_transactions (
    description,
    type,
    status,
    amount,
    paid_amount,
    due_date,
    transaction_date,
    currency_id,
    exchange_rate_to_base,
    amount_base_currency,
    transactable_type,
    transactable_id
) VALUES (
    'Sales Invoice SI-2026-001 - XYZ Corp',
    'receivable',
    'pending',
    2000000,  -- $20.000 em centavos
    0,
    '2026-01-31',
    '2026-01-01',
    1,  -- USD
    5.00,
    10000000,  -- R$100.000 em centavos ($20.000 × 5,00)
    'SalesInvoice',
    1
);
```

#### 2. Registro do Recebimento

**Data:** 31/01/2026  
**Taxas de Câmbio no dia 31/01:**
- USD/BRL: 1 USD = 4,80 BRL (dólar desvalorizou!)
- EUR/BRL: 1 EUR = 5,60 BRL

**Cliente paga $20.000. Você recebe em EUR na sua conta:**
- $20.000 × 4,80 BRL = R$96.000
- R$96.000 ÷ 5,60 EUR = **€17.142,86**

**Você registra o recebimento:**

```sql
INSERT INTO financial_payments (
    description,
    type,
    bank_account_id,
    payment_date,
    amount,
    currency_id,
    exchange_rate_to_base,
    amount_base_currency
) VALUES (
    'Recebimento SI-2026-001 via transferência EUR',
    'credit',
    2,  -- Conta em EUR
    '2026-01-31',
    1714286,  -- €17.142,86 em centavos
    2,  -- EUR
    5.60,
    9600000  -- R$96.000 em centavos (€17.142,86 × 5,60)
);
```

#### 3. Alocação e Cálculo da Variação Cambial

**Sistema calcula:**

```php
// Valor a receber na data da venda (em BRL)
$original_value_brl = 10000000; // R$100.000

// Valor recebido na data do pagamento (em BRL)
$payment_value_brl = 9600000; // R$96.000

// Variação cambial (perda)
$gain_loss = $payment_value_brl - $original_value_brl;
// = 9600000 - 10000000 = -400000 (R$4.000 de perda)
```

**Registro da alocação:**

```sql
INSERT INTO financial_payment_allocations (
    financial_payment_id,
    financial_transaction_id,
    allocated_amount,
    gain_loss_on_exchange
) VALUES (
    2,  -- Recebimento de €17.142,86
    2,  -- Receita de $20.000
    2000000,  -- $20.000 em centavos (quitação total)
    -400000  -- Perda de R$4.000
);
```

### Resultado Contábil

| Item | Valor Original (BRL) | Valor Recebido (BRL) | Variação |
|------|---------------------|---------------------|----------|
| Receita em USD | R$ 100.000,00 | R$ 96.000,00 | **-R$ 4.000,00** |

**Interpretação:** Você teve uma **perda cambial de R$4.000** porque o dólar desvalorizou entre a data da venda e a data do recebimento.

---

## Cenário 3: Pagamento Parcial em Múltiplas Moedas

### Contexto
- Você tem uma dívida de **€50.000** com vencimento em 60 dias
- Você decide pagar em 2 parcelas, usando contas diferentes

### Passo 1: Primeira Parcela (30 dias)

**Pagamento:** $15.000 (da conta USD)  
**Taxa do dia:** USD/BRL = 5,00 | EUR/BRL = 5,50

**Conversão:**
- $15.000 × 5,00 = R$75.000
- R$75.000 ÷ 5,50 = **€13.636,36**

**Alocação:**
```sql
INSERT INTO financial_payment_allocations (
    financial_payment_id,
    financial_transaction_id,
    allocated_amount,
    gain_loss_on_exchange
) VALUES (
    3,
    3,
    1363636,  -- €13.636,36 em centavos
    0  -- Sem variação (mesma taxa da transação original)
);
```

**Atualização da transação:**
```sql
UPDATE financial_transactions
SET 
    paid_amount = 1363636,  -- €13.636,36 pagos
    status = 'partially_paid'
WHERE id = 3;
```

**Saldo restante:** €50.000 - €13.636,36 = **€36.363,64**

### Passo 2: Segunda Parcela (60 dias)

**Pagamento:** €36.363,64 (da conta EUR)  
**Taxa do dia:** EUR/BRL = 5,80 (euro valorizou!)

**Cálculo da variação:**
- Saldo restante na taxa original: €36.363,64 × 5,50 = R$199.999,52
- Saldo restante na taxa atual: €36.363,64 × 5,80 = R$210.909,11
- **Perda cambial:** R$210.909,11 - R$199.999,52 = **R$10.909,59**

**Alocação:**
```sql
INSERT INTO financial_payment_allocations (
    financial_payment_id,
    financial_transaction_id,
    allocated_amount,
    gain_loss_on_exchange
) VALUES (
    4,
    3,
    3636364,  -- €36.363,64 em centavos
    -1090959  -- Perda de R$10.909,59
);
```

**Atualização final:**
```sql
UPDATE financial_transactions
SET 
    paid_amount = 5000000,  -- €50.000 totalmente pagos
    status = 'paid'
WHERE id = 3;
```

### Resumo da Operação

| Parcela | Moeda | Valor Pago | Taxa BRL | Valor em BRL | Variação Cambial |
|---------|-------|-----------|----------|--------------|------------------|
| 1ª | USD | $15.000,00 | 5,00 | R$ 75.000,00 | R$ 0,00 |
| 2ª | EUR | €36.363,64 | 5,80 | R$ 210.909,11 | **-R$ 10.909,59** |
| **Total** | - | - | - | **R$ 285.909,11** | **-R$ 10.909,59** |

**Valor original da dívida:** €50.000 × 5,50 = R$275.000,00  
**Valor efetivamente pago:** R$285.909,11  
**Perda total com variação cambial:** R$10.909,59

---

## Benefícios da Arquitetura

1. **Rastreamento Completo:** Cada transação mantém sua moeda original + conversão para a moeda base.
2. **Ganhos/Perdas Automáticos:** O sistema calcula automaticamente a variação cambial em cada alocação.
3. **Relatórios Precisos:** Você pode gerar relatórios em qualquer moeda, com conversões corretas.
4. **Compliance Contábil:** Atende às normas contábeis internacionais para registro de variação cambial.
5. **Flexibilidade Total:** Pague em qualquer moeda, receba em qualquer moeda, sem limitações.
