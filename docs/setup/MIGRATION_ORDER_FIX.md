# 🔧 Correção de Ordem de Migrations

## 🎯 Problema Identificado

O projeto tem **migrations de 2024** que tentam **alterar tabelas** (ALTER TABLE) que só são **criadas** pelas **migrations de 2025** (CREATE TABLE).

### Exemplo do Erro:

```
SQLSTATE[42S02]: Base table or view not found: 1146 
Table 'impex_project_final.users' doesn't exist
```

### Por Que Isso Acontece?

Laravel executa migrations em **ordem alfabética** por nome de arquivo. Como os nomes começam com timestamps:

- `2024_12_08_000001_...` roda **ANTES** de
- `2025_12_09_000085_...`

Mas se a migration de 2024 tenta alterar uma tabela que só é criada em 2025, dá erro!

---

## 📋 Migrations Problemáticas

As seguintes migrations de 2024 precisam rodar **DEPOIS** das de 2025:

1. `2024_12_08_000001_add_status_column_to_users_table.php`
   - Tenta alterar `users` que é criada em `2025_12_09_000085_create_users_table.php`

2. `2024_12_08_000002_add_function_column_to_client_contacts_table.php`
   - Tenta alterar `client_contacts` que é criada em `2025_12_09_000010_create_client_contacts_table.php`

3. `2024_12_08_000003_add_function_column_to_supplier_contacts_table.php`
   - Tenta alterar `supplier_contacts` que é criada em `2025_12_09_000075_create_supplier_contacts_table.php`

4. `2024_12_08_000004_make_address_footer_nullable_in_company_settings.php`
   - Tenta alterar `company_settings` que é criada em `2025_12_09_000015_create_company_settings_table.php`

---

## ✅ Solução Automatizada

### Script: `fix_migration_order.sh`

Este script:
1. ✅ Identifica todas as migrations de 2024
2. ✅ Encontra a última migration de 2025
3. ✅ Renomeia as migrations de 2024 para rodarem **depois** das de 2025
4. ✅ Mantém a ordem relativa entre as migrations de 2024

### Como Usar:

```bash
# 1. Navegue até o projeto
cd /caminho/para/Impex_project_final

# 2. Execute o script
bash fix_migration_order.sh

# 3. Confirme quando solicitado (digite 'SIM')
Deseja continuar? (digite 'SIM' em maiúsculas para confirmar): SIM

# 4. Execute as migrations
php artisan migrate
```

---

## 🔄 O Que o Script Faz

### Antes:

```
2024_12_08_000001_add_status_column_to_users_table.php
2024_12_08_000002_add_function_column_to_client_contacts_table.php
2024_12_08_000003_add_function_column_to_supplier_contacts_table.php
2024_12_08_000004_make_address_footer_nullable_in_company_settings.php
2025_12_09_000000_create_available_widgets_table.php
...
2025_12_09_000091_create_what_if_scenarios_table.php
```

### Depois:

```
2025_12_09_000000_create_available_widgets_table.php
...
2025_12_09_000091_create_what_if_scenarios_table.php
2025_12_09_000092_add_status_column_to_users_table.php
2025_12_09_000093_add_function_column_to_client_contacts_table.php
2025_12_09_000094_add_function_column_to_supplier_contacts_table.php
2025_12_09_000095_make_address_footer_nullable_in_company_settings.php
```

---

## 🎓 Por Que Isso Aconteceu?

### Cenário Provável:

1. **Dezembro de 2024:** Alguém criou migrations para adicionar colunas em tabelas existentes
2. **Dezembro de 2025:** O projeto foi refatorado e todas as migrations foram recriadas do zero
3. **Problema:** As migrations antigas de 2024 não foram removidas ou atualizadas

### Lição Aprendida:

- ❌ **Nunca** mantenha migrations antigas quando refatorar o schema
- ✅ **Sempre** delete migrations antigas ao recriar o schema
- ✅ **Sempre** use `migrate:fresh` em desenvolvimento
- ✅ **Sempre** teste migrations em ordem do zero

---

## 🛡️ Prevenção Futura

### 1. Sempre Criar Migrations em Ordem Correta

```bash
# Primeiro: Criar tabela
php artisan make:migration create_users_table

# Depois: Alterar tabela
php artisan make:migration add_status_to_users_table
```

### 2. Usar Verificações nas Migrations de Alteração

```php
public function up(): void
{
    // Verificar se tabela existe antes de alterar
    if (!Schema::hasTable('users')) {
        return;
    }
    
    Schema::table('users', function (Blueprint $table) {
        // Verificar se coluna já existe
        if (!Schema::hasColumn('users', 'status')) {
            $table->enum('status', ['active', 'inactive'])
                ->default('active');
        }
    });
}
```

### 3. Limpar Migrations Antigas ao Refatorar

```bash
# Ao refatorar o schema:
# 1. Fazer backup
cp -r database/migrations database/migrations.backup

# 2. Remover migrations antigas
rm database/migrations/*.php

# 3. Criar novas migrations
php artisan make:migration create_all_tables

# 4. Testar do zero
php artisan migrate:fresh
```

---

## 🔍 Verificação Pós-Correção

Após executar o script, verifique:

```bash
# 1. Listar migrations em ordem
ls -1 database/migrations/*.php

# 2. Ver status (todas devem estar pendentes)
php artisan migrate:status

# 3. Executar migrations
php artisan migrate

# 4. Verificar se todas rodaram
php artisan migrate:status
```

---

## 🚨 Troubleshooting

### Erro: "Migration already exists"

**Causa:** Você já tinha renomeado manualmente algumas migrations.

**Solução:**
```bash
# Reverter para estado original
git checkout database/migrations/

# Executar script novamente
bash fix_migration_order.sh
```

---

### Erro: "File not found"

**Causa:** Script não encontrou as migrations.

**Solução:**
```bash
# Verificar se está no diretório correto
pwd
# Deve mostrar: /caminho/para/Impex_project_final

# Verificar se migrations existem
ls -l database/migrations/2024_*
```

---

### Migrations Continuam Falhando

**Causa:** Pode haver outros problemas além da ordem.

**Solução:**
```bash
# Ver logs detalhados
php artisan migrate --verbose

# Verificar migration específica
php artisan migrate --path=database/migrations/2025_12_09_000092_add_status_column_to_users_table.php
```

---

## 📞 Suporte

Se o script não resolver o problema:

1. **Verifique os logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **Execute em modo dry-run:**
   ```bash
   php artisan migrate --pretend
   ```

3. **Verifique o banco de dados:**
   ```bash
   php artisan db:show
   ```

---

**Última atualização:** 10 de dezembro de 2025  
**Versão:** 1.0.0  
**Autor:** Sistema de Correção de Migrations Impex
