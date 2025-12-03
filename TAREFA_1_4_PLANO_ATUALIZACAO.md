# Tarefa 1.4: Plano de Ação - Atualizar Código para Usar Repositories

## Objetivo

O objetivo desta tarefa é refatorar o código existente da aplicação para utilizar a camada de `Repository` recém-implementada, desacoplando a lógica de negócio do acesso direto ao banco de dados via Eloquent. Isso resultará em um código mais limpo, testável e de fácil manutenção.

## Análise de Impacto

A análise inicial identificou mais de 40 arquivos que utilizam diretamente os models `Order`, `Product`, `Client` e `Supplier`. A atualização será focada nas seguintes áreas:

- **Filament Resources:** A principal interface de administração.
- **Actions:** Onde a lógica de negócio está encapsulada.
- **Services:** Lógica de negócio que ainda não foi refatorada para Actions.
- **Livewire Components (Widgets):** Componentes de dashboard e UI.

## Estratégia de Implementação

A implementação será dividida em fases, começando pelos componentes mais críticos e de maior impacto.

### Fase 1: Atualização dos Filament Resources (Prioridade Alta)

| Recurso | Repositório a ser Injetado | Métodos a serem Atualizados |
|---|---|---|
| `OrderResource` | `OrderRepository` | `getEloquentQuery`, `resolveRecord`, `getRelations` |
| `ProductResource` | `ProductRepository` | `getEloquentQuery`, `resolveRecord`, `getRelations` |
| `ClientResource` | `ClientRepository` | `getEloquentQuery`, `resolveRecord` |
| `SupplierResource` | `SupplierRepository` | `getEloquentQuery`, `resolveRecord` |

**Passos:**
1. Injetar o repositório correspondente no construtor de cada `Resource`.
2. Substituir as chamadas diretas ao Eloquent (`Order::query()`, `Product::where()`, etc.) por chamadas aos métodos do repositório (`$this->orderRepository->all()`, `$this->productRepository->getActiveProducts()`, etc.).
3. Atualizar as `Actions` dentro dos resources para usar os repositórios.

### Fase 2: Atualização das Actions (Prioridade Alta)

| Action | Repositório a ser Injetado |
|---|---|
| `ImportRfqAction` | `OrderRepository`, `ProductRepository` |
| `CompareQuotesAction` | `OrderRepository`, `SupplierRepository` |
| `ImportSupplierQuotesAction` | `OrderRepository`, `SupplierRepository` |
| `UploadFileAction` | - (Nenhum) |

**Passos:**
1. Injetar os repositórios necessários no construtor de cada `Action`.
2. Substituir todas as chamadas diretas ao Eloquent por métodos dos repositórios.

### Fase 3: Atualização dos Services e Widgets (Prioridade Média)

| Componente | Repositório a ser Injetado |
|---|---|
| `QuoteComparisonService` | `OrderRepository`, `SupplierRepository` |
| `RFQImportService` | `OrderRepository`, `ProductRepository` |
| `ProjectExpensesWidget` | `OrderRepository` |
| `RfqStatsWidget` | `OrderRepository` |

**Passos:**
1. Injetar os repositórios necessários.
2. Substituir as chamadas diretas ao Eloquent.

## Plano de Execução

1. **Registrar o `RepositoryServiceProvider`:** Adicionar `App\Providers\RepositoryServiceProvider::class` ao `config/app.php`.
2. **Atualizar `OrderResource`:** Começar pelo resource mais complexo.
3. **Atualizar `ProductResource`:** Continuar com o segundo resource mais complexo.
4. **Atualizar `ClientResource` e `SupplierResource`:** Finalizar os resources.
5. **Atualizar as `Actions`:** Refatorar a lógica de negócio.
6. **Atualizar `Services` e `Widgets`:** Finalizar a integração.
7. **Executar Testes:** Rodar todos os testes de integração para garantir que a aplicação continua funcionando como esperado.

## Cronograma Estimado

- **Fase 1 (Resources):** 2-3 horas
- **Fase 2 (Actions):** 1-2 horas
- **Fase 3 (Services/Widgets):** 1 hora
- **Testes:** 1 hora

**Total Estimado:** 5-7 horas

Estou pronto para iniciar a execução. Começarei registrando o `RepositoryServiceProvider`.
