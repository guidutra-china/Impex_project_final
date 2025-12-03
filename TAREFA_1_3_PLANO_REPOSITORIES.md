# Tarefa 1.3: Implementar Repository Pattern

## Objetivo
Implementar o Repository Pattern para os models mais importantes do projeto, criando uma camada de abstração entre a lógica de negócio e o acesso a dados.

## Models Prioritários

### 1. OrderRepository (Prioridade: CRÍTICA)
**Por que:** Orders é o core do negócio, com muitas consultas complexas

**Métodos:**
- `findById($id): ?Order` - Encontrar ordem por ID
- `getPendingOrdersByCustomer($customerId): Collection` - Ordens pendentes
- `getCompletedOrders($limit = 10): Collection` - Ordens completadas
- `getOrdersByStatus($status): Collection` - Ordens por status
- `getOrdersWithItems($customerId): Collection` - Ordens com itens
- `create($data): Order` - Criar nova ordem
- `update($id, $data): bool` - Atualizar ordem
- `delete($id): bool` - Deletar ordem
- `getRecentOrders($days = 30): Collection` - Ordens recentes
- `searchOrders($query): Collection` - Buscar ordens

### 2. ProductRepository (Prioridade: CRÍTICA)
**Por que:** Products é usado em muitos contextos (BOM, Orders, Quotes)

**Métodos:**
- `findById($id): ?Product` - Encontrar produto por ID
- `findBySku($sku): ?Product` - Encontrar por SKU
- `getActiveProducts(): Collection` - Produtos ativos
- `getProductsByCategory($categoryId): Collection` - Por categoria
- `getProductsWithBom(): Collection` - Com BOM
- `searchProducts($query): Collection` - Buscar produtos
- `create($data): Product` - Criar produto
- `update($id, $data): bool` - Atualizar produto
- `delete($id): bool` - Deletar produto
- `getProductsBySupplier($supplierId): Collection` - Por fornecedor

### 3. ClientRepository (Prioridade: ALTA)
**Por que:** Clients são essenciais para Orders e RFQs

**Métodos:**
- `findById($id): ?Client` - Encontrar cliente por ID
- `getActiveClients(): Collection` - Clientes ativos
- `getClientsByCountry($country): Collection` - Por país
- `searchClients($query): Collection` - Buscar clientes
- `create($data): Client` - Criar cliente
- `update($id, $data): bool` - Atualizar cliente
- `delete($id): bool` - Deletar cliente
- `getClientOrders($clientId): Collection` - Ordens do cliente

### 4. SupplierRepository (Prioridade: ALTA)
**Por que:** Suppliers são importantes para sourcing e quotes

**Métodos:**
- `findById($id): ?Supplier` - Encontrar fornecedor por ID
- `getActiveSuppliers(): Collection` - Fornecedores ativos
- `getSuppliersByCountry($country): Collection` - Por país
- `searchSuppliers($query): Collection` - Buscar fornecedores
- `create($data): Supplier` - Criar fornecedor
- `update($id, $data): bool` - Atualizar fornecedor
- `delete($id): bool` - Deletar fornecedor
- `getSupplierProducts($supplierId): Collection` - Produtos do fornecedor

## Arquitetura

### Estrutura de Pastas
```
app/
├── Repositories/
│   ├── Contracts/
│   │   └── RepositoryInterface.php (interface base)
│   ├── BaseRepository.php (classe base abstrata)
│   ├── OrderRepository.php
│   ├── ProductRepository.php
│   ├── ClientRepository.php
│   └── SupplierRepository.php
```

### RepositoryInterface
Define a interface padrão que todos os repositories devem implementar:
```php
interface RepositoryInterface {
    public function findById($id);
    public function all();
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
}
```

### BaseRepository
Classe abstrata que implementa métodos comuns:
```php
abstract class BaseRepository implements RepositoryInterface {
    protected $model;
    
    public function findById($id) { ... }
    public function all() { ... }
    public function create(array $data) { ... }
    public function update($id, array $data) { ... }
    public function delete($id) { ... }
}
```

### Repositories Específicos
Herdam de BaseRepository e adicionam métodos específicos do domínio.

## Benefícios

| Aspecto | Benefício |
|---------|-----------|
| **Manutenibilidade** | Lógica de acesso centralizada |
| **Testabilidade** | Fácil mockar repositories em testes |
| **Flexibilidade** | Trocar banco de dados sem afetar lógica |
| **Reutilização** | Mesmos métodos em múltiplos contextos |
| **Escalabilidade** | Fácil adicionar novos repositories |
| **Profissionalismo** | Padrão amplamente usado em aplicações enterprise |

## Impacto Esperado

### Antes (Sem Repository)
```php
// Em Controllers, Services, Actions
$orders = Order::where('status', 'pending')
    ->where('customer_id', $customerId)
    ->with('items', 'customer')
    ->orderBy('created_at', 'desc')
    ->get();
```

### Depois (Com Repository)
```php
// Em Controllers, Services, Actions
$orders = app(OrderRepository::class)->getPendingOrdersByCustomer($customerId);
```

## Implementação

### Fase 1: Criar Base (2 horas)
1. RepositoryInterface
2. BaseRepository

### Fase 2: Implementar Repositories (4 horas)
1. OrderRepository
2. ProductRepository
3. ClientRepository
4. SupplierRepository

### Fase 3: Testes (3 horas)
1. Testes para cada repository
2. Testes de integração

### Fase 4: Integração (2 horas)
1. Registrar repositories no container
2. Atualizar código existente para usar repositories

## Tempo Total Estimado
- Implementação: 11 horas
- Testes: 3 horas
- Total: 14 horas

## Prioridade
**Alta** - Melhora significativa na arquitetura e manutenibilidade
