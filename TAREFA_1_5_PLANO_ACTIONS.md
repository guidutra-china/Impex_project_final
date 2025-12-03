# Tarefa 1.5: Atualização de Filament Actions para Usar Repositories

## Objetivo

Refatorar todos os Filament Actions para injetar e utilizar os Repositories, garantindo que todas as operações de dados sejam centralizadas e reutilizáveis.

## Status: 📋 PLANEJADO

---

## Análise Inicial

### Actions Encontradas

O projeto contém Actions em:
- `app/Filament/Actions/` - Actions globais/compartilhadas
- `app/Filament/Resources/*/Actions/` - Actions específicas de cada Resource
- Inline Actions em Resources e Pages

### Tipos de Actions a Refatorar

1. **CRUD Actions**
   - CreateAction
   - EditAction
   - DeleteAction
   - ViewAction

2. **Custom Actions**
   - RFQ-related actions
   - Quote comparison actions
   - Document generation actions
   - Financial transaction actions

3. **Bulk Actions**
   - Operações em múltiplos registros
   - Ações em massa

---

## Estrutura de Implementação

### Padrão Recomendado para Actions

```php
<?php

namespace App\Filament\Actions;

use App\Repositories\OrderRepository;
use Filament\Actions\Action;

class CreateOrderAction extends Action
{
    protected OrderRepository $orderRepository;

    public static function getDefaultName(): ?string
    {
        return 'create_order';
    }

    public function setUp(): void
    {
        parent::setUp();
        
        $this->orderRepository = app(OrderRepository::class);
        
        $this
            ->action(function (array $data) {
                $this->handleCreateOrder($data);
            });
    }

    protected function handleCreateOrder(array $data): void
    {
        try {
            // Usar repository para criar
            $order = $this->orderRepository->create($data);
            
            // Notificação de sucesso
            \Filament\Notifications\Notification::make()
                ->success()
                ->title('Pedido criado com sucesso')
                ->send();
                
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('Erro ao criar pedido')
                ->body($e->getMessage())
                ->send();
        }
    }
}
```

---

## Fases de Implementação

### Fase 1: Actions de RFQ (Prioridade Alta)

**Arquivos a refatorar:**
- `app/Filament/Resources/Orders/Actions/*` - Actions relacionadas a RFQ
- Ações de criação e edição de RFQ
- Ações de comparação de cotações
- Ações de geração de documentos

**Métodos do Repository a utilizar:**
- `OrderRepository::create()`
- `OrderRepository::update()`
- `OrderRepository::findByIdWithRelations()`
- `OrderRepository::getRFQWithSupplierQuotes()`

### Fase 2: Actions de Produtos (Prioridade Alta)

**Arquivos a refatorar:**
- `app/Filament/Resources/Products/Actions/*`
- Ações de duplicação de produtos
- Ações de atualização de custos
- Ações de gerenciamento de BOM

**Métodos do Repository a utilizar:**
- `ProductRepository::create()`
- `ProductRepository::update()`
- `ProductRepository::findByIdWithRelations()`
- `ProductRepository::getProductsWithBOM()`

### Fase 3: Actions de Clientes e Fornecedores (Prioridade Média)

**Arquivos a refatorar:**
- `app/Filament/Resources/Clients/Actions/*`
- `app/Filament/Resources/Suppliers/Actions/*`

**Métodos do Repository a utilizar:**
- `ClientRepository::create()`, `update()`, `findByIdWithRelations()`
- `SupplierRepository::create()`, `update()`, `findByIdWithRelations()`

### Fase 4: Actions Financeiras (Prioridade Média)

**Arquivos a refatorar:**
- `app/Filament/Resources/FinancialTransactions/Actions/*`
- `app/Filament/Resources/FinancialPayments/Actions/*`

---

## Checklist de Implementação

### Para Cada Action

- [ ] Adicionar injeção de Repository via `setUp()`
- [ ] Refatorar lógica para usar métodos do Repository
- [ ] Adicionar tratamento de exceções
- [ ] Adicionar notificações de sucesso/erro
- [ ] Preservar validações existentes
- [ ] Adicionar logging de operações críticas
- [ ] Testar com dados reais
- [ ] Documentar padrão utilizado

### Validação Geral

- [ ] Todas as Actions usam Repositories
- [ ] Padrão consistente em todas as Actions
- [ ] Testes passando (84+ testes)
- [ ] Funcionalidades preservadas
- [ ] Performance mantida ou melhorada
- [ ] Código documentado em português

---

## Exemplo de Refatoração

### Antes (sem Repository)

```php
class ApproveQuoteAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'approve_quote';
    }

    public function setUp(): void
    {
        parent::setUp();

        $this
            ->form([
                // formulário
            ])
            ->action(function (array $data) {
                $quote = $this->record;
                
                // Lógica inline
                $quote->update([
                    'status' => 'approved',
                    'approved_at' => now(),
                    'approved_by' => auth()->id(),
                ]);
                
                // Atualizar order
                $quote->order()->update([
                    'status' => 'quote_approved',
                ]);
            });
    }
}
```

### Depois (com Repository)

```php
class ApproveQuoteAction extends Action
{
    protected OrderRepository $orderRepository;
    protected SupplierQuoteRepository $quoteRepository;

    public static function getDefaultName(): ?string
    {
        return 'approve_quote';
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->orderRepository = app(OrderRepository::class);
        $this->quoteRepository = app(SupplierQuoteRepository::class);

        $this
            ->form([
                // formulário
            ])
            ->action(function (array $data) {
                $this->handleApproveQuote($data);
            });
    }

    protected function handleApproveQuote(array $data): void
    {
        try {
            $quote = $this->record;
            
            // Usar repository para atualizar cotação
            $this->quoteRepository->update($quote->id, [
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => auth()->id(),
            ]);
            
            // Usar repository para atualizar order
            $this->orderRepository->update($quote->order_id, [
                'status' => 'quote_approved',
            ]);
            
            \Filament\Notifications\Notification::make()
                ->success()
                ->title('Cotação aprovada com sucesso')
                ->send();
                
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('Erro ao aprovar cotação')
                ->body($e->getMessage())
                ->send();
        }
    }
}
```

---

## Repositories Necessários

Para esta tarefa, será necessário criar/atualizar:

1. **SupplierQuoteRepository** (novo)
   - Métodos para gerenciar cotações de fornecedores
   - Métodos para filtrar por status, ordem, fornecedor

2. **RFQRepository** (novo)
   - Métodos específicos para gerenciar RFQs
   - Métodos para comparação de cotações

3. **DocumentRepository** (novo)
   - Métodos para gerenciar documentos gerados
   - Métodos para filtrar por tipo, status

4. **FinancialTransactionRepository** (novo)
   - Métodos para gerenciar transações financeiras
   - Métodos para filtrar por categoria, status

---

## Testes Necessários

### Testes de Integração

```php
// tests/Integration/Filament/Actions/ApproveQuoteActionTest.php
test('ApproveQuoteAction injects repositories', function () {
    $action = new ApproveQuoteAction();
    expect($action->orderRepository)->toBeInstanceOf(OrderRepository::class);
    expect($action->quoteRepository)->toBeInstanceOf(SupplierQuoteRepository::class);
});

test('ApproveQuoteAction updates quote via repository', function () {
    $quote = SupplierQuote::factory()->create();
    $action = new ApproveQuoteAction();
    
    $action->handleApproveQuote([]);
    
    expect($quote->fresh()->status)->toBe('approved');
});
```

### Testes de Feature

```php
// tests/Feature/Filament/Actions/ApproveQuoteActionTest.php
test('user can approve quote via action', function () {
    $user = User::factory()->create();
    $quote = SupplierQuote::factory()->create();
    
    $this->actingAs($user)
        ->post(route('filament.admin.resources.supplier-quotes.approve', $quote))
        ->assertSuccessful();
});
```

---

## Métricas de Sucesso

| Métrica | Meta | Status |
|---------|------|--------|
| Actions Refatoradas | 100% | ⏳ Pendente |
| Repositories Utilizados | 100% | ⏳ Pendente |
| Testes Passando | 84+ | ⏳ Pendente |
| Cobertura de Testes | >80% | ⏳ Pendente |
| Documentação | 100% | ⏳ Pendente |

---

## Recomendações Profissionais

### 1. **Criar Trait para Injeção de Repositories**

Para evitar repetição de código, criar um trait:

```php
namespace App\Filament\Traits;

trait InjectsRepositories
{
    protected function injectRepository(string $repositoryClass): object
    {
        return app($repositoryClass);
    }
}
```

### 2. **Criar Base Action Class**

```php
namespace App\Filament\Actions;

use Filament\Actions\Action;

abstract class BaseAction extends Action
{
    protected function injectRepository(string $repositoryClass)
    {
        return app($repositoryClass);
    }
}
```

### 3. **Implementar Logging**

Adicionar logging para operações críticas:

```php
\Log::info('Quote approved', [
    'quote_id' => $quote->id,
    'approved_by' => auth()->id(),
    'timestamp' => now(),
]);
```

### 4. **Implementar Auditoria**

Usar o padrão de auditoria para rastrear mudanças:

```php
// Registrar mudança no audit log
\App\Models\AuditLog::create([
    'model_type' => get_class($quote),
    'model_id' => $quote->id,
    'action' => 'approve',
    'user_id' => auth()->id(),
    'changes' => ['status' => ['pending', 'approved']],
]);
```

---

## Próximos Passos

1. **Criar Repositories Adicionais** (SupplierQuoteRepository, RFQRepository, etc.)
2. **Refatorar Actions de RFQ** (Prioridade Alta)
3. **Refatorar Actions de Produtos** (Prioridade Alta)
4. **Refatorar Actions de Clientes/Fornecedores** (Prioridade Média)
5. **Refatorar Actions Financeiras** (Prioridade Média)
6. **Criar Testes Completos**
7. **Documentar Padrões**
8. **Fazer Commit e Review**

---

## Estimativa de Esforço

- **Análise**: 2-3 horas
- **Implementação**: 8-10 horas
- **Testes**: 4-6 horas
- **Documentação**: 2-3 horas
- **Total**: 16-22 horas

---

## Riscos e Mitigações

| Risco | Probabilidade | Impacto | Mitigação |
|-------|---------------|--------|-----------|
| Quebra de funcionalidades existentes | Média | Alto | Testes completos antes de commit |
| Performance degradada | Baixa | Médio | Monitorar queries com Laravel Debugbar |
| Inconsistência de padrão | Média | Médio | Code review rigoroso |
| Falta de testes | Média | Alto | Criar testes junto com refatoração |

---

**Documento criado em:** 04 de Dezembro de 2025
**Versão:** 1.0
**Status:** Pronto para Implementação
