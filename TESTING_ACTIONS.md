# Guia de Teste das Actions - Fase 1

Este documento descreve como testar as Actions refatoradas para garantir que estão funcionando corretamente.

## Índice

1. [Testes Automatizados](#testes-automatizados)
2. [Testes Manuais](#testes-manuais)
3. [Verificação de Integração](#verificação-de-integração)
4. [Troubleshooting](#troubleshooting)

---

## Testes Automatizados

### Executar todos os testes

```bash
php artisan test
```

### Executar apenas testes de Actions

```bash
# Testes unitários das Actions
php artisan test tests/Unit/Actions/

# Testes de integração das Actions
php artisan test tests/Integration/Actions/

# Ambos
php artisan test tests/Unit/Actions/ tests/Integration/Actions/
```

### Executar testes específicos

```bash
# Testes do ImportRfqAction
php artisan test tests/Unit/Actions/ImportRfqActionTest.php

# Testes do CompareQuotesAction
php artisan test tests/Unit/Actions/CompareQuotesActionTest.php

# Testes de integração
php artisan test tests/Integration/Actions/ActionsIntegrationTest.php
```

### Ver cobertura de testes

```bash
php artisan test --coverage
```

---

## Testes Manuais

### 1. Verificar se as Actions podem ser resolvidas do container

```bash
php artisan tinker

# Dentro do tinker:
$action = app(\App\Actions\RFQ\ImportRfqAction::class);
$action  // Deve retornar uma instância de ImportRfqAction

$action = app(\App\Actions\Quote\CompareQuotesAction::class);
$action  // Deve retornar uma instância de CompareQuotesAction

$action = app(\App\Actions\File\UploadFileAction::class);
$action  // Deve retornar uma instância de UploadFileAction

$action = app(\App\Actions\Quote\ImportSupplierQuotesAction::class);
$action  // Deve retornar uma instância de ImportSupplierQuotesAction
```

### 2. Testar CompareQuotesAction

```bash
php artisan tinker

# Dentro do tinker:
$order = \App\Models\Order::first();
$action = app(\App\Actions\Quote\CompareQuotesAction::class);

# Testar execute()
$result = $action->execute($order);
dd($result);  // Deve retornar um array com comparação de cotações

# Testar getCheapestQuote()
$cheapest = $action->getCheapestQuote($order);
dd($cheapest);  // Deve retornar a cotação mais barata ou null

# Testar getRankedQuotes()
$ranked = $action->getRankedQuotes($order);
dd($ranked);  // Deve retornar array de cotações ordenadas por preço
```

### 3. Testar UploadFileAction

```bash
php artisan tinker

# Dentro do tinker:
$action = app(\App\Actions\File\UploadFileAction::class);

# Criar um arquivo fake
$file = \Illuminate\Http\UploadedFile::fake()->create('test.pdf', 100);

# Testar execute()
$result = $action->execute($file, 'test-category', 'test-prefix');
dd($result);  // Deve retornar array com success e path

# Testar validate()
$validation = $action->validate($file, 'test-category');
dd($validation);  // Deve retornar array com success: true
```

### 4. Testar ImportSupplierQuotesAction

```bash
php artisan tinker

# Dentro do tinker:
$order = \App\Models\Order::first();
$action = app(\App\Actions\Quote\ImportSupplierQuotesAction::class);

# Testar getQuoteStatus()
$status = $action->getQuoteStatus($order);
dd($status);  // Deve retornar array com total_quotes, suppliers, latest_quote_date
```

---

## Verificação de Integração

### 1. Verificar se o ActionServiceProvider está registrado

```bash
php artisan tinker

# Dentro do tinker:
$providers = config('app.providers');
// Ou verificar se as Actions podem ser resolvidas (veja acima)
```

### 2. Verificar se as dependências estão injetadas corretamente

```bash
php artisan tinker

# Dentro do tinker:
$action = app(\App\Actions\RFQ\ImportRfqAction::class);
// Se conseguir resolver sem erro, as dependências estão corretas

# Verificar se o FileUploadService está registrado
$service = app(\App\Services\FileUploadService::class);
$service  // Deve retornar uma instância do serviço
```

### 3. Testar em um Controller ou Livewire Component

```php
<?php

namespace App\Http\Controllers;

use App\Actions\Quote\CompareQuotesAction;
use App\Models\Order;

class OrderController extends Controller
{
    public function compareQuotes(Order $order, CompareQuotesAction $action)
    {
        $result = $action->execute($order);
        return response()->json($result);
    }
}
```

---

## Checklist de Testes

- [ ] Todos os testes passam com `php artisan test`
- [ ] Testes de Actions passam com `php artisan test tests/Unit/Actions/ tests/Integration/Actions/`
- [ ] Actions podem ser resolvidas do container via `app(ActionClass::class)`
- [ ] Dependências são injetadas corretamente
- [ ] Métodos `execute()` funcionam sem erros
- [ ] Métodos `handle()` funcionam com validação
- [ ] Métodos auxiliares funcionam corretamente
- [ ] Logging funciona corretamente
- [ ] Exceções são lançadas quando apropriado
- [ ] Cobertura de testes é adequada (>80%)

---

## Troubleshooting

### Erro: "Class not found"

**Problema:** Ao tentar resolver uma Action do container, recebe erro "Class not found"

**Solução:**
1. Verifique se o arquivo da Action existe no caminho correto
2. Verifique se o namespace está correto
3. Execute `composer dump-autoload`
4. Verifique se o ActionServiceProvider está registrado em AppServiceProvider

### Erro: "Service not found"

**Problema:** Ao tentar resolver uma Action, recebe erro sobre o Service não ser encontrado

**Solução:**
1. Verifique se o Service está registrado no container (AppServiceProvider ou ActionServiceProvider)
2. Verifique se o namespace do Service está correto
3. Execute `composer dump-autoload`

### Testes falhando

**Problema:** Testes das Actions estão falhando

**Solução:**
1. Verifique se o banco de dados de teste está configurado corretamente
2. Execute `php artisan migrate --env=testing`
3. Verifique se as factories estão criando dados válidos
4. Verifique os logs em `storage/logs/laravel.log`

---

## Próximos Passos

Após validar que as Actions estão funcionando:

1. Integrar as Actions nos Filament Resources
2. Criar Filament Actions que usam essas Business Logic Actions
3. Atualizar Controllers para usar as Actions
4. Adicionar testes de feature para workflows completos
