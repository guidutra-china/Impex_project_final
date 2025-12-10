# 👤 Criação de Super Admin

## 🎯 Objetivo

Após resetar o banco de dados, você precisa criar um usuário super admin para acessar o sistema Filament.

---

## 🚀 Como Usar

### **Método 1: Script PHP Interativo** ⭐ (Recomendado)

```bash
cd /caminho/para/Impex_project_final
php create_super_admin.php
```

O script vai solicitar:
- Nome
- Email
- Senha
- Telefone (opcional)

**Exemplo de execução:**

```
==================================================
  Criação de Super Admin
==================================================

Digite os dados do Super Admin:

Nome: Administrador
Email: admin@impex.com
Senha: senha123
Telefone (opcional): +55 11 99999-9999

Confirme os dados:
Nome: Administrador
Email: admin@impex.com
Telefone: +55 11 99999-9999

Deseja continuar? (s/n): s

✅ Super Admin criado com sucesso!
✅ Role 'super_admin' atribuída!

==================================================
  Credenciais de Acesso
==================================================
Email: admin@impex.com
Senha: (a que você digitou)

Acesse o painel em: http://localhost:8000/admin
```

---

### **Método 2: Usando Artisan Tinker**

```bash
php artisan tinker
```

Depois execute:

```php
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

// Criar usuário
$user = User::create([
    'name' => 'Administrador',
    'email' => 'admin@impex.com',
    'password' => Hash::make('senha123'),
    'is_admin' => true,
    'email_verified_at' => now(),
    'locale' => 'en',
]);

// Criar role super_admin
$role = Role::firstOrCreate(
    ['name' => 'super_admin'],
    ['guard_name' => 'web']
);

// Atribuir role
$user->assignRole('super_admin');

echo "✅ Super Admin criado!\n";
echo "Email: admin@impex.com\n";
echo "Senha: senha123\n";
```

---

### **Método 3: Comando SQL Direto** (Não Recomendado)

```bash
mysql -u seu_usuario -p impex_project_final
```

```sql
-- Inserir usuário
INSERT INTO users (name, email, password, is_admin, email_verified_at, locale, created_at, updated_at)
VALUES (
    'Administrador',
    'admin@impex.com',
    '$2y$12$...',  -- Hash da senha (use Hash::make() no Laravel)
    1,
    NOW(),
    'en',
    NOW(),
    NOW()
);

-- Criar role
INSERT INTO roles (name, guard_name, created_at, updated_at)
VALUES ('super_admin', 'web', NOW(), NOW());

-- Atribuir role ao usuário
INSERT INTO model_has_roles (role_id, model_type, model_id)
VALUES (
    (SELECT id FROM roles WHERE name = 'super_admin'),
    'App\\Models\\User',
    (SELECT id FROM users WHERE email = 'admin@impex.com')
);
```

---

## 🔐 Segurança

### **Senhas Fortes**

Use senhas fortes para o super admin:
- Mínimo 12 caracteres
- Letras maiúsculas e minúsculas
- Números
- Caracteres especiais

**Exemplo de senha forte:** `Adm!n@2025#Impex`

### **Trocar Senha Após Primeiro Acesso**

Após criar o super admin, acesse o sistema e troque a senha:

1. Acesse http://localhost:8000/admin
2. Faça login com as credenciais criadas
3. Vá em **Perfil** ou **Configurações**
4. Altere a senha

---

## 🎭 Roles e Permissões

O projeto usa **Spatie Permission** para gerenciar roles e permissões.

### **Roles Padrão:**

- `super_admin` - Acesso total ao sistema
- `admin` - Acesso administrativo
- `panel_user` - Usuário comum do painel

### **Criar Outras Roles:**

```php
use Spatie\Permission\Models\Role;

// Criar role
$role = Role::create([
    'name' => 'manager',
    'guard_name' => 'web'
]);

// Atribuir a um usuário
$user->assignRole('manager');
```

---

## 🐛 Troubleshooting

### **Erro: "Field 'can_see_all' doesn't have a default value"**

**Causa:** A migration da tabela `roles` está faltando valor padrão.

**Solução:**

```bash
# Editar migration
nano database/migrations/*create_roles_table.php

# Mudar linha 18 de:
$table->integer('can_see_all');

# Para:
$table->integer('can_see_all')->default(0);

# Resetar migrations
php artisan migrate:fresh
```

---

### **Erro: "Class 'Spatie\Permission\Models\Role' not found"**

**Causa:** Pacote Spatie Permission não instalado.

**Solução:**

```bash
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
```

---

### **Erro: "SQLSTATE[23000]: Integrity constraint violation"**

**Causa:** Email duplicado ou constraint violada.

**Solução:**

```bash
# Verificar usuários existentes
php artisan tinker
User::all();

# Deletar usuário duplicado
User::where('email', 'admin@impex.com')->delete();

# Criar novamente
php create_super_admin.php
```

---

## 📊 Verificar Usuários Criados

```bash
php artisan tinker
```

```php
// Listar todos os usuários
User::all();

// Verificar roles de um usuário
$user = User::where('email', 'admin@impex.com')->first();
$user->roles;

// Verificar permissões
$user->getAllPermissions();

// Verificar se é super admin
$user->hasRole('super_admin');
```

---

## 🎓 Boas Práticas

1. ✅ **Sempre crie um super admin após resetar o banco**
2. ✅ **Use senhas fortes**
3. ✅ **Não compartilhe credenciais de super admin**
4. ✅ **Crie usuários específicos para cada pessoa**
5. ✅ **Use roles apropriadas para cada usuário**
6. ✅ **Revise permissões regularmente**

---

## 🔄 Workflow Completo

```bash
# 1. Resetar banco de dados
php artisan migrate:fresh

# 2. Criar super admin
php create_super_admin.php

# 3. Iniciar servidor
php artisan serve

# 4. Acessar painel
# http://localhost:8000/admin

# 5. Fazer login

# 6. Criar outros usuários pelo painel
```

---

**Última atualização:** 10 de dezembro de 2025  
**Versão:** 1.0.0
