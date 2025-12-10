# 🚀 Guia Rápido de Sincronização

## TL;DR - Resetar Banco de Dados do Zero

```bash
# 1. Validar ambiente (opcional mas recomendado)
bash validate_environment.sh

# 2. Fazer backup (opcional mas recomendado)
bash quick_backup.sh

# 3. Resetar e sincronizar tudo
bash fresh_sync.sh
```

---

## 📋 Scripts Disponíveis

### 1. **fresh_sync.sh** ⭐ (Principal)
Reset completo do banco de dados e sincronização com GitHub.

**Uso:**
```bash
bash fresh_sync.sh
```

**O que faz:**
- ✅ Pull do GitHub
- ✅ Limpa cache do Laravel
- ✅ Reseta banco de dados (APAGA TUDO)
- ✅ Executa todas as migrations
- ✅ Executa seeders (opcional)

---

### 2. **validate_environment.sh** (Recomendado antes do reset)
Valida se o ambiente está pronto para o reset.

**Uso:**
```bash
bash validate_environment.sh
```

**O que verifica:**
- ✅ Arquivo artisan existe
- ✅ Arquivo .env configurado
- ✅ PHP instalado e versão correta
- ✅ Composer instalado
- ✅ Dependências instaladas
- ✅ Conexão com banco de dados
- ✅ Git configurado
- ✅ Permissões de diretórios
- ✅ Migrations existem
- ✅ Espaço em disco

---

### 3. **quick_backup.sh** (Recomendado antes do reset)
Cria backup rápido do banco de dados.

**Uso:**
```bash
bash quick_backup.sh
```

**O que faz:**
- ✅ Cria backup em `storage/backups/`
- ✅ Suporta MySQL, PostgreSQL e SQLite
- ✅ Adiciona timestamp ao nome do arquivo
- ✅ Mostra comando para restaurar

---

## 🎯 Workflow Recomendado

### Para Reset Completo (Primeira Vez)

```bash
# Passo 1: Validar ambiente
bash validate_environment.sh

# Passo 2: Fazer backup (se quiser manter dados)
bash quick_backup.sh

# Passo 3: Resetar tudo
bash fresh_sync.sh
```

### Para Uso Diário (Sem Reset)

```bash
# Apenas atualizar código e rodar novas migrations
git pull origin main
php artisan migrate
php artisan config:clear
```

---

## ⚠️ Avisos Importantes

### ❌ NÃO use fresh_sync.sh se:
- Você está em produção
- Você precisa dos dados atuais
- Você não tem certeza do que está fazendo

### ✅ USE fresh_sync.sh quando:
- Você quer sincronizar 100% com o GitHub
- Você não precisa dos dados locais
- Você quer eliminar inconsistências de migrations
- Você está configurando um novo ambiente

---

## 🆘 Problemas Comuns

### "Permission denied"
```bash
chmod +x *.sh
```

### "Command not found: bash"
```bash
sh fresh_sync.sh
```

### "Access denied for user"
Verifique as credenciais no arquivo `.env`:
```bash
nano .env
# Ou
code .env
```

### "Nothing to migrate"
Tudo certo! Suas migrations já estão atualizadas.

---

## 📚 Documentação Completa

Para mais detalhes, consulte:
- **FRESH_SYNC_README.md** - Guia completo de sincronização
- **SYNC_MIGRATIONS_README.md** - Guia de sincronização sem reset

---

## 🎓 Boas Práticas

1. **Sempre valide antes de resetar:**
   ```bash
   bash validate_environment.sh
   ```

2. **Sempre faça backup antes de resetar:**
   ```bash
   bash quick_backup.sh
   ```

3. **Confirme a branch correta:**
   ```bash
   git branch --show-current
   ```

4. **Verifique mudanças não commitadas:**
   ```bash
   git status
   ```

---

## 📞 Suporte

Se você encontrar problemas:

1. Leia o erro com atenção
2. Consulte **FRESH_SYNC_README.md** para troubleshooting
3. Verifique os logs: `storage/logs/laravel.log`
4. Execute em modo verbose: `php artisan migrate --verbose`

---

**Última atualização:** 10 de dezembro de 2025  
**Versão:** 1.0.0
