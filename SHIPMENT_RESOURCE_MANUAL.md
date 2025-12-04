# Manual de Uso do Recurso Shipment

Este manual detalha como usar o recurso **Shipment** no painel de administração do Impex, incluindo suas funcionalidades, relacionamentos e fluxo de trabalho.

---

## 1. Visão Geral

O recurso **Shipment** é o coração da logística no Impex. Ele representa um envio, seja de exportação (outbound) ou importação (inbound), e centraliza todas as informações relacionadas, desde os itens e faturas até os contêineres e caixas de embalagem.

### 1.1. Estrutura do Recurso

O recurso é dividido em:
- **Formulário Principal:** Informações básicas, detalhes de envio, datas, finanças e medições.
- **Tabela de Listagem:** Visão geral de todos os shipments com filtros e ações rápidas.
- **Páginas de Ação:** Criar, editar, visualizar e listar shipments.
- **Gerenciadores de Relacionamento (RelationManagers):**
  - Invoices (Faturas)
  - Items (Itens)
  - Packing Boxes (Caixas de Embalagem)
  - Shipment Containers (Contêineres)

### 1.2. Fluxo de Trabalho Básico

1. **Criar um novo Shipment:**
   - Preencher informações básicas (tipo, status, método de envio).
   - Salvar como rascunho (draft).

2. **Adicionar Faturas (Invoices):**
   - Anexar uma ou mais faturas de venda (Sales Invoices) ao shipment.
   - Os itens das faturas são automaticamente adicionados ao shipment.

3. **Gerenciar Itens (Items):**
   - Visualizar todos os itens de todas as faturas anexadas.
   - Ajustar quantidades a serem enviadas (se necessário).

4. **Criar Caixas de Embalagem (Packing Boxes):**
   - Criar caixas e atribuir itens a elas.
   - O sistema calcula automaticamente o peso e volume de cada caixa.

5. **Criar Contêineres (Shipment Containers):**
   - Criar contêineres e atribuir caixas a eles.
   - Selar (Seal) e desselar (Unseal) contêineres.

6. **Confirmar e Rastrear:**
   - Mudar o status do shipment (e.g., de `draft` para `confirmed`).
   - Adicionar informações de rastreamento (tracking number, carrier).

---

## 2. Formulário do Shipment

O formulário é dividido em seções colapsáveis para facilitar a navegação.

### 2.1. Basic Information

| Campo | Descrição |
|---|---|
| **Shipment Number** | Gerado automaticamente (e.g., `SHP-2025-00001`). Não pode ser editado. |
| **Type** | `Outbound (Export)` ou `Inbound (Import)`. |
| **Status** | Status atual do shipment (e.g., `Draft`, `Confirmed`, `In Transit`). |

### 2.2. Shipping Details

| Campo | Descrição |
|---|---|
| **Shipping Method** | Método de envio (Air, Sea, Land, etc.). |
| **Carrier** | Transportadora (e.g., DHL, Maersk). |
| **Tracking Number** | Número de rastreamento. |
| **Container Number** | Número do contêiner principal (se houver). |
| **Vessel/Flight Name** | Nome do navio ou voo. |
| **Voyage/Flight Number** | Número da viagem ou voo. |
| **Origin/Destination Address** | Endereços de origem e destino. |

### 2.3. Dates

Seção para gerenciar todas as datas relevantes, desde a data do envio até as datas estimadas e reais de partida/chegada.

### 2.4. Financial

| Campo | Descrição |
|---|---|
| **Shipping Cost** | Custo do frete. |
| **Insurance Cost** | Custo do seguro. |
| **Currency** | Moeda dos custos. |
| **Incoterm** | Termos Internacionais de Comércio (e.g., FOB, CIF). |
| **Payment Terms** | Termos de pagamento. |

### 2.5. Measurements (Auto-calculado)

Esta seção é **somente leitura** e exibe totais calculados automaticamente a partir dos itens e caixas:
- **Total Items:** Número de itens únicos.
- **Total Quantity:** Soma de todas as quantidades de itens.
- **Total Weight (kg):** Peso total.
- **Total Volume (m³):** Volume total.
- **Total Boxes:** Número de caixas de embalagem.
- **Packing Status:** `Not Packed`, `Partially Packed`, ou `Fully Packed`.

---

## 3. Gerenciadores de Relacionamento

Estes são os componentes mais importantes na página de edição de um shipment.

### 3.1. Invoices (Faturas)

- **Funcionalidade:** Anexar faturas de venda existentes ao shipment.
- **Como usar:**
  1. Clique em **Attach Invoice**.
  2. Selecione uma ou mais faturas da lista.
  3. Clique em **Attach**.
- **Lógica:**
  - A lista mostra apenas faturas que **não estão canceladas** e que **têm itens**.
  - Ao anexar uma fatura, todos os seus itens são automaticamente adicionados à relação de itens do shipment.

### 3.2. Items (Itens)

- **Funcionalidade:** Visualizar e gerenciar os itens do shipment.
- **Como usar:**
  - A lista é populada automaticamente a partir das faturas anexadas.
  - Você pode editar a quantidade de cada item a ser incluída no shipment.
- **Lógica:**
  - O `CreateAction` usa um `ShipmentService` para adicionar itens, garantindo que a lógica de negócio seja aplicada.

### 3.3. Packing Boxes (Caixas de Embalagem)

- **Funcionalidade:** Criar e gerenciar caixas de embalagem.
- **Como usar:**
  1. Clique em **Create Box**.
  2. Atribua um número à caixa e adicione itens a ela.
  3. O sistema calcula o peso e volume da caixa.
- **Lógica:**
  - O `CreateAction` usa um `PackingService` para criar a caixa, garantindo que o peso e volume sejam calculados corretamente.

### 3.4. Shipment Containers (Contêineres)

- **Funcionalidade:** Criar, gerenciar, selar e desselar contêineres.
- **Como usar:**
  1. Clique em **Add Container**.
  2. Preencha as informações do contêiner.
  3. Use as ações **Seal** e **Unseal** para gerenciar o status do selo.
- **Lógica:**
  - O `CreateAction` usa o callback `using()` para definir o `created_by` e criar o registro.
  - As ações `Seal` e `Unseal` são custom actions que modificam o status do contêiner e registram o número do selo.

---

## 4. Lógica Chave e Padrões

### 4.1. Separação de Lógica

- **Resources (Recursos):** Definição da estrutura (formulários, tabelas, relações).
- **Repositories (Repositórios):** Lógica de acesso a dados (queries complexas).
- **Services (Serviços):** Lógica de negócio (cálculos, validações).
- **Custom Actions (Ações Customizadas):** Lógica de ações específicas (e.g., selar contêiner).

### 4.2. Padrões do Filament V4

- **Lazy Loading em RelationManagers:**
  - Propriedades como repositories são injetadas no `mount()`.
  - Para evitar erros de propriedade não inicializada, usamos lazy loading com o null coalescing operator:
    ```php
    ($this->repository ?? app(ShipmentRepository::class))->getItemsQuery(...)
    ```

- **Callbacks de Ações:**
  - `using()` é o método preferido para customizar a criação de registros em `CreateAction` de RelationManagers.
  - `mutateFormDataBeforeCreate()` é usado em `CreateAction` de Resources, mas não em RelationManagers.
  - `before()` é um callback mais genérico, executado antes da ação principal.

- **Estrutura de Ações em Tabelas:**
  - `->headerActions([...])` para ações no cabeçalho (e.g., criar).
  - `->recordActions([...])` para ações por registro (e.g., editar, deletar).
  - `->toolbarActions([...])` para ações em massa.

---

## 5. Conclusão

O recurso Shipment é um exemplo robusto de como usar o Filament V4 para criar interfaces complexas com lógica de negócio encapsulada. A separação de responsabilidades e o uso correto dos padrões do Filament garantem um código limpo, manutenível e escalável.

Espero que este manual ajude a entender melhor a lógica empregada! Se tiver mais alguma dúvida, pode perguntar.
