# 🛠️ Setup & Installation Documentation

Documentação de instalação, configuração e manutenção do Impex Project.

---

## 📚 Guias Disponíveis

### **[SYNC_QUICK_START.md](SYNC_QUICK_START.md)** - Início Rápido
Guia rápido para instalação e sincronização.

### **[FRESH_SYNC_README.md](FRESH_SYNC_README.md)** - Reset Completo
Reset completo do banco de dados e sincronização com GitHub.

### **[MIGRATION_ORDER_FIX.md](MIGRATION_ORDER_FIX.md)** - Correção de Migrations
Correção de problemas de ordem de migrations.

### **[CREATE_ADMIN_README.md](CREATE_ADMIN_README.md)** - Criação de Usuários
Criação de super admin e gestão de usuários.

### **[TROUBLESHOOTING.md](TROUBLESHOOTING.md)** - Solução de Problemas
Problemas comuns e suas soluções.

---

## 🚀 Instalação Rápida

```bash
git clone https://github.com/guidutra-china/Impex_project_final.git
cd Impex_project_final
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php scripts/maintenance/create_super_admin.php
php artisan serve
```

---

**Veja [SYNC_QUICK_START.md](SYNC_QUICK_START.md) para instruções detalhadas.**
