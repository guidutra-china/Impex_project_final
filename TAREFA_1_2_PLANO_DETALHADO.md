_# Plano de Ação Detalhado: Tarefa 1.2 - Reduzir Complexidade dos Models_

Este documento descreve o plano de ação para a **Tarefa 1.2**, focada em reduzir a complexidade dos "God Objects" `Order` e `Product`. O objetivo é melhorar a manutenibilidade, testabilidade e a clareza do código, alinhando-o com as melhores práticas de design de software.

---

## Análise da Complexidade

Após uma análise detalhada, identificamos que os models `Order` e `Product` acumularam uma quantidade significativa de lógica de negócio, o que os torna difíceis de manter e testar. 

| Model   | Linhas de Código (aprox.) | Responsabilidades Acumuladas                                                                 |
| :------ | :------------------------ | :------------------------------------------------------------------------------------------- |
| `Order`   | ~250                      | Geração de número de pedido, manipulação de status, cálculos, formatação de dados, queries. |
| `Product` | ~150                      | Lógica de pesquisa, manipulação de atributos, formatação de dados, relações complexas.     |

---

## Estratégia de Refatoração

A estratégia será baseada em extrair a lógica de negócio dos models para classes dedicadas, seguindo o **Princípio da Responsabilidade Única (SRP)**.

### 1. Refatoração do Model `Order`

O model `Order` será o foco principal, pois contém a lógica mais complexa.

**Passos:**

1.  **Extrair Geração de Número de Pedido:**
    *   **O quê:** O método `generateOrderNumber()` será extraído para uma nova classe `App\Services\Order\OrderNumberGenerator`.
    *   **Por quê:** A geração de números de pedido é uma responsabilidade distinta e pode se tornar mais complexa no futuro. Isolá-la facilita a manutenção e os testes.

2.  **Extrair Lógica de Status:**
    *   **O quê:** Os métodos de verificação de status (ex: `isConfirmed()`, `isDraft()`) e a lógica de transição de status serão movidos para um `Enum` com métodos (`OrderStatus.php`) ou para um Trait `HasStatus`.
    *   **Por quê:** Centraliza a lógica de status, tornando-a mais fácil de gerenciar e evitando a dispersão de condicionais pelo código.

3.  **Extrair Cálculos:**
    *   **O quê:** Métodos relacionados a cálculos (ex: `calculateTotalAmount()`, `calculateCommission()`) serão movidos para uma classe `App\Services\Order\OrderCalculator`.
    *   **Por quê:** A lógica de cálculo pode ser complexa e volátil. Isolá-la em uma classe dedicada facilita a validação e a modificação das regras de negócio.

4.  **Usar Scopes para Queries:**
    *   **O quê:** Queries complexas serão movidas para `local scopes` dentro do model `Order`.
    *   **Por quê:** Melhora a legibilidade e a reutilização de queries, tornando o código dos controllers e resources mais limpo.

### 2. Refatoração do Model `Product`

O model `Product` tem menos lógica de negócio, mas pode ser melhorado.

**Passos:**

1.  **Extrair Lógica de Pesquisa:**
    *   **O quê:** A lógica de pesquisa complexa será movida para `local scopes` ou para um `ProductRepository` se a complexidade aumentar.
    *   **Por quê:** Desacopla a lógica de busca do model, permitindo que seja reutilizada e testada de forma independente.

2.  **Usar Traits para Comportamentos Compartilhados:**
    *   **O quê:** Se houver comportamentos que possam ser compartilhados com outros models (ex: formatação de preço), eles serão extraídos para `Traits`.
    *   **Por quê:** Promove a reutilização de código e evita a duplicação.

---

## Plano de Implementação

| Tarefa                                     | Descrição                                                                                             | Estimativa | Prioridade |
| :----------------------------------------- | :---------------------------------------------------------------------------------------------------- | :--------- | :--------- |
| **1.2.1: Refatorar `Order` Model**         |                                                                                                       | **~4 horas** | Alta       |
| _1.2.1.1: Criar `OrderNumberGenerator`_    | Mover a lógica de `generateOrderNumber()` para a nova classe e atualizar o model `Order`.             | 1 hora     | Alta       |
| _1.2.1.2: Refatorar Lógica de Status_    | Mover a lógica de status para um `Enum` com métodos ou um `Trait`.                                    | 1.5 horas  | Alta       |
| _1.2.1.3: Criar `OrderCalculator`_        | Mover a lógica de cálculos para a nova classe.                                                        | 1 hora     | Média      |
| _1.2.1.4: Criar Testes Unitários_        | Criar testes para `OrderNumberGenerator`, lógica de status e `OrderCalculator`.                     | 0.5 horas  | Alta       |
| **1.2.2: Refatorar `Product` Model**       |                                                                                                       | **~2 horas** | Média      |
| _1.2.2.1: Implementar Scopes de Query_    | Mover a lógica de pesquisa para `local scopes` no model `Product`.                                  | 1 hora     | Média      |
| _1.2.2.2: Extrair Traits (se aplicável)_ | Identificar e extrair comportamentos compartilhados para `Traits`.                                    | 0.5 horas  | Baixa      |
| _1.2.2.3: Criar Testes Unitários_        | Criar testes para os `scopes` e `traits` implementados.                                               | 0.5 horas  | Média      |

---

## Próximos Passos

Sugiro começarmos pela **Tarefa 1.2.1**, focando na refatoração do model `Order`, que trará os maiores benefícios imediatos para a qualidade do código.

Estou pronto para começar a implementação assim que você aprovar este plano.
