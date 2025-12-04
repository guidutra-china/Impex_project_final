# Resumo de Melhorias do Recurso Shipment

## 📋 Mudanças Implementadas

### 1. ✅ Banco de Dados e Models

#### Novas Tabelas:
- **container_types** - Tipos padrão de contêineres (20ft, 40ft, 40hc, pallet, etc)
  - Dimensões (L x W x H em metros)
  - Capacidade (peso máximo, volume máximo)
  - Peso vazio (tare weight)
  - Custo base e moeda

- **packing_box_types** - Tipos padrão de caixas de embalagem
  - Dimensões (L x W x H em cm)
  - Capacidade (peso máximo, volume máximo)
  - Peso vazio
  - Custo unitário

- **shipment_proforma_invoices** - Relacionamento muitos-para-muitos
  - Vincula shipments a proforma invoices
  - Rastreia quantidade enviada

#### Alterações em Tabelas Existentes:
- **shipments**
  - ✅ Adicionado `customer_id` (obrigatório)
  - ✅ Removido `container_number` (gerenciado em shipment_containers)
  - ✅ Adicionado `reference_number` (PO do cliente)

- **products**
  - ✅ Adicionado `standard_packaging_quantity` (unidades por caixa)
  - ✅ Adicionado `package_weight` (peso da caixa padrão)
  - ✅ Adicionado `package_dimensions` (dimensões da caixa)
  - ✅ Adicionado `package_volume` (volume da caixa)

### 2. ✅ ShipmentForm (UI)

- ✅ Adicionado campo **Customer** (obrigatório) em Basic Information
- ✅ Adicionado campo **Reference Number** para PO do cliente
- ✅ Removido **Container Number** de Shipping Details
- ✅ Ajustado layout de grids para acomodar novos campos

### 3. ✅ Models Criados

- **ContainerType** - Com relacionamentos e método de cálculo de volume
- **PackingBoxType** - Com relacionamentos e método de cálculo de volume

---

## 🚀 Próximas Etapas (A Implementar)

### Fase 4: Lógica de Embalagem

1. **Atualizar Models com Relacionamentos:**
   - `Shipment` → `hasMany('proformaInvoices')` via `shipment_proforma_invoices`
   - `Shipment` → `belongsTo('customer')`
   - `Product` → relacionamento com `PackingBoxType` (opcional)
   - `ShipmentContainer` → `belongsTo('containerType')`
   - `PackingBox` → `belongsTo('packingBoxType')`

2. **Criar Services:**
   - `PackagingService` - Lógica para calcular embalagem padrão
   - `ContainerService` - Lógica para gerenciar contêineres e caixas

3. **Atualizar ShipmentItems:**
   - Adicionar campos de embalagem (quantidade por caixa, peso, dimensões)
   - Calcular automaticamente a partir do Product

### Fase 5: RelationManagers

1. **ProformaInvoicesRelationManager** - Substituir SalesInvoices
   - Anexar proforma invoices ao shipment
   - Rastrear quantidade enviada
   - Filtrar apenas proformas do cliente

2. **Atualizar ContainersRelationManager:**
   - Usar ContainerType para pré-preencher dimensões e capacidade
   - Validar que caixas não excedem capacidade do contêiner

3. **Atualizar PackingBoxesRelationManager:**
   - Usar PackingBoxType para pré-preencher dimensões
   - Suportar caixas customizadas para produtos sem embalagem padrão
   - Validar que itens não excedem capacidade da caixa

### Fase 6: Validações e Cálculos

1. **Validar Embalagem:**
   - Produtos com embalagem padrão devem usar quantidade múltipla
   - Produtos sem embalagem ou com quantidade inferior devem ter caixa específica

2. **Calcular Automaticamente:**
   - Peso total do shipment (soma de caixas)
   - Volume total do shipment (soma de caixas)
   - Utilização de contêineres (% de capacidade usada)

3. **Relatórios:**
   - Resumo de embalagem por shipment
   - Eficiência de utilização de contêineres
   - Custos de embalagem

---

## 📊 Estrutura de Dados Resultante

```
Shipment
├── customer (Client)
├── proformaInvoices (ProformaInvoice) - via shipment_proforma_invoices
├── items (ShipmentItem)
│   └── product (Product)
│       ├── standard_packaging_quantity
│       ├── package_weight
│       ├── package_dimensions
│       └── package_volume
├── containers (ShipmentContainer)
│   ├── containerType (ContainerType)
│   └── items (ShipmentContainerItem)
│       └── packingBox (PackingBox)
│           ├── packingBoxType (PackingBoxType)
│           └── items (PackingBoxItem)
│               └── shipmentItem (ShipmentItem)
└── invoices (CommercialInvoice) - Criada automaticamente dos itens enviados
```

---

## 🔧 Comandos para Executar

Quando estiver pronto para aplicar as mudanças:

```bash
# Executar migrations
php artisan migrate

# Limpar cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Recompilar autoloader
composer dump-autoload

# Reiniciar servidor
valet restart
```

---

## 📝 Notas Importantes

1. **Customer é Obrigatório:** Todos os shipments devem ter um cliente associado
2. **Proforma vs Sales Invoice:** 
   - Proforma é usada para planejamento e cotação
   - Sales Invoice é criada automaticamente quando o shipment é confirmado
3. **Embalagem Padrão:** Produtos podem ter embalagem padrão definida
4. **Caixas Customizadas:** Para produtos sem embalagem ou com quantidade inferior, criar caixas específicas
5. **Container Types:** Evita digitar dimensões e capacidade toda vez

---

## 🎯 Benefícios

- ✅ Rastreamento de cliente para cada shipment
- ✅ Relacionamento com proforma invoices (não sales invoices)
- ✅ Reutilização de tipos de contêineres e caixas
- ✅ Embalagem padrão nos produtos
- ✅ Suporte para caixas customizadas
- ✅ Cálculos automáticos de peso e volume
- ✅ Melhor controle de embalagem e logística

---

## 📅 Status

- ✅ Fase 1: Análise (Completa)
- ✅ Fase 2: Migrations e Models (Completa)
- ✅ Fase 3: ShipmentForm (Completa)
- ⏳ Fase 4: Lógica de Embalagem (Próxima)
- ⏳ Fase 5: RelationManagers (Depois)
- ⏳ Fase 6: Validações e Cálculos (Depois)
