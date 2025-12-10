# 🛠️ Scripts de Manutenção - Impex Project

Este diretório contém scripts utilitários para facilitar a manutenção, instalação e sincronização do projeto.

---

## 📁 Estrutura

```
scripts/
└── maintenance/
    ├── fresh_sync.sh              # Reset completo do banco de dados
    ├── fix_migration_order.sh     # Correção de ordem de migrations
    ├── validate_environment.sh    # Validação do ambiente
    ├── quick_backup.sh            # Backup rápido do banco
    └── create_super_admin.php     # Criação de super admin
```

---

## 🚀 Scripts Disponíveis

### **1. fresh_sync.sh** - Reset Completo do Banco

Reseta o banco de dados do zero e sincroniza com o GitHub.

```bash
bash scripts/maintenance/fresh_sync.sh
```

**O que faz:**
- ✅ Verifica mudanças locais no Git
- ✅ Faz pull do GitHub
- ✅ Reseta banco de dados (migrate:fresh)
- ✅ Limpa cache do Laravel
- ✅ Executa seeders (opcional)

**Quando usar:**
- Após clonar o repositório
- Quando o banco está inconsistente
- Para sincronizar com mudanças do servidor

---

### **2. fix_migration_order.sh** - Correção de Ordem de Migrations

Renomeia migrations antigas para rodarem na ordem correta.

```bash
bash scripts/maintenance/fix_migration_order.sh
```

**O que faz:**
- ✅ Identifica migrations de 2024
- ✅ Renomeia para rodarem depois das de 2025
- ✅ Mantém ordem relativa

**Quando usar:**
- Quando migrations falham por ordem incorreta
- Erro: "Table doesn't exist"

---

### **3. validate_environment.sh** - Validação do Ambiente

Valida se o ambiente está configurado corretamente.

```bash
bash scripts/maintenance/validate_environment.sh
```

**O que faz:**
- ✅ Verifica PHP, Composer, Git
- ✅ Valida arquivo .env
- ✅ Testa conexão com banco
- ✅ Verifica permissões de diretórios

**Quando usar:**
- Antes de qualquer operação crítica
- Após configurar novo ambiente
- Para troubleshooting

---

### **4. quick_backup.sh** - Backup Rápido

Cria backup do banco de dados.

```bash
bash scripts/maintenance/quick_backup.sh
```

**O que faz:**
- ✅ Cria backup em storage/backups/
- ✅ Suporta MySQL, PostgreSQL, SQLite
- ✅ Adiciona timestamp ao nome

**Quando usar:**
- Antes de operações destrutivas
- Antes de atualizar o sistema
- Para backup regular

---

### **5. create_super_admin.php** - Criação de Super Admin

Cria usuário super admin interativamente.

```bash
php scripts/maintenance/create_super_admin.php
```

**O que faz:**
- ✅ Solicita dados do admin
- ✅ Cria usuário com is_admin=true
- ✅ Cria e atribui role super_admin
- ✅ Verifica se usuário já existe

**Quando usar:**
- Após resetar o banco
- Para criar primeiro usuário
- Para adicionar novos admins

---

## 📖 Documentação Completa

Toda a documentação detalhada está em `docs/setup/`:

- **SYNC_QUICK_START.md** - Guia rápido de sincronização
- **FRESH_SYNC_README.md** - Documentação completa do fresh_sync
- **MIGRATION_ORDER_FIX.md** - Explicação do problema de ordem de migrations
- **CREATE_ADMIN_README.md** - Guia de criação de usuários
- **TROUBLESHOOTING.md** - Solução de problemas comuns

---

## 🎯 Workflow de Instalação Completo

### **Para Nova Instalação:**

```bash
# 1. Clonar repositório
git clone https://github.com/guidutra-china/Impex_project_final.git
cd Impex_project_final

# 2. Instalar dependências
composer install
npm install

# 3. Configurar ambiente
cp .env.example .env
php artisan key:generate

# 4. Editar .env com credenciais do banco
nano .env

# 5. Validar ambiente
bash scripts/maintenance/validate_environment.sh

# 6. Executar migrations
php artisan migrate

# 7. Criar super admin
php scripts/maintenance/create_super_admin.php

# 8. Iniciar servidor
php artisan serve
```

---

### **Para Sincronização com GitHub:**

```bash
# 1. Fazer backup (opcional)
bash scripts/maintenance/quick_backup.sh

# 2. Sincronizar tudo do zero
bash scripts/maintenance/fresh_sync.sh

# 3. Criar super admin
php scripts/maintenance/create_super_admin.php

# 4. Iniciar servidor
php artisan serve
```

---

### **Para Corrigir Problemas de Migrations:**

```bash
# 1. Corrigir ordem de migrations
bash scripts/maintenance/fix_migration_order.sh

# 2. Executar migrations
php artisan migrate

# 3. Se ainda falhar, reset completo
bash scripts/maintenance/fresh_sync.sh
```

---

## 🔐 Permissões

Todos os scripts devem ter permissão de execução:

```bash
chmod +x scripts/maintenance/*.sh
chmod +x scripts/maintenance/*.php
```

---

## 🐛 Troubleshooting

### **Erro: "Permission denied"**

```bash
chmod +x scripts/maintenance/nome_do_script.sh
```

### **Erro: "Command not found"**

Execute com `bash` ou `php` explicitamente:

```bash
bash scripts/maintenance/fresh_sync.sh
php scripts/maintenance/create_super_admin.php
```

### **Script não encontra artisan**

Certifique-se de executar do diretório raiz do projeto:

```bash
cd /caminho/para/Impex_project_final
bash scripts/maintenance/fresh_sync.sh
```

---

## 📝 Contribuindo

Ao adicionar novos scripts:

1. ✅ Coloque em `scripts/maintenance/`
2. ✅ Adicione documentação neste README
3. ✅ Crie documentação detalhada em `docs/setup/`
4. ✅ Adicione permissão de execução
5. ✅ Teste em ambiente limpo
6. ✅ Commite ao Git

---

## 🎓 Boas Práticas

1. ✅ **Sempre faça backup** antes de operações destrutivas
2. ✅ **Valide o ambiente** antes de executar scripts
3. ✅ **Leia a documentação** completa antes de usar
4. ✅ **Teste em ambiente local** antes de produção
5. ✅ **Mantenha scripts atualizados** com o projeto

---

**Última atualização:** 10 de dezembro de 2025  
**Versão:** 1.0.0  
**Mantenedor:** Equipe Impex
