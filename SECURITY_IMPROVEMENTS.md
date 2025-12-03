# Security Improvements - Fase 0

**Data:** 03 de Dezembro de 2025
**Status:** Implementação em Progresso

## 1. Resumo das Melhorias de Segurança

Este documento detalha as melhorias de segurança implementadas na Fase 0 do projeto Impex. O foco é corrigir as vulnerabilidades críticas identificadas na análise de segurança.

## 2. Vulnerabilidades Corrigidas

### 2.1. Transações de Banco de Dados

**Problema:** Operações que envolvem múltiplas escritas no banco de dados não estavam encapsuladas em transações, deixando o banco em estado inconsistente se uma etapa falhasse.

**Solução Implementada:**
- Criado arquivo de referência `app/Services/RFQImportService.secured.php` demonstrando o uso correto de `DB::transaction()`
- Todas as operações de serviço que modificam múltiplos registros devem ser envolvidas em transações
- Rollback automático em caso de erro

**Exemplo de Implementação:**
```php
return DB::transaction(function () use ($order, $filePath) {
    $worksheet = $this->loadAndValidateWorksheet($filePath);
    $rows = $this->extractDataRows($worksheet);
    return $this->processRows($order, $rows);
});
```

**Aplicar em:**
- `app/Services/RFQImportService.php` - Linhas 27-55
- `app/Services/SupplierQuoteImportService.php` - Linhas 28-79

### 2.2. Validação Segura de Upload de Arquivos

**Problema:** O sistema não validava adequadamente os tipos de arquivo durante o upload, abrindo brechas para ataques.

**Solução Implementada:**
- Criado novo `FileUploadService` com validação rigorosa
- Validação de MIME types contra lista branca
- Bloqueio de extensões perigosas (.php, .exe, etc.)
- Detecção de path traversal em nomes de arquivo
- Geração de nomes de arquivo seguros usando UUID
- Armazenamento em diretório privado por padrão

**Localização:** `app/Services/FileUploadService.php`

**Como Usar:**
```php
$uploadService = new FileUploadService();
$result = $uploadService->upload($file, 'spreadsheets', 'imports/rfq');

if ($result['success']) {
    // File stored at $result['path']
} else {
    // Handle error: $result['error']
}
```

**Aplicar em:**
- Todos os formulários Filament que fazem upload de arquivos
- Especialmente: `Orders`, `SupplierQuotes`, `Documents`

### 2.3. Autorização Centralizada com Policies

**Problema:** A lógica de autorização estava espalhada pelo código, tornando difícil garantir que as permissões fossem verificadas em todos os lugares.

**Solução Implementada:**
- O projeto já possui `Policies` configuradas via Filament Shield
- Recomendação: Expandir as Policies para incluir lógica de negócio específica
- Exemplo: `app/Policies/OrderPolicy.php` (fornecido como referência)

**Verificação de Autorização em Services:**
```php
// Em qualquer serviço que acesse dados sensíveis
$this->authorize('view', $order);
```

## 3. Arquivos Criados

| Arquivo | Descrição | Status |
| :--- | :--- | :--- |
| `tests/Arch/ArchitectureTest.php` | Testes de arquitetura para garantir conformidade com padrões | ✅ Criado |
| `tests/Helpers/TestHelpers.php` | Helpers para criação de dados de teste | ✅ Criado |
| `tests/Pest.php` | Configuração atualizada do Pest com helpers globais | ✅ Atualizado |
| `setup-tests.sh` | Script para preparar o ambiente de testes | ✅ Criado |
| `app/Services/FileUploadService.php` | Serviço seguro para upload de arquivos | ✅ Criado |
| `app/Services/RFQImportService.secured.php` | Referência de implementação segura | ✅ Criado |
| `SECURITY_IMPROVEMENTS.md` | Este documento | ✅ Criado |

## 4. Próximos Passos

### 4.1. Aplicar Transações ao RFQImportService

**Tarefa:** Atualizar `app/Services/RFQImportService.php` para usar `DB::transaction()`

**Passos:**
1. Abrir `app/Services/RFQImportService.php`
2. Envolver o método `importFromExcel()` com `DB::transaction()`
3. Testar com dados reais
4. Repetir para `SupplierQuoteImportService.php`

**Estimativa:** 30 minutos

### 4.2. Integrar FileUploadService nos Filament Resources

**Tarefa:** Usar `FileUploadService` em todos os formulários que fazem upload

**Passos:**
1. Identificar todos os `FileUpload` components nos Filament Resources
2. Criar Actions customizadas que usem `FileUploadService`
3. Testar com arquivos válidos e inválidos
4. Documentar o padrão para novos uploads

**Estimativa:** 2-3 horas

### 4.3. Expandir Policies

**Tarefa:** Adicionar métodos específicos de negócio às Policies existentes

**Exemplo:**
```php
public function importItems(User $user, Order $order): bool
{
    return $user->can('Update:Order') && $order->status === 'pending';
}
```

**Estimativa:** 1-2 horas

## 5. Testes de Segurança

### 5.1. Testes para Validação de Arquivo

Criar testes em `tests/Feature/FileUploadTest.php`:

```php
test('rejects files with dangerous extensions')
    ->expect(FileUploadService::class)
    ->toRejectFile('malicious.php');

test('rejects files exceeding size limit')
    ->expect(FileUploadService::class)
    ->toRejectFile('large_file.pdf'); // > 10MB

test('accepts valid spreadsheet files')
    ->expect(FileUploadService::class)
    ->toAcceptFile('data.xlsx');
```

### 5.2. Testes para Transações

Criar testes em `tests/Feature/RFQImportSecurityTest.php`:

```php
test('rolls back on import error')
    ->expect(Order::count())->toBe(0)
    ->when(function () {
        RFQImportService::import($order, 'invalid.xlsx');
    })
    ->expect(Order::count())->toBe(0); // Rollback successful
```

## 6. Checklist de Implementação

- [ ] Aplicar transações ao `RFQImportService`
- [ ] Aplicar transações ao `SupplierQuoteImportService`
- [ ] Integrar `FileUploadService` nos Filament Resources
- [ ] Expandir `Policies` com métodos de negócio
- [ ] Criar testes de segurança para upload de arquivos
- [ ] Criar testes de segurança para transações
- [ ] Revisar código com segurança em mente
- [ ] Documentar padrões de segurança para novos desenvolvedores

## 7. Referências de Segurança

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Laravel Security Documentation](https://laravel.com/docs/security)
- [File Upload Security Best Practices](https://owasp.org/www-community/vulnerabilities/Unrestricted_File_Upload)
- [Database Transaction Best Practices](https://en.wikipedia.org/wiki/Database_transaction)
