# 🔧 Guia de Resolução de Problemas

## Problemas Comuns Durante Sincronização

### 1. ❌ Erro: "Your local changes would be overwritten by merge"

**Causa:** Você tem mudanças locais não commitadas.

**Solução:** O script agora oferece 4 opções:
1. Fazer commit das mudanças
2. Descartar mudanças (reset hard)
3. Salvar no stash
4. Cancelar operação

**Recomendação:** Use opção 1 se as mudanças são importantes, ou opção 2 para descartá-las.

---

### 2. ❌ Erro: "Table 'cache' doesn't exist"

**Causa:** Laravel tenta limpar cache do banco antes das tabelas serem criadas.

**Solução:** O script foi atualizado para:
1. Resetar o banco PRIMEIRO
2. Limpar cache DEPOIS
3. Ignorar erros de cache se necessário

**Status:** ✅ Corrigido na versão atualizada do script

---

### 3. ❌ Erro: "Type of $navigationGroup must be UnitEnum|string|null"

**Causa:** Resource usando tipagem do Filament 3 em projeto Filament 4.

**Solução:** Adicionar `use UnitEnum;` e atualizar tipagem:

```php
// ❌ Antes
protected static ?string $navigationGroup = 'System';

// ✅ Depois
use UnitEnum;
protected static UnitEnum|string|null $navigationGroup = 'System';
```

**Status:** ✅ Corrigido no DocumentImportResource

---

### 4. ❌ Erro: "SQLSTATE[HY000] [1045] Access denied"

**Causa:** Credenciais do banco incorretas no `.env`.

**Solução:**
```bash
# Editar .env
nano .env

# Verificar credenciais
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=seu_banco
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha

# Testar conexão
php artisan db:show
```

---

### 5. ❌ Erro: "Connection refused"

**Causa:** Servidor de banco de dados não está rodando.

**Solução:**
```bash
# MySQL/MariaDB
sudo systemctl start mysql
sudo systemctl status mysql

# PostgreSQL
sudo systemctl start postgresql
sudo systemctl status postgresql
```

---

### 6. ⚠️ Aviso: "mysqldump not found"

**Causa:** Cliente MySQL não instalado.

**Solução:**
```bash
# Ubuntu/Debian
sudo apt-get update
sudo apt-get install mysql-client

# macOS
brew install mysql-client
```

---

### 7. ❌ Erro: "Permission denied" ao executar script

**Causa:** Script não tem permissão de execução.

**Solução:**
```bash
chmod +x fresh_sync.sh
chmod +x validate_environment.sh
chmod +x quick_backup.sh
```

---

### 8. ❌ Erro: "Composer dependencies not installed"

**Causa:** Diretório `vendor` não existe.

**Solução:**
```bash
composer install
# ou
composer update
```

---

### 9. ❌ Erro: "Class not found" após migrations

**Causa:** Autoload do Composer desatualizado.

**Solução:**
```bash
composer dump-autoload
php artisan config:clear
php artisan cache:clear
```

---

### 10. ⚠️ Migrations executam mas dados não aparecem

**Causa:** Conectado ao banco errado.

**Solução:**
```bash
# Verificar qual banco está conectado
php artisan db:show

# Verificar .env
cat .env | grep DB_

# Listar tabelas
php artisan tinker
DB::select('SHOW TABLES');
```

---

## 🚨 Problemas Críticos

### Script para sem motivo aparente

**Diagnóstico:**
```bash
# Ver logs do Laravel
tail -f storage/logs/laravel.log

# Executar em modo verbose
bash -x fresh_sync.sh

# Testar migrations manualmente
php artisan migrate --pretend
php artisan migrate --verbose
```

---

### Banco fica em estado inconsistente

**Recuperação:**
```bash
# Opção 1: Rollback completo
php artisan migrate:reset
php artisan migrate

# Opção 2: Fresh (apaga tudo)
php artisan migrate:fresh

# Opção 3: Restaurar backup
mysql -u[user] -p[pass] [database] < storage/backups/backup_YYYYMMDD_HHMMSS.sql
```

---

### Conflitos de merge complexos

**Resolução:**
```bash
# Ver conflitos
git status

# Opção 1: Aceitar versão do GitHub
git checkout --theirs caminho/do/arquivo
git add caminho/do/arquivo

# Opção 2: Manter versão local
git checkout --ours caminho/do/arquivo
git add caminho/do/arquivo

# Opção 3: Resolver manualmente
# Edite o arquivo e remova os marcadores de conflito
git add caminho/do/arquivo

# Finalizar merge
git commit
```

---

## 🔍 Comandos de Diagnóstico

### Verificar estado do sistema

```bash
# Versão do PHP
php -v

# Versão do Laravel
php artisan --version

# Versão do Composer
composer --version

# Status do Git
git status
git log --oneline -5

# Status do banco
php artisan db:show

# Status das migrations
php artisan migrate:status

# Espaço em disco
df -h

# Processos do banco
ps aux | grep mysql
# ou
ps aux | grep postgres
```

---

### Verificar configurações

```bash
# Ver configuração do cache
php artisan config:show cache

# Ver configuração do banco
php artisan config:show database

# Ver todas as configurações
php artisan config:show

# Ver variáveis de ambiente
php artisan env
```

---

## 📞 Quando Pedir Ajuda

Se você tentou tudo acima e ainda tem problemas:

1. **Colete informações:**
   ```bash
   php artisan about > debug_info.txt
   php artisan migrate:status >> debug_info.txt
   git status >> debug_info.txt
   tail -100 storage/logs/laravel.log >> debug_info.txt
   ```

2. **Descreva o problema:**
   - O que você estava tentando fazer?
   - Qual comando executou?
   - Qual foi o erro exato?
   - O que você já tentou?

3. **Compartilhe o contexto:**
   - Sistema operacional
   - Versão do PHP
   - Versão do Laravel
   - Tipo de banco de dados

---

## 🎓 Prevenção de Problemas

### Checklist Antes de Sincronizar

- [ ] Backup do banco de dados criado
- [ ] Mudanças locais commitadas ou stashed
- [ ] Servidor de banco rodando
- [ ] Credenciais do .env corretas
- [ ] Espaço em disco suficiente (> 10%)
- [ ] Dependências do Composer instaladas

### Checklist Depois de Sincronizar

- [ ] Todas as migrations executadas
- [ ] Aplicação inicia sem erros
- [ ] Rotas principais funcionam
- [ ] Filament Admin acessível
- [ ] Cache funcionando
- [ ] Logs sem erros críticos

---

**Última atualização:** 10 de dezembro de 2025  
**Versão:** 2.0.0
