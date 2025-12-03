# Plano de Ação Detalhado: Fase 1 - Refatoração e Qualidade do Código

**Objetivo:** Melhorar a estrutura interna do sistema, reduzir a complexidade, aumentar a testabilidade e alinhar o projeto com as melhores práticas do ecossistema Laravel e Filament.

**Prioridade:** Alta. Esta fase é essencial para garantir a manutenibilidade e a escalabilidade do projeto a longo prazo.

---

## Tarefas e Estimativas

A fase será dividida nas seguintes tarefas principais:

| Tarefa | Descrição | Estimativa de Tempo | Prioridade |
| :--- | :--- | :--- | :--- |
| **1.1** | **Refatorar `Services` para `Actions`** | 2-3 dias | Alta |
| **1.2** | **Reduzir complexidade dos `Models`** | 3-4 dias | Alta |
| **1.3** | **Implementar `Repository Pattern`** | 2-3 dias | Média |
| **1.4** | **Melhorar cobertura de testes** | Contínuo | Alta |

---

### Tarefa 1.1: Refatorar `Services` para `Actions`

**Justificativa:** O Filament V3 e o ecossistema Laravel moderno favorecem o uso de classes de Ação (Actions) para encapsular lógicas de negócio específicas. Elas são mais fáceis de testar, reutilizar e entender do que classes de Serviço (Services) genéricas.

**Passos:**

1.  **Identificar `Services` a serem refatorados:**
    -   `RFQImportService`
    -   `SupplierQuoteImportService`
    -   `QuoteComparisonService`
    -   `FileUploadService` (já está bem estruturado, mas pode ser convertido para uma `Action` se apropriado)

2.  **Criar `Actions` correspondentes:**
    -   Para cada método público em um `Service`, criar uma classe de `Action` dedicada em `app/Actions/`.
    -   Exemplo: `RFQImportService::import()` se tornará `App\Actions\RFQ\ImportRfqAction`.

3.  **Mover a lógica e as validações:**
    -   Transferir a lógica de negócio do `Service` para o método `handle()` ou `__invoke()` da `Action`.
    -   Usar `Form Requests` ou o validador do Laravel dentro da `Action` para garantir que os dados de entrada são válidos.

4.  **Refatorar o código para usar as `Actions`:**
    -   Substituir as chamadas aos `Services` nos `Controllers`, `Livewire Components` e `Filament Resources` pelas novas `Actions`.

5.  **Criar testes unitários para cada `Action`:**
    -   Garantir que cada `Action` tenha testes isolados que cubram tanto os casos de sucesso quanto os de falha.

### Tarefa 1.2: Reduzir complexidade dos `Models` ("God Objects")

**Justificativa:** Os modelos `Order` e `Product` contêm uma quantidade significativa de lógica de negócio, o que os torna difíceis de manter e testar (padrão de anti-design "God Object").

**Passos:**

1.  **Analisar `Order.php` e `Product.php`:**
    -   Identificar métodos que contêm lógica de negócio complexa (ex: `generateOrderNumber()`, cálculos, etc.).

2.  **Extrair lógica para `Traits` ou `Actions`:**
    -   Lógica de geração de números ou códigos pode ser movida para `Actions` específicas (ex: `GenerateOrderNumberAction`).
    -   Cálculos complexos podem ser movidos para `Services` de domínio ou `Actions`.
    -   Lógica de estado (status) pode ser gerenciada com bibliotecas como `spatie/laravel-model-states`.

3.  **Mover `Query Scopes` para classes dedicadas:**
    -   Em vez de `scopes` locais no modelo, criar classes de `Scope` em `app/Models/Scopes/` (como já foi feito com `ClientOwnershipScope`).

### Tarefa 1.3: Implementar `Repository Pattern` (Opcional, mas recomendado)

**Justificativa:** O `Repository Pattern` desacopla a lógica de negócio da camada de acesso a dados, facilitando a troca de ORM (se necessário) e tornando os testes mais fáceis (é possível "mockar" o repositório).

**Passos:**

1.  **Criar `Interfaces` de Repositório:**
    -   Em `app/Interfaces/Repositories/`, criar interfaces como `OrderRepositoryInterface`, `ProductRepositoryInterface`, etc.

2.  **Criar `Implementações` Eloquent:**
    -   Em `app/Repositories/Eloquent/`, criar as classes que implementam as interfaces usando o Eloquent.

3.  **Registrar os `Bindings` no `AppServiceProvider`:**
    -   Fazer o "bind" das interfaces às suas implementações.

4.  **Refatorar o código para usar os Repositórios:**
    -   Injetar as interfaces dos repositórios nos `Controllers` e `Actions` em vez de usar o Eloquent diretamente.

### Tarefa 1.4: Melhorar Cobertura de Testes

**Justificativa:** Com a nova estrutura, precisamos garantir que a cobertura de testes seja alta para evitar regressões.

**Passos:**

1.  **Testes Unitários:**
    -   Escrever testes para todas as novas `Actions`.
    -   Escrever testes para os `Repositories` (se implementados).

2.  **Testes de Feature:**
    -   Revisar e adaptar os testes de feature existentes (`RFQWorkflowTest`, `QuoteComparisonTest`) para a nova arquitetura.
    -   Garantir que os fluxos de negócio críticos continuem funcionando como esperado.

---

## Próximos Passos

Sugiro começarmos pela **Tarefa 1.1: Refatorar `Services` para `Actions`**, pois é a que trará o maior benefício imediato em termos de organização e alinhamento com as práticas modernas do Filament.

Estou pronto para começar assim que você aprovar este plano.
