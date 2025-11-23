# 📊 Análise Completa do Sistema Impex - Workflow End-to-End

## 🎯 Visão Geral

O sistema Impex é uma plataforma completa de gerenciamento de importação/exportação que cobre todo o ciclo desde o recebimento de uma solicitação de orçamento (RFQ) até o faturamento e pagamento.

---

## 📋 Fluxo Completo do Processo

### **FASE 1: Recebimento da Solicitação (RFQ - Request for Quote)**

#### **1.1 Cliente Envia RFQ**
- **Entidade:** `Client` (Cliente)
- **Documento:** RFQ (Request for Quote)
- **Ação:** Cliente solicita cotação para produtos específicos

#### **1.2 Criação do Order (RFQ)**
- **Model:** `Order`
- **Resource:** `Orders`
- **Campos Principais:**
  - `order_number`: Número do pedido (auto-gerado: RFQ-YYYY-NNNN)
  - `customer_id`: Cliente que solicitou
  - `customer_nr_rfq`: Número de referência do cliente
  - `currency_id`: Moeda do pedido
  - `category_id`: Categoria dos produtos
  - `commission_percent`: Percentual de comissão
  - `commission_type`: Tipo de comissão (embedded/separate)
  - `status`: Status do pedido
  - `customer_notes`: Notas do cliente
  - `notes`: Notas internas

#### **1.3 Adição de Items ao Order**
- **Model:** `OrderItem`
- **Campos Principais:**
  - `order_id`: Referência ao Order
  - `product_id`: Produto solicitado
  - `quantity`: Quantidade
  - `target_price_cents`: Preço alvo (opcional)
  - `notes`: Observações

**💡 Lógica:** Cada item do RFQ representa um produto que o cliente deseja cotar.

---

### **FASE 2: Solicitação de Cotações aos Fornecedores**

#### **2.1 Seleção de Fornecedores**
- **Model:** `Supplier`
- **Resource:** `Suppliers`
- **Ação:** Selecionar fornecedores para solicitar cotação

#### **2.2 Criação de Supplier Quotes**
- **Model:** `SupplierQuote`
- **Resource:** `SupplierQuotes`
- **Campos Principais:**
  - `order_id`: Referência ao Order (RFQ)
  - `supplier_id`: Fornecedor
  - `quote_number`: Número da cotação (auto-gerado: [3 letras supplier][YY][NNNN]_Rev[N])
  - `revision_number`: Número da revisão
  - `is_latest`: Se é a versão mais recente
  - `status`: Status (draft, sent, accepted, rejected)
  - `currency_id`: Moeda da cotação
  - `total_price_before_commission`: Total antes da comissão
  - `total_price_after_commission`: Total depois da comissão
  - `commission_amount`: Valor da comissão
  - `locked_exchange_rate`: Taxa de câmbio travada
  - `locked_exchange_rate_date`: Data da taxa
  - `valid_until`: Validade da cotação
  - `supplier_notes`: Notas do fornecedor
  - `notes`: Notas internas

#### **2.3 Adição de Items à Quote**
- **Model:** `QuoteItem`
- **Campos Principais:**
  - `supplier_quote_id`: Referência à SupplierQuote
  - `order_item_id`: Referência ao OrderItem original
  - `product_id`: Produto
  - `quantity`: Quantidade
  - `unit_price_before_commission`: Preço unitário antes da comissão
  - `unit_price_after_commission`: Preço unitário depois da comissão
  - `total_price_before_commission`: Total antes da comissão
  - `total_price_after_commission`: Total depois da comissão
  - `converted_price_cents`: Preço convertido para moeda do Order
  - `delivery_days`: Prazo de entrega
  - `supplier_part_number`: Número de peça do fornecedor
  - `supplier_notes`: Notas do fornecedor

**💡 Lógica Automática:**
- **Exchange Rate Locking:** Ao criar a quote, o sistema trava a taxa de câmbio automaticamente
- **Commission Calculation:** Calcula automaticamente a comissão baseado no `commission_type`:
  - **Embedded:** Comissão embutida no preço
  - **Separate:** Comissão adicionada separadamente
- **Price Conversion:** Converte preços da moeda do fornecedor para a moeda do Order

#### **2.4 Envio da Quote ao Fornecedor**
- **Model:** `QuoteSentLog`
- **Ação:** Registra quando a quote foi enviada ao fornecedor
- **Status:** `draft` → `sent`

---

### **FASE 3: Recebimento e Análise de Cotações**

#### **3.1 Fornecedor Responde com Preços**
- **Ação:** Fornecedor preenche os preços e prazos
- **Status:** Quote permanece `sent` ou muda para `accepted`/`rejected`

#### **3.2 Comparação de Quotes**
- **Funcionalidade:** Sistema permite comparar múltiplas quotes de diferentes fornecedores
- **Critérios:**
  - Preço total
  - Prazo de entrega
  - Histórico do fornecedor
  - Qualidade

#### **3.3 Seleção da Melhor Quote**
- **Model:** `Order`
- **Campo:** `selected_quote_id`
- **Ação:** Selecionar a quote vencedora
- **Status Quote:** `accepted`

**💡 Lógica:** Apenas uma quote pode ser selecionada por Order.

---

### **FASE 4: Criação do Purchase Order (PO)**

#### **4.1 Geração do PO**
- **Model:** `PurchaseOrder`
- **Resource:** `PurchaseOrders`
- **Origem:** Criado a partir da `SupplierQuote` selecionada
- **Campos Principais:**
  - `po_number`: Número do PO (auto-gerado: PO-YYYY-NNNN)
  - `supplier_id`: Fornecedor
  - `supplier_quote_id`: Quote de origem
  - `currency_id`: Moeda
  - `base_currency_id`: Moeda base
  - `exchange_rate`: Taxa de câmbio
  - `status`: Status (draft, sent, confirmed, completed, cancelled)
  - `subtotal`: Subtotal
  - `tax`: Impostos
  - `shipping`: Frete
  - `total`: Total
  - `total_base_currency`: Total na moeda base
  - `expected_delivery_date`: Data prevista de entrega
  - `payment_term_id`: Condições de pagamento
  - `notes`: Observações
  - `terms_and_conditions`: Termos e condições

#### **4.2 Items do PO**
- **Model:** `PurchaseOrderItem`
- **Origem:** Copiados dos `QuoteItem`
- **Campos Principais:**
  - `purchase_order_id`: Referência ao PO
  - `product_id`: Produto
  - `quantity`: Quantidade
  - `unit_price`: Preço unitário
  - `tax_rate`: Taxa de imposto
  - `tax_amount`: Valor do imposto
  - `total`: Total do item
  - `notes`: Observações

#### **4.3 Envio do PO ao Fornecedor**
- **Status:** `draft` → `sent`
- **Ação:** PO é enviado ao fornecedor para confirmação

#### **4.4 Confirmação do Fornecedor**
- **Status:** `sent` → `confirmed`
- **Ação:** Fornecedor confirma o PO

**💡 Lógica:** 
- Valores são calculados automaticamente
- Taxa de câmbio é travada no momento da criação
- Payment Terms definem prazos de pagamento

---

### **FASE 5: Recebimento e Controle de Qualidade**

#### **5.1 Shipment (Embarque)**
- **Model:** `Shipment`
- **Resource:** `Shipments`
- **Campos Principais:**
  - `shipment_number`: Número do embarque
  - `purchase_order_id`: PO relacionado
  - `status`: Status (pending, in_transit, delivered, cancelled)
  - `tracking_number`: Número de rastreamento
  - `carrier`: Transportadora
  - `shipped_date`: Data de embarque
  - `expected_delivery_date`: Data prevista de entrega
  - `actual_delivery_date`: Data real de entrega

#### **5.2 Shipment Items**
- **Model:** `ShipmentItem`
- **Campos:** Produtos e quantidades embarcadas

#### **5.3 Quality Inspection**
- **Model:** `QualityInspection`
- **Resource:** `QualityInspections`
- **Campos Principais:**
  - `shipment_id`: Embarque inspecionado
  - `status`: Status (pending, in_progress, passed, failed, conditional)
  - `inspector_name`: Nome do inspetor
  - `inspection_date`: Data da inspeção
  - `overall_result`: Resultado geral
  - `notes`: Observações

#### **5.4 Quality Checkpoints**
- **Model:** `QualityInspectionCheckpoint`
- **Ação:** Verificação de cada ponto de qualidade
- **Resultado:** Pass/Fail para cada checkpoint

**💡 Lógica:** Inspeção de qualidade garante que os produtos atendem aos padrões antes de aceitar o recebimento.

---

### **FASE 6: Purchase Invoice (Fatura de Compra)**

#### **6.1 Criação da Purchase Invoice**
- **Model:** `PurchaseInvoice`
- **Resource:** `PurchaseInvoices`
- **Origem:** Criada a partir do `PurchaseOrder`
- **Campos Principais:**
  - `invoice_number`: Número da invoice (PI-YYYY-NNNN)
  - `revision_number`: Número da revisão
  - `supplier_id`: Fornecedor
  - `purchase_order_id`: PO relacionado
  - `payment_term_id`: **Condições de pagamento**
  - `currency_id`: Moeda
  - `base_currency_id`: Moeda base
  - `exchange_rate`: Taxa de câmbio
  - `invoice_date`: Data da invoice
  - `shipment_date`: **Data de embarque**
  - `due_date`: **Data de vencimento (auto-calculada)**
  - `payment_date`: Data do pagamento
  - `subtotal`: Subtotal
  - `tax`: Impostos
  - `total`: Total
  - `total_base_currency`: Total na moeda base
  - `status`: Status (draft, sent, paid, overdue, cancelled, superseded)
  - `payment_method`: Método de pagamento
  - `payment_reference`: Referência do pagamento
  - `superseded_by_id`: ID da invoice que substituiu esta
  - `supersedes_id`: ID da invoice que esta substituiu
  - `cancellation_reason`: Motivo do cancelamento

#### **6.2 Items da Purchase Invoice**
- **Model:** `PurchaseInvoiceItem`
- **Origem:** Copiados do `PurchaseOrderItem`

#### **6.3 Payment Terms (Condições de Pagamento)**
- **Model:** `PaymentTerm`
- **Resource:** `PaymentTerms`
- **Estrutura:**
  - **PaymentTermStage:** Estágios de pagamento
    - `percentage`: Percentual do pagamento
    - `days`: Dias para pagamento
    - `calculation_base`: **Base de cálculo (invoice_date ou shipment_date)**
    - `sort_order`: Ordem do estágio

**💡 Lógica Automática:**
- **Due Date Calculation:** 
  - Se `calculation_base = invoice_date`: `due_date = invoice_date + days`
  - Se `calculation_base = shipment_date`: `due_date = shipment_date + days`
- **Reactive Fields:** Quando Payment Term, Invoice Date ou Shipment Date mudam, o Due Date é recalculado automaticamente

#### **6.4 Actions de Status**

**Mark as Sent:**
- **Status:** `draft` → `sent`
- **Campo:** `sent_at` = data atual
- **Ação:** Invoice foi enviada ao fornecedor

**Mark as Paid:**
- **Status:** `sent`/`overdue` → `paid`
- **Campos:**
  - `paid_at`: Data do pagamento
  - `payment_date`: Data do pagamento
  - `payment_method`: Método (bank_transfer, credit_card, check, cash, wire_transfer, other)
  - `payment_reference`: Referência (transaction ID, número do cheque, etc.)
- **Ação:** Registra o pagamento da invoice

**Cancel Invoice:**
- **Status:** qualquer → `cancelled`
- **Campos:**
  - `cancelled_at`: Data do cancelamento
  - `cancellation_reason`: Motivo do cancelamento
- **Ação:** Cancela a invoice (não pode ser desfeito)

**Create Revision:**
- **Status:** `sent`/`paid`/`overdue` → nova invoice em `draft`
- **Lógica:**
  1. Cria nova invoice com `revision_number` incrementado
  2. Copia todos os items
  3. Marca invoice original como `superseded`
  4. Cria links bidirecionais (`superseded_by_id` e `supersedes_id`)
  5. Nova invoice começa em status `draft`
  6. Limpa timestamps de envio/pagamento
- **Uso:** Corrigir erros em invoices já enviadas

#### **6.5 Export to PDF**
- **Funcionalidade:** Gerar PDF profissional da invoice
- **Template:** Inclui:
  - Logo e informações da empresa (de `CompanySettings`)
  - Dados do fornecedor
  - Tabela de items
  - Totais
  - Payment terms
  - Notes e Terms & Conditions
  - Watermarks por status (Draft, Cancelled, Superseded)

---

### **FASE 7: Sales Invoice (Fatura de Venda)**

#### **7.1 Criação da Sales Invoice**
- **Model:** `SalesInvoice`
- **Resource:** `SalesInvoices`
- **Origem:** Pode ser criada a partir de:
  - **Quote:** Preenche o Client automaticamente
  - **Purchase Orders:** Preenche os Items automaticamente
- **Campos Principais:**
  - `invoice_number`: Número da invoice (SI-YYYY-NNNN)
  - `revision_number`: Número da revisão
  - `client_id`: Cliente
  - `quote_id`: Quote de origem (opcional)
  - `payment_term_id`: **Condições de pagamento**
  - `currency_id`: Moeda
  - `base_currency_id`: Moeda base
  - `exchange_rate`: Taxa de câmbio
  - `invoice_date`: Data da invoice
  - `shipment_date`: **Data de embarque**
  - `due_date`: **Data de vencimento (auto-calculada)**
  - `payment_date`: Data do pagamento
  - `subtotal`: Subtotal
  - `commission`: Comissão
  - `tax`: Impostos
  - `total`: Total
  - `total_base_currency`: Total na moeda base
  - `status`: Status (draft, sent, paid, overdue, cancelled, superseded)
  - `payment_method`: Método de pagamento
  - `payment_reference`: Referência do pagamento
  - `superseded_by_id`: ID da invoice que substituiu esta
  - `supersedes_id`: ID da invoice que esta substituiu

#### **7.2 Workflow de Criação**

**Passo 1: Selecionar Quote**
- **Ação:** Selecionar a `SupplierQuote` relacionada
- **Auto-fill:** Preenche automaticamente o `client_id` (de `quote->order->customer_id`)

**Passo 2: Selecionar Purchase Orders**
- **Ação:** Selecionar um ou múltiplos `PurchaseOrder`
- **Auto-fill:** Preenche automaticamente os items:
  - Product ID, Name, SKU
  - Quantity
  - Unit Price (convertido de centavos)
  - Commission (calculada)
  - Total
  - Referência ao PO de origem

**Passo 3: Revisar e Ajustar**
- **Ação:** Revisar items, ajustar quantidades, preços, comissões
- **Cálculos:** Totais são recalculados automaticamente

**Passo 4: Definir Payment Terms**
- **Ação:** Selecionar Payment Term
- **Auto-cálculo:** Due Date é calculado automaticamente baseado em:
  - Invoice Date
  - Shipment Date (se aplicável)
  - Payment Term configuration

#### **7.3 Items da Sales Invoice**
- **Model:** `SalesInvoiceItem`
- **Campos Principais:**
  - `sales_invoice_id`: Invoice
  - `product_id`: Produto
  - `purchase_order_id`: PO de origem
  - `purchase_order_item_id`: Item do PO
  - `quote_item_id`: Item da Quote
  - `quantity`: Quantidade
  - `unit_price`: Preço unitário
  - `commission`: Comissão por unidade
  - `tax_rate`: Taxa de imposto
  - `tax_amount`: Valor do imposto
  - `total`: Total do item

#### **7.4 Relacionamento com Purchase Orders**
- **Model:** `SalesInvoice` ↔ `PurchaseOrder` (Many-to-Many)
- **Tabela Pivot:** `sales_invoice_purchase_orders`
- **Lógica:** Uma Sales Invoice pode consolidar múltiplos POs

#### **7.5 Actions de Status**
- **Mark as Sent:** `draft` → `sent`
- **Mark as Paid:** `sent`/`overdue` → `paid` (com payment details)
- **Cancel Invoice:** qualquer → `cancelled`
- **Create Revision:** Criar nova versão da invoice

#### **7.6 Export to PDF**
- **Template:** Similar ao Purchase Invoice, mas com:
  - Informações do cliente
  - Referências aos POs
  - Coluna de comissão
  - Totais com comissão destacada

---

### **FASE 8: Pagamentos e Reconciliação**

#### **8.1 Customer Receipt (Recebimento do Cliente)**
- **Model:** `CustomerReceipt`
- **Resource:** `CustomerReceipts`
- **Campos:**
  - `client_id`: Cliente
  - `amount`: Valor recebido
  - `receipt_date`: Data do recebimento
  - `payment_method`: Método
  - `reference`: Referência

#### **8.2 Customer Receipt Allocation**
- **Model:** `CustomerReceiptAllocation`
- **Ação:** Alocar o recebimento às Sales Invoices
- **Lógica:** Um recebimento pode ser alocado para múltiplas invoices

#### **8.3 Supplier Payment (Pagamento ao Fornecedor)**
- **Model:** `SupplierPayment`
- **Resource:** `SupplierPayments`
- **Campos:**
  - `supplier_id`: Fornecedor
  - `amount`: Valor pago
  - `payment_date`: Data do pagamento
  - `payment_method`: Método
  - `reference`: Referência

#### **8.4 Supplier Payment Allocation**
- **Model:** `SupplierPaymentAllocation`
- **Ação:** Alocar o pagamento às Purchase Invoices
- **Lógica:** Um pagamento pode ser alocado para múltiplas invoices

---

## 🔄 Diagrama de Fluxo Simplificado

```
┌─────────────────────────────────────────────────────────────────────┐
│                         FASE 1: RFQ                                 │
│                                                                     │
│  Cliente → Order (RFQ) → OrderItems                                │
│            RFQ-2025-0001                                            │
└─────────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────────┐
│                  FASE 2: Supplier Quotes                            │
│                                                                     │
│  Order → SupplierQuote (múltiplas) → QuoteItems                    │
│          TRA250001_Rev1                                             │
│          ABC250001_Rev1                                             │
│          XYZ250001_Rev1                                             │
│                                                                     │
│  Lógica Automática:                                                │
│  ✓ Exchange Rate Locking                                           │
│  ✓ Commission Calculation                                          │
│  ✓ Price Conversion                                                │
└─────────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────────┐
│              FASE 3: Seleção da Melhor Quote                        │
│                                                                     │
│  Order.selected_quote_id = SupplierQuote.id                        │
│  SupplierQuote.status = 'accepted'                                 │
└─────────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────────┐
│                  FASE 4: Purchase Order                             │
│                                                                     │
│  SupplierQuote → PurchaseOrder → PurchaseOrderItems                │
│                  PO-2025-0001                                       │
│                                                                     │
│  Status: draft → sent → confirmed → completed                      │
└─────────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────────┐
│            FASE 5: Shipment & Quality Control                       │
│                                                                     │
│  PurchaseOrder → Shipment → ShipmentItems                          │
│                  SH-2025-0001                                       │
│                                                                     │
│  Shipment → QualityInspection → QualityCheckpoints                 │
│             Status: pending → in_progress → passed/failed           │
└─────────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────────┐
│                FASE 6: Purchase Invoice                             │
│                                                                     │
│  PurchaseOrder → PurchaseInvoice → PurchaseInvoiceItems            │
│                  PI-2025-0001-Rev1                                  │
│                                                                     │
│  Payment Terms Logic:                                              │
│  ✓ Due Date Auto-calculation                                       │
│  ✓ Based on Invoice Date OR Shipment Date                          │
│  ✓ Reactive updates                                                │
│                                                                     │
│  Actions:                                                           │
│  ✓ Mark as Sent                                                    │
│  ✓ Mark as Paid (with payment details)                             │
│  ✓ Cancel Invoice                                                  │
│  ✓ Create Revision                                                 │
│  ✓ Download PDF                                                    │
│                                                                     │
│  Status: draft → sent → paid/overdue/cancelled/superseded          │
└─────────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────────┐
│                 FASE 7: Sales Invoice                               │
│                                                                     │
│  Quote + PurchaseOrders → SalesInvoice → SalesInvoiceItems         │
│                            SI-2025-0001-Rev1                        │
│                                                                     │
│  Workflow:                                                          │
│  1. Select Quote → Auto-fill Client                                │
│  2. Select POs → Auto-fill Items                                   │
│  3. Review & Adjust                                                │
│  4. Set Payment Terms → Auto-calculate Due Date                    │
│                                                                     │
│  Actions:                                                           │
│  ✓ Mark as Sent                                                    │
│  ✓ Mark as Paid (with payment details)                             │
│  ✓ Cancel Invoice                                                  │
│  ✓ Create Revision                                                 │
│  ✓ Download PDF                                                    │
│                                                                     │
│  Status: draft → sent → paid/overdue/cancelled/superseded          │
└─────────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────────┐
│              FASE 8: Payments & Reconciliation                      │
│                                                                     │
│  CustomerReceipt → CustomerReceiptAllocation → SalesInvoice         │
│  SupplierPayment → SupplierPaymentAllocation → PurchaseInvoice      │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 🎯 Funcionalidades Chave Implementadas

### **1. Payment Terms com Flexibilidade Total**
- ✅ Cálculo baseado em Invoice Date
- ✅ Cálculo baseado em Shipment Date
- ✅ Multi-stage payments
- ✅ Auto-cálculo reativo do Due Date
- ✅ Interface admin para configuração

### **2. Revision System**
- ✅ Criar novas versões de invoices
- ✅ Manter histórico completo
- ✅ Links bidirecionais entre versões
- ✅ Status "Superseded" para versões antigas
- ✅ Visualização de histórico na sidebar

### **3. Status Management**
- ✅ Mark as Sent (com timestamp)
- ✅ Mark as Paid (com payment details completos)
- ✅ Cancel Invoice (com motivo)
- ✅ Validações de transição de status

### **4. PDF Export**
- ✅ Templates profissionais
- ✅ Dados da empresa (CompanySettings)
- ✅ Logo customizável
- ✅ Watermarks por status
- ✅ Formatação de currency
- ✅ Nome de arquivo: PI-2025-0001-Rev1.pdf

### **5. Company Settings**
- ✅ Configuração centralizada
- ✅ Upload de logo
- ✅ Informações de contato
- ✅ Dados bancários
- ✅ Footer customizável
- ✅ Usado em todos os documentos

### **6. Auto-fill Inteligente**
- ✅ Quote → Client
- ✅ POs → Items (com consolidação)
- ✅ Payment Term → Due Date
- ✅ Exchange Rate → Conversões automáticas
- ✅ Commission → Cálculos automáticos

### **7. Multi-Currency Support**
- ✅ Exchange rate locking
- ✅ Conversão automática
- ✅ Base currency tracking
- ✅ Historical rates

### **8. Commission Management**
- ✅ Embedded commission
- ✅ Separate commission
- ✅ Cálculos automáticos
- ✅ Tracking por item

---

## 📊 Entidades Principais e Relacionamentos

### **Core Entities:**

```
Client (Customer)
  ↓ has many
Order (RFQ)
  ↓ has many
OrderItem
  ↓ referenced by
QuoteItem
  ↓ belongs to
SupplierQuote
  ↓ selected as
Order.selected_quote_id
  ↓ generates
PurchaseOrder
  ↓ has many
PurchaseOrderItem
  ↓ generates
PurchaseInvoice
  ↓ has many
PurchaseInvoiceItem

---

SupplierQuote
  ↓ referenced by
SalesInvoice.quote_id (for client)
  
PurchaseOrder (multiple)
  ↓ many-to-many
SalesInvoice
  ↓ has many
SalesInvoiceItem
  ↓ paid via
CustomerReceipt
  ↓ allocated to
CustomerReceiptAllocation

---

PurchaseInvoice
  ↓ paid via
SupplierPayment
  ↓ allocated to
SupplierPaymentAllocation
```

### **Supporting Entities:**

- **PaymentTerm** → **PaymentTermStage** (multi-stage payments)
- **Currency** → **ExchangeRate** (currency conversion)
- **Supplier** → **SupplierContact**, **SupplierFile**, **SupplierPerformanceMetric**
- **Client** → **ClientContact**
- **Product** → **ProductFeature**, **ProductFile**
- **Shipment** → **ShipmentItem**, **TrackingEvent**
- **QualityInspection** → **QualityInspectionCheckpoint**, **QualityInspectionItem**
- **CompanySetting** (singleton for company info)

---

## 🔐 Regras de Negócio Implementadas

### **1. Exchange Rate Locking**
- Taxa travada ao criar SupplierQuote
- Garante preços consistentes
- Histórico de taxas mantido

### **2. Commission Calculation**
- Embedded: Comissão embutida no preço
- Separate: Comissão adicionada ao total
- Cálculo automático em QuoteItem e SalesInvoiceItem

### **3. Status Transitions**
- Validações de transição
- Timestamps automáticos
- Ações permitidas por status

### **4. Revision Control**
- Apenas uma revisão "latest" por invoice number
- Constraint unique: (invoice_number, revision_number)
- Links bidirecionais mantidos

### **5. Payment Terms**
- Due date calculado automaticamente
- Base de cálculo configurável (invoice_date ou shipment_date)
- Suporte a multi-stage payments

### **6. Auto-numbering**
- Order: RFQ-YYYY-NNNN
- SupplierQuote: [3 letras][YY][NNNN]_Rev[N]
- PurchaseOrder: PO-YYYY-NNNN
- PurchaseInvoice: PI-YYYY-NNNN
- SalesInvoice: SI-YYYY-NNNN

---

## 📈 Métricas e KPIs Disponíveis

### **Supplier Performance:**
- **Model:** `SupplierPerformanceMetric`
- **Métricas:**
  - On-time delivery rate
  - Quality score
  - Response time
  - Price competitiveness

### **Financial:**
- Total Purchase Invoices (paid, pending, overdue)
- Total Sales Invoices (paid, pending, overdue)
- Commission earned
- Currency exposure

### **Operational:**
- Orders in progress
- Quotes pending
- POs confirmed
- Shipments in transit
- Quality inspections pending

---

## 🚀 Próximas Funcionalidades Sugeridas

### **Prioridade Alta:**
1. **Overdue Detection** - Scheduled command para detectar invoices vencidas
2. **Dashboard** - Widgets com KPIs e gráficos
3. **Email Notifications** - Notificações automáticas de status
4. **Payment Tracking** - Pagamentos parciais e multi-stage

### **Prioridade Média:**
5. **Reports** - Relatórios financeiros e operacionais
6. **Bulk Actions** - Ações em massa (export, email, status update)
7. **Client Portal** - Portal para clientes verem invoices
8. **Document Management** - Anexar documentos (contratos, certificados)

### **Prioridade Baixa:**
9. **Multi-company** - Suporte para múltiplas empresas
10. **Advanced Analytics** - BI e análises avançadas
11. **API** - API REST para integrações
12. **Mobile App** - App mobile para aprovações

---

## 💡 Pontos Fortes do Sistema Atual

1. ✅ **Workflow Completo:** Cobre todo o ciclo de importação/exportação
2. ✅ **Automação Inteligente:** Auto-fill, auto-cálculo, auto-numbering
3. ✅ **Multi-currency:** Suporte completo com exchange rate locking
4. ✅ **Revision Control:** Sistema robusto de versionamento
5. ✅ **Payment Flexibility:** Payment terms configuráveis e flexíveis
6. ✅ **Quality Control:** Inspeção de qualidade integrada
7. ✅ **Traceability:** Rastreamento completo de Order → Invoice
8. ✅ **Professional Documents:** PDFs profissionais com branding
9. ✅ **Commission Management:** Cálculo automático de comissões
10. ✅ **Status Management:** Controle rigoroso de status e transições

---

## 🎓 Conclusão

O sistema Impex está **bem estruturado** e cobre os principais processos de uma operação de importação/exportação. A arquitetura é **escalável**, os relacionamentos estão **bem definidos**, e as automações implementadas **reduzem significativamente** o trabalho manual.

**Principais Conquistas:**
- ✅ Workflow end-to-end funcional
- ✅ Payment Terms flexível e poderoso
- ✅ Revision system robusto
- ✅ PDF export profissional
- ✅ Company settings centralizado
- ✅ Auto-fill inteligente

**Próximos Passos Recomendados:**
1. Implementar Overdue Detection
2. Criar Dashboard com KPIs
3. Adicionar Email Notifications
4. Implementar Payment Tracking completo

O sistema está **pronto para uso em produção** e pode ser expandido gradualmente conforme as necessidades do negócio.
