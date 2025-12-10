# Guia de Sincronização Completa do Banco de Dados

## 🎯 Objetivo

Este guia mostra como **resetar completamente o banco de dados local do zero** e sincronizar com o estado atual do GitHub, garantindo um ambiente limpo e consistente.

---

## ⚠️ ATENÇÃO

**Este processo irá APAGAR TODOS OS DADOS do seu banco de dados local!**

Use este método quando:
- ✅ Você quer sincronizar 100% com o servidor/GitHub
- ✅ Você não precisa dos dados locais
- ✅ Você quer eliminar qualquer inconsistência de migrations
- ✅ Você está começando em um novo ambiente de desenvolvimento

**NÃO use este método em produção!**

---

## 🚀 Método Recomendado: Script Automatizado

### Passo a Passo

1. **Navegue até o diretório do projeto:**
   ```bash
   cd /caminho/para/Impex_project_final
   ```

2. **Execute o script:**
   ```bash
   bash fresh_sync.sh
   ```

3. **Siga as instruções interativas:**
   - Confirme que deseja resetar (digite `SIM` em maiúsculas)
   - Escolha se deseja fazer backup antes (recomendado)
   - Escolha se deseja executar seeders

4. **Aguarde a conclusão:**
   O script irá:
   - ✅ Fazer pull do GitHub
   - ✅ Limpar cache do Laravel
   - ✅ Resetar o banco de dados
   - ✅ Executar todas as migrations
   - ✅ Executar seeders (opcional)
   - ✅ Mostrar status final

### Vantagens do Script

- 🔒 **Seguro** - Pede confirmação explícita antes de apagar dados
- 💾 **Backup opcional** - Oferece criar backup antes de resetar
- 🎨 **Interface colorida** - Feedback visual claro de cada etapa
- ✅ **Validações** - Verifica se está no diretório correto e se o .env existe
- 📊 **Status final** - Mostra o estado de todas as migrations ao final

---

## 🛠️ Método Manual (Alternativa)

Se preferir fazer manualmente, siga estes passos:

### 1. Fazer Pull do GitHub

```bash
git fetch origin
git pull origin main  # ou sua branch principal
```

### 2. Limpar Cache do Laravel

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### 3. Resetar Banco de Dados

```bash
# Opção A: Reset completo (recomendado)
php artisan migrate:fresh --seed

# Opção B: Reset sem seeders
php artisan migrate:fresh

# Opção C: Rollback e re-executar
php artisan migrate:reset
php artisan migrate
```

### 4. Verificar Status

```bash
php artisan migrate:status
```

---

## 📋 Comandos Úteis

### Verificar Conexão com Banco de Dados

```bash
php artisan db:show
```

### Ver Todas as Migrations e Status

```bash
php artisan migrate:status
```

### Executar Migrations em Modo Dry-Run (sem executar)

```bash
php artisan migrate --pretend
```

### Rollback da Última Batch de Migrations

```bash
php artisan migrate:rollback
```

### Rollback de Todas as Migrations

```bash
php artisan migrate:reset
```

### Resetar e Re-executar Tudo

```bash
php artisan migrate:refresh
```

### Resetar, Re-executar e Seedar

```bash
php artisan migrate:refresh --seed
```

---

## 💾 Backup Manual (Antes de Resetar)

### MySQL/MariaDB

```bash
# Criar backup
mysqldump -u[usuario] -p[senha] [nome_banco] > backup_$(date +%Y%m%d_%H%M%S).sql

# Restaurar backup
mysql -u[usuario] -p[senha] [nome_banco] < backup_20251210_120000.sql
```

### PostgreSQL

```bash
# Criar backup
pg_dump -U [usuario] [nome_banco] > backup_$(date +%Y%m%d_%H%M%S).sql

# Restaurar backup
psql -U [usuario] [nome_banco] < backup_20251210_120000.sql
```

### SQLite

```bash
# Criar backup
cp database/database.sqlite database/database.sqlite.backup

# Restaurar backup
cp database/database.sqlite.backup database/database.sqlite
```

---

## 🔍 Troubleshooting

### Erro: "No such file or directory: artisan"

**Causa:** Você não está no diretório raiz do projeto.

**Solução:**
```bash
cd /caminho/para/Impex_project_final
```

### Erro: "SQLSTATE[HY000] [1045] Access denied"

**Causa:** Credenciais do banco de dados incorretas no `.env`.

**Solução:**
1. Verifique o arquivo `.env`
2. Confirme usuário, senha e nome do banco
3. Teste a conexão: `php artisan db:show`

### Erro: "SQLSTATE[HY000] [2002] Connection refused"

**Causa:** Servidor de banco de dados não está rodando.

**Solução:**
```bash
# MySQL/MariaDB
sudo systemctl start mysql

# PostgreSQL
sudo systemctl start postgresql
```

### Erro: "Nothing to migrate"

**Causa:** Todas as migrations já foram executadas.

**Solução:**
```bash
# Ver status
php artisan migrate:status

# Se necessário, resetar
php artisan migrate:fresh
```

### Migrations executam mas tabelas não aparecem

**Causa:** Você pode estar conectado ao banco errado.

**Solução:**
```bash
# Verificar qual banco está conectado
php artisan db:show

# Verificar .env
cat .env | grep DB_
```

---

## 🎓 Boas Práticas

### 1. Sempre Faça Backup Antes de Resetar

Mesmo em ambiente de desenvolvimento, é bom ter um backup:

```bash
# Criar diretório de backups
mkdir -p storage/backups

# Fazer backup antes de resetar
mysqldump -u[user] -p[pass] [db] > storage/backups/backup_$(date +%Y%m%d_%H%M%S).sql

# Depois resetar
php artisan migrate:fresh
```

### 2. Use Seeders para Dados de Teste

Em vez de criar dados manualmente toda vez, use seeders:

```bash
# Criar um seeder
php artisan make:seeder UserSeeder

# Executar seeders
php artisan db:seed

# Ou resetar e seedar de uma vez
php artisan migrate:fresh --seed
```

### 3. Mantenha .env.example Atualizado

Sempre que adicionar novas variáveis ao `.env`, atualize o `.env.example`:

```bash
# Copiar estrutura (sem valores sensíveis)
cp .env .env.example
# Depois remova valores sensíveis do .env.example
```

### 4. Use Migrations Idempotentes

Sempre verifique se colunas/tabelas existem antes de criar:

```php
// ✅ BOM - Idempotente
if (!Schema::hasTable('users')) {
    Schema::create('users', function (Blueprint $table) {
        // ...
    });
}

if (!Schema::hasColumn('users', 'status')) {
    Schema::table('users', function (Blueprint $table) {
        $table->string('status');
    });
}

// ❌ RUIM - Não idempotente
Schema::create('users', function (Blueprint $table) {
    // Vai falhar se a tabela já existir
});
```

### 5. Nunca Edite Migrations Já Commitadas

Se uma migration já foi commitada e aplicada em outros ambientes:

```bash
# ❌ NUNCA faça isso
# Editar migration antiga

# ✅ SEMPRE faça isso
# Criar nova migration para corrigir
php artisan make:migration fix_users_table_issue
```

---

## 🔄 Workflow Recomendado

### Para Desenvolvimento Diário

```bash
# 1. Atualizar código
git pull origin main

# 2. Executar novas migrations
php artisan migrate

# 3. Limpar cache se necessário
php artisan config:clear
```

### Para Sincronização Completa (Semanal/Mensal)

```bash
# 1. Fazer backup
mysqldump -u[user] -p[pass] [db] > backup.sql

# 2. Resetar tudo
bash fresh_sync.sh

# 3. Testar aplicação
php artisan serve
```

### Para Resolver Conflitos de Migrations

```bash
# Se houver conflitos ou inconsistências
bash fresh_sync.sh
```

---

## 📊 Diferenças Entre Comandos de Migration

| Comando | O que faz | Quando usar |
|---------|-----------|-------------|
| `migrate` | Executa migrations pendentes | Desenvolvimento diário |
| `migrate:fresh` | Apaga tudo e recria | Reset completo |
| `migrate:refresh` | Rollback + migrate | Testar migrations |
| `migrate:reset` | Rollback de tudo | Preparar para fresh |
| `migrate:rollback` | Desfaz última batch | Corrigir erro recente |
| `migrate:status` | Mostra status | Verificar estado |

---

## 🚨 Avisos Importantes

### ⚠️ Em Desenvolvimento

- ✅ Pode usar `migrate:fresh` livremente
- ✅ Pode resetar o banco quando quiser
- ✅ Use seeders para recriar dados de teste

### ⚠️ Em Staging

- ⚠️ Cuidado ao usar `migrate:fresh`
- ✅ Prefira `migrate` para aplicar novas migrations
- ✅ Coordene com o time antes de resetar

### ⚠️ Em Produção

- ❌ **NUNCA** use `migrate:fresh`
- ❌ **NUNCA** use `migrate:refresh`
- ❌ **NUNCA** use `migrate:reset`
- ✅ **SEMPRE** use apenas `migrate`
- ✅ **SEMPRE** faça backup antes
- ✅ **SEMPRE** teste em staging primeiro

---

## 📞 Suporte

Se você encontrar problemas:

1. **Verifique os logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **Execute em modo verbose:**
   ```bash
   php artisan migrate --verbose
   ```

3. **Verifique a conexão:**
   ```bash
   php artisan db:show
   ```

4. **Consulte a documentação oficial:**
   - [Laravel Migrations](https://laravel.com/docs/migrations)
   - [Laravel Database](https://laravel.com/docs/database)

---

## 📝 Checklist Pós-Reset

Após executar o reset completo, verifique:

- [ ] Todas as migrations foram executadas (`php artisan migrate:status`)
- [ ] Não há migrations pendentes
- [ ] A aplicação inicia sem erros (`php artisan serve`)
- [ ] As rotas principais funcionam
- [ ] O Filament Admin funciona (`/admin`)
- [ ] Você consegue fazer login (se tiver seeders de usuários)

---

## 🎉 Conclusão

Resetar o banco de dados do zero é a forma mais limpa e profissional de garantir sincronização completa com o GitHub. Use o script `fresh_sync.sh` para automatizar o processo com segurança.

**Lembre-se:** Esta é uma operação destrutiva. Sempre confirme que você está no ambiente correto antes de executar!

---

**Última atualização:** 10 de dezembro de 2025  
**Versão do Laravel:** 11.x  
**Versão do Filament:** 4.x  
**Autor:** Sistema de Sincronização Impex
