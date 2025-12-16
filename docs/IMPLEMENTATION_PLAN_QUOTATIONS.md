# Plano de Implementação: Módulo de Cotações para Cliente (Quotations)

**Para:** [Nome do Usuário/Equipe]

**De:** Manus AI

**Data:** 16 de Dezembro de 2025

**Assunto:** Passos Detalhados para a Implementação da Etapa "Quotations" e Gestão de Comissões

## 1. Análise e Recomendações

Após uma análise aprofundada da sua proposta e do sistema Impex, a inclusão da etapa **"Quotations"** é altamente recomendada. Ela representa uma evolução estratégica que aumenta a transparência e a satisfação do cliente.

### 1.1. Gestão de Comissões: Embutida vs. Separada

O sistema atual já possui uma base sólida para a gestão de comissões, com os campos `commission_type` e `commission_percent` nos modelos `Order` e `SupplierQuote`. A questão central é como apresentar essa comissão ao cliente final na nova etapa de "Quotations".

| Abordagem               | Descrição                                                                                                                                                                                                                                                           | Vantagens                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  - **Transparência Total:** O cliente vê exatamente o valor do produto e o valor da sua comissão.
- **Justifica o Valor:** Deixa claro o custo do seu serviço de sourcing, fortalecendo a percepção de valor.
- **Flexibilidade:** Permite negociar a comissão separadamente do preço do produto.

| **Comissão Embutida** | O preço apresentado ao cliente já inclui a sua comissão. O cliente não vê a separação entre o custo do produto e o seu serviço. | - **Simplicidade:** O cliente lida com um único preço final, facilitando a decisão.
- **Competitividade:** Evita que o cliente questione o percentual da comissão, focando apenas no preço total.
- **Padrão de Mercado:** É a abordagem mais comum em trading e sourcing para evitar atritos.

**Recomendação:**

**Implementar AMBAS as opções e permitir que o usuário escolha por `Order` (pedido).** O sistema já está preparado para isso com o campo `commission_type`. A nova interface de "Quotations" deve respeitar essa escolha:

-   Se `commission_type` for **`embedded`**, a página do cliente mostrará apenas o preço final (com comissão já inclusa).
-   Se `commission_type` for **`separate`**, a página do cliente mostrará o preço do produto, o percentual/valor da comissão e o preço total.

Esta abordagem híbrida oferece máxima flexibilidade para se adaptar a diferentes clientes e estratégias de negociação.

## 2. Plano de Implementação Detalhado

Para garantir uma implementação robusta e modular, sugiro os seguintes passos:

### Fase 1: Estrutura de Dados

1.  **Criar a Migration para `customer_quotes`:**

    ```bash
    php artisan make:migration create_customer_quotes_table
    ```

    **Estrutura da Tabela:**

    ```php
    Schema::create("customer_quotes", function (Blueprint $table) {
        $table->id();
        $table->foreignId("order_id")->constrained("orders");
        $table->string("quote_number")->unique();
        $table->enum("status", ["draft", "sent", "approved", "rejected", "expired"])->default("draft");
        $table->string("public_token")->unique()->nullable(); // Token para acesso público
        $table->timestamp("sent_at")->nullable();
        $table->timestamp("approved_at")->nullable();
        $table->timestamp("expires_at")->nullable();
        $table->foreignId("approved_by_user_id")->nullable()->constrained("users");
        $table->text("customer_notes")->nullable();
        $table->timestamps();
    });
    ```

2.  **Criar a Migration para `customer_quote_items`:**

    ```bash
    php artisan make:migration create_customer_quote_items_table
    ```

    **Estrutura da Tabela:**

    ```php
    Schema::create("customer_quote_items", function (Blueprint $table) {
        $table->id();
        $table->foreignId("customer_quote_id")->constrained("customer_quotes")->onDelete("cascade");
        $table->foreignId("supplier_quote_id")->constrained("supplier_quotes");
        $table->string("display_name"); // Ex: "Opção A", "Fornecedor 1"
        $table->integer("price_before_commission");
        $table->integer("commission_amount");
        $table->integer("price_after_commission");
        $table->string("delivery_time");
        $table->text("notes")->nullable();
        $table->boolean("is_selected_by_customer")->default(false);
        $table->timestamps();
    });
    ```

3.  **Criar os Models `CustomerQuote` e `CustomerQuoteItem`:**

    ```bash
    php artisan make:model CustomerQuote
    php artisan make:model CustomerQuoteItem
    ```

    -   Configurar os relacionamentos (`belongsTo`, `hasMany`).

### Fase 2: Lógica de Negócio e Geração

1.  **Criar um `CustomerQuoteService`:**
    -   Este serviço conterá a lógica para criar uma `CustomerQuote` a partir de um `Order` e uma seleção de `SupplierQuote`.
    -   Método `create(Order $order, array $supplierQuoteIds)`: Itera sobre as `SupplierQuote` selecionadas, calcula os preços com base no `commission_type` do `Order` e cria os `CustomerQuoteItem` correspondentes.

2.  **Desenvolver a Interface de Geração (Filament):**
    -   Na página de visualização do `Order` (`ViewOrder`), adicionar uma `Action` chamada **"Gerar Cotação para Cliente"**.
    -   Esta ação abrirá um modal com um `CheckboxList` para selecionar as `SupplierQuote` disponíveis.
    -   Permitir a edição do `display_name` para cada `SupplierQuote` selecionada (para anonimização).
    -   Ao confirmar, chamar o `CustomerQuoteService->create()`.

### Fase 3: Interface do Cliente e Aprovação

1.  **Criar um Controller `PublicQuoteController`:**
    -   Método `show(string $token)`: Busca a `CustomerQuote` pelo `public_token` e exibe a página de visualização.

2.  **Criar a View Pública (`public-quote.blade.php`):**
    -   Esta página não usará o layout do Filament.
    -   Deve ser uma página limpa, com a marca da sua empresa.
    -   Exibirá as opções (`CustomerQuoteItem`) em um formato de tabela comparativa.
    -   Respeitará o `commission_type`: se for `separate`, mostrará a coluna de comissão.
    -   Para cada opção, haverá um botão "Selecionar esta opção".

3.  **Implementar a Lógica de Aprovação:**
    -   Ao clicar em "Selecionar", uma rota (ex: `/quotes/{token}/approve/{itemId}`) será chamada.
    -   O `PublicQuoteController` registrará a seleção (`is_selected_by_customer = true`), atualizará o status da `CustomerQuote` para `approved` e o `selected_quote_id` no `Order` original.
    -   Enviar uma notificação por e-mail para a equipe interna informando sobre a aprovação.

### Fase 4: Integração com o Fluxo Existente

1.  **Atualizar o Status do `Order`:**
    -   Após o envio da `CustomerQuote`, o status do `Order` deve mudar para `pending_customer_approval`.
    -   Após a aprovação, o status muda para `customer_approved`.

2.  **Automatizar a Criação da `ProformaInvoice`:**
    -   Na página do `Order`, após o cliente aprovar a cotação, a ação "Gerar Proforma Invoice" deve agora usar os dados da `SupplierQuote` selecionada pelo cliente para pré-preencher a PI, tornando o processo mais rápido e à prova de erros.

## 3. Próximos Passos

Recomendo iniciar pela **Fase 1 (Estrutura de Dados)** para construir a base da nova funcionalidade. Posso começar a criar as migrations e os modelos imediatamente.

Aguardo a sua confirmação para dar início ao desenvolvimento.
