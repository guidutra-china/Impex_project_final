# 🚀 Shipments Quick Start Guide

## 📦 Como Usar o Sistema de Shipments

### PASSO 1: Criar um Shipment

1. Vá em **Shipments** → **Create**
2. Preencha as informações básicas:
   - **Type**: Outbound (envio) ou Inbound (recebimento)
   - **Status**: Draft (rascunho)
   - **Shipping Method**: Sea, Air, Land, Courier
   - **Carrier**: DHL, FedEx, Maersk, etc.
   - **Dates**: Shipment date, estimated dates
3. Clique em **Create**

✅ Shipment criado com status **Draft**!

---

### PASSO 2: Adicionar Sales Invoices ao Shipment

**Agora você está na página Edit do Shipment.**

1. Clique na aba **"Invoices"** (Relation Manager)
2. Clique em **"Attach Invoice"**
3. Selecione uma ou mais **Sales Invoices**
4. Clique em **Attach**

✅ As invoices foram vinculadas ao shipment!
✅ O sistema calculou automaticamente os totais (items, quantity, weight, volume)

**Você pode adicionar múltiplas invoices:**
- Invoice SI-001 (Cliente A)
- Invoice SI-002 (Cliente A)  
- Invoice SI-003 (Cliente B)

Todas no mesmo shipment! 🎯

---

### PASSO 3: Adicionar Items ao Shipment

**Agora que as invoices estão vinculadas, você pode adicionar os items.**

1. Clique na aba **"Items"** (Relation Manager)
2. Clique em **"Create"**
3. **Selecione o Item** do dropdown:
   - Mostra: `[SI-001] ABC-123 - Widget A (Remaining: 50)`
   - Você vê:
     - Qual invoice
     - SKU do produto
     - Nome do produto
     - Quantidade disponível para enviar

4. **Digite a quantidade** que vai enviar:
   - Exemplo: 30 (de 50 disponíveis)
   - O sistema valida automaticamente!

5. Clique em **Create**

✅ Item adicionado ao shipment!
✅ Quantity tracking atualizado:
   - Ordered: 50
   - To Ship: 30
   - Remaining: 20

**Repita para todos os items que quer enviar!**

---

### PASSO 4: Empacotar os Items (Packing)

**Agora você precisa colocar os items em caixas.**

#### Opção A: Auto-Pack (Recomendado!)

1. Clique na aba **"Packing Boxes"**
2. Clique em **"Auto-Pack Items"** (botão no topo)
3. Digite o **número de caixas**: 3
4. Clique em **Auto-Pack**

✅ O sistema:
- Cria 3 caixas automaticamente
- Distribui os items uniformemente
- Atualiza os status
- Calcula totais

#### Opção B: Manual

1. Clique na aba **"Packing Boxes"**
2. Clique em **"Create"**
3. Preencha:
   - **Box Number**: 1 (auto-generated)
   - **Type**: Carton, Wooden Crate, Pallet, etc.
   - **Label**: "Electronics - Fragile"
   - **Dimensions**: 50 × 40 × 30 cm
   - **Gross Weight**: 25.5 kg
4. Clique em **Create**

✅ Caixa criada!

**Agora adicione items à caixa:**

1. Na lista de caixas, clique em **"View Contents"**
2. Clique em **"Add Item"**
3. Selecione o item
4. Digite a quantidade
5. Clique em **Create**

✅ Item adicionado à caixa!

**Repita até todos os items estarem empacotados.**

---

### PASSO 5: Lacrar as Caixas

**Quando uma caixa estiver completa:**

1. Na lista de caixas, clique em **"Seal"** (action)
2. Confirme

✅ Caixa lacrada!
✅ Status: **Sealed**
✅ Não pode mais ser editada

---

### PASSO 6: Confirmar o Shipment

**Quando tudo estiver pronto:**

1. Volte para a página principal do Shipment (View ou Edit)
2. Clique em **"Confirm Shipment"** (header action - será criado na Phase 3 Part 3)

✅ O sistema valida:
- Todos os items empacotados? ✅
- Todas as caixas lacradas? ✅
- Quantidades corretas? ✅

✅ Shipment confirmado!
✅ Status: **Draft** → **Confirmed**
✅ Quantities nas invoices atualizadas
✅ Invoice status atualizado (se fully shipped)

---

## 📊 RESUMO DO WORKFLOW

```
1. CREATE SHIPMENT
   ↓
2. ATTACH INVOICES (Tab: Invoices)
   - Attach SI-001
   - Attach SI-002
   - Attach SI-003
   ↓
3. ADD ITEMS (Tab: Items)
   - Add Widget A x 30
   - Add Widget B x 20
   - Add Widget C x 40
   ↓
4. PACK ITEMS (Tab: Packing Boxes)
   Option A: Auto-Pack (3 boxes)
   Option B: Manual (create boxes, add items)
   ↓
5. SEAL BOXES
   - Seal Box #1
   - Seal Box #2
   - Seal Box #3
   ↓
6. CONFIRM SHIPMENT
   - Validate all packed
   - Lock shipment
   - Update invoices
   ↓
7. GENERATE DOCUMENTS (Phase 4)
   - Commercial Invoice PDF
   - Packing List PDF
```

---

## 🎯 EXEMPLOS PRÁTICOS

### Exemplo 1: Shipment Simples (1 Invoice)

```
Shipment: SHP-2025-0001
├── Invoice: SI-001 (Cliente ABC)
│   ├── Widget A x 50 → Ship 30
│   └── Widget B x 30 → Ship 20
└── Packing:
    ├── Box #1: Widget A x 15, Widget B x 10
    └── Box #2: Widget A x 15, Widget B x 10
```

### Exemplo 2: Shipment Multi-Invoice

```
Shipment: SHP-2025-0002
├── Invoice: SI-001 (Cliente ABC)
│   └── Product X x 100 → Ship 50
├── Invoice: SI-002 (Cliente ABC)
│   └── Product Y x 80 → Ship 40
└── Invoice: SI-003 (Cliente XYZ)
    └── Product Z x 60 → Ship 30
└── Packing:
    ├── Box #1: Product X x 25
    ├── Box #2: Product X x 25
    ├── Box #3: Product Y x 40
    └── Box #4: Product Z x 30
```

### Exemplo 3: Partial Shipment

```
Invoice SI-001:
- Total Ordered: 100 units

Shipment 1:
- Shipped: 40 units
- Remaining: 60 units
- Status: partially_shipped

Shipment 2 (later):
- Shipped: 60 units
- Remaining: 0 units
- Status: fully_shipped
```

---

## 🔍 DICAS IMPORTANTES

### ✅ DO's

1. **Sempre attach invoices primeiro** antes de adicionar items
2. **Use Auto-Pack** para economizar tempo
3. **Seal boxes** quando estiverem completas
4. **Confirme o shipment** só quando tudo estiver pronto
5. **Use o ViewShipment** para ver todos os detalhes

### ❌ DON'Ts

1. **Não adicione items** sem ter invoices attached
2. **Não confirme** sem empacotar tudo
3. **Não edite** caixas lacradas (unseal primeiro)
4. **Não delete** shipments confirmados (cancele ao invés)

---

## 📱 INTERFACE

### Tabs Disponíveis (Edit/View Page)

1. **Details** - Informações do shipment
2. **Invoices** - Gerenciar invoices vinculadas
3. **Items** - Gerenciar items do shipment
4. **Packing Boxes** - Gerenciar caixas e packing

### Colunas Importantes

**Items Table:**
- Invoice # - De qual invoice veio
- SKU - Código do produto
- Product - Nome do produto
- Ordered - Quantidade na invoice
- To Ship - Quantidade neste shipment
- Shipped - Já enviado (outros shipments)
- Remaining - Ainda falta enviar
- Packing Status - unpacked / partially_packed / fully_packed
- Packed - Quantidade já empacotada
- Remaining - Falta empacotar

**Packing Boxes Table:**
- Box # - Número da caixa
- Type - Tipo (Carton, Crate, etc)
- Status - empty / packing / sealed
- Items - Quantidade de items diferentes
- Quantity - Quantidade total de unidades
- Dimensions - L × W × H
- Volume - m³ (auto-calculated)
- Weight - Gross / Net
- Sealed At/By - Quando e quem lacrou

---

## 🎨 STATUS BADGES

### Shipment Status
- 🟦 **Draft** - Rascunho, pode editar tudo
- 🟨 **Pending** - Aguardando processamento
- 🟧 **Preparing** - Preparando para envio
- 🟩 **Ready to Ship** - Pronto para enviar
- 🟦 **Picked Up** - Coletado
- 🟦 **In Transit** - Em trânsito
- 🟨 **Customs Clearance** - Desembaraço aduaneiro
- 🟧 **Out for Delivery** - Saiu para entrega
- 🟩 **Delivered** - Entregue
- 🟥 **Cancelled** - Cancelado
- 🟥 **Returned** - Devolvido

### Packing Status
- 🟥 **Unpacked** - Não empacotado
- 🟨 **Partially Packed** - Parcialmente empacotado
- 🟩 **Fully Packed** - Totalmente empacotado

### Box Status
- ⚪ **Empty** - Vazia
- 🟨 **Packing** - Empacotando
- 🟩 **Sealed** - Lacrada

---

## ❓ FAQ

### P: Posso adicionar items de invoices diferentes no mesmo shipment?
**R:** Sim! Esse é o objetivo do sistema multi-invoice. Você pode combinar items de várias invoices no mesmo shipment.

### P: Posso enviar parcialmente uma invoice?
**R:** Sim! Você pode criar múltiplos shipments para a mesma invoice. O sistema rastreia o que já foi enviado e o que falta.

### P: O que acontece se eu adicionar mais items do que disponível?
**R:** O sistema valida automaticamente e não permite. Você só pode adicionar até a quantidade disponível (Remaining).

### P: Posso editar um shipment depois de confirmar?
**R:** Não diretamente. Você precisa cancelar o shipment primeiro, o que reverterá todas as operações.

### P: Como sei se todos os items estão empacotados?
**R:** Veja a coluna "Packing Status" na tab Items. Deve estar "fully_packed" para todos os items.

### P: Posso deslacrar uma caixa?
**R:** Sim! Use a action "Unseal" na lista de caixas. Mas só faça isso se realmente necessário.

### P: Quantas caixas posso criar?
**R:** Sem limite! Crie quantas precisar.

### P: O Auto-Pack distribui igualmente?
**R:** Sim! Se você tem 90 items e cria 3 caixas, cada caixa terá 30 items.

### P: Posso ter items de invoices diferentes na mesma caixa?
**R:** Sim! As caixas são independentes das invoices.

---

## 🎉 PRONTO!

Agora você sabe como usar o sistema completo de Shipments!

**Próximos passos:**
- Phase 3 Part 3: Actions (Confirm, Cancel)
- Phase 4: Document Generation (Commercial Invoice, Packing List PDFs)
- Phase 5: Testing & Polish

**Qualquer dúvida, consulte este guia!** 📖
