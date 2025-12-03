# Tarefa 1.7: Refatorar Relation Managers para usar Repositories

## Objetivo

Refatorar todos os 22 Relation Managers da aplicação para injetar e utilizar os Repositories, seguindo o padrão consistente implementado em Pages, Actions e Widgets.

---

## Análise de Relation Managers

### Total Encontrado: 22 Relation Managers

#### **Alta Prioridade (8)** - Lógica complexa, múltiplas operações

1. **Orders/ItemsRelationManager**
   - Gerencia itens de pedido
   - Necessário: OrderRepository, ProductRepository
   - Operações: Create, Edit, Delete items

2. **Orders/SupplierQuotesRelationManager**
   - Gerencia cotações de fornecedores
   - Necessário: SupplierQuoteRepository
   - Operações: View, Delete quotes

3. **Orders/SuppliersToQuoteRelationManager**
   - Gerencia fornecedores para cotação
   - Necessário: SupplierRepository
   - Operações: Add, Remove suppliers

4. **ProformaInvoice/ItemsRelationManager**
   - Gerencia itens de proforma
   - Necessário: ProformaInvoiceRepository
   - Operações: Create, Edit, Delete items

5. **SupplierQuotes/ItemsRelationManager**
   - Gerencia itens de cotação
   - Necessário: SupplierQuoteRepository
   - Operações: Create, Edit, Delete items

6. **Products/BomItemsRelationManager**
   - Gerencia itens de BOM
   - Necessário: ProductRepository
   - Operações: Create, Edit, Delete BOM items

7. **Products/FeaturesRelationManager**
   - Gerencia features de produtos
   - Necessário: ProductRepository
   - Operações: Create, Edit, Delete features

8. **Shipments/ItemsRelationManager**
   - Gerencia itens de envio
   - Necessário: ShipmentRepository (novo)
   - Operações: Create, Edit, Delete items

#### **Média Prioridade (8)** - Lógica moderada

9. **Products/BomVersionsRelationManager**
10. **Products/CostHistoryRelationManager**
11. **Products/DocumentsRelationManager**
12. **Products/PhotosRelationManager**
13. **Products/WhatIfScenariosRelationManager**
14. **Suppliers/DocumentsRelationManager**
15. **Clients/ClientcontactsRelationManager**
16. **FinancialPayments/AllocationsRelationManager**

#### **Baixa Prioridade (6)** - Lógica simples

17. **Categories/CategoryFeaturesRelationManager**
18. **PaymentTerms/StagesRelationManager**
19. **Shipments/InvoicesRelationManager**
20. **Shipments/PackingBoxesRelationManager**
21. **FinancialTransactions/AttachmentsRelationManager** (se existir)
22. Outros...

---

## Repositories Necessários

### Já Existentes (12)
- ✅ OrderRepository
- ✅ ProductRepository
- ✅ ClientRepository
- ✅ SupplierRepository
- ✅ FinancialTransactionRepository
- ✅ ProformaInvoiceRepository
- ✅ SupplierQuoteRepository
- ✅ SalesInvoiceRepository
- ✅ PurchaseOrderRepository
- ✅ DocumentRepository
- ✅ RFQRepository
- ✅ EventRepository

### Novos Necessários (2)
1. **ShipmentRepository** - Para gerenciar envios
2. **CategoryRepository** - Para gerenciar categorias

---

## Padrão de Refatoração

### Antes (Sem Repository)

```php
class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('product_id')
                ->relationship('product', 'name')
                ->required(),
            // ... mais campos
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                OrderItem::query()
                    ->where('order_id', $this->getOwnerRecord()->id)
            )
            ->columns([
                // ... colunas
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
```

### Depois (Com Repository)

```php
class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected OrderRepository $orderRepository;
    protected ProductRepository $productRepository;

    public function __construct()
    {
        parent::__construct();
        $this->orderRepository = app(OrderRepository::class);
        $this->productRepository = app(ProductRepository::class);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('product_id')
                ->options($this->productRepository->getSelectOptions())
                ->required(),
            // ... mais campos
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                $this->orderRepository->getItemsQuery($this->getOwnerRecord()->id)
            )
            ->columns([
                // ... colunas
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
```

---

## Plano de Implementação

### Fase 1: Repositories Novos (2-3 horas)
1. Criar ShipmentRepository
2. Criar CategoryRepository
3. Registrar no RepositoryServiceProvider

### Fase 2: Relation Managers de Alta Prioridade (6-8 horas)
1. Orders/ItemsRelationManager
2. Orders/SupplierQuotesRelationManager
3. Orders/SuppliersToQuoteRelationManager
4. ProformaInvoice/ItemsRelationManager
5. SupplierQuotes/ItemsRelationManager
6. Products/BomItemsRelationManager
7. Products/FeaturesRelationManager
8. Shipments/ItemsRelationManager

### Fase 3: Relation Managers de Média Prioridade (4-5 horas)
- 8 Relation Managers

### Fase 4: Relation Managers de Baixa Prioridade (2-3 horas)
- 6 Relation Managers

---

## Métodos Necessários nos Repositories

### OrderRepository
```php
public function getItemsQuery(int $orderId): Builder
public function getSupplierQuotesQuery(int $orderId): Builder
public function getSuppliersToQuoteQuery(int $orderId): Builder
```

### ProductRepository
```php
public function getSelectOptions(): array
public function getBomItemsQuery(int $productId): Builder
public function getFeaturesQuery(int $productId): Builder
```

### ShipmentRepository (novo)
```php
public function getItemsQuery(int $shipmentId): Builder
public function getInvoicesQuery(int $shipmentId): Builder
public function getPackingBoxesQuery(int $shipmentId): Builder
```

---

## Benefícios Esperados

- ✅ Centralização de lógica de dados
- ✅ Código mais limpo e legível
- ✅ Fácil de testar com mocks
- ✅ Reutilização de métodos
- ✅ Melhor manutenibilidade
- ✅ Logging e auditoria centralizada
- ✅ Padrão consistente em 100% da aplicação

---

## Estimativa de Esforço

- **Fase 1:** 2-3 horas
- **Fase 2:** 6-8 horas
- **Fase 3:** 4-5 horas
- **Fase 4:** 2-3 horas
- **Total:** 14-19 horas

---

## Próximas Etapas

1. Criar Repositories novos (ShipmentRepository, CategoryRepository)
2. Adicionar métodos necessários aos Repositories existentes
3. Refatorar Relation Managers de alta prioridade
4. Refatorar Relation Managers de média prioridade
5. Refatorar Relation Managers de baixa prioridade
6. Criar testes de integração

---

**Data:** 04 de Dezembro de 2025
**Status:** Pronto para Implementação
